<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Riki\Subscription\Model\Profile\ResourceModel\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\MessageQueue\Consumer\ConfigInterface as ConsumerConfig;
use Magento\Framework\Communication\ConfigInterface as CommunicationConfig;
use Magento\Framework\MessageQueue\QueueRepository;
use Psr\Log\LoggerInterface;

/**
 * A MessageQueue Consumer to handle receiving a message.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Consumer extends \Magento\Framework\MessageQueue\Consumer
{
    /**
     * @var \Magento\Framework\MessageQueue\ConsumerConfigurationInterface
     */
    private $configuration;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Framework\MessageQueue\MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var \Magento\Framework\MessageQueue\CallbackInvoker
     */
    private $invoker;

    /**
     * @var \Magento\Framework\MessageQueue\MessageController
     */
    private $messageController;

    /**
     * @var \Magento\Framework\MessageQueue\QueueRepository
     */
    private $queueRepository;

    /**
     * @var \Magento\Framework\MessageQueue\EnvelopeFactory
     */
    private $envelopeFactory;

    /**
     * @var \Magento\Framework\MessageQueue\MessageValidator
     */
    private $messageValidator;

    /**
     * @var ConsumerConfig
     */
    private $consumerConfig;

    /**
     * @var CommunicationConfig
     */
    private $communicationConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\MessageQueue\CallbackInvoker $invoker
     * @param \Magento\Framework\MessageQueue\MessageEncoder $messageEncoder
     * @param ResourceConnection $resource
     * @param \Magento\Framework\MessageQueue\ConsumerConfigurationInterface $configuration
     * @param LoggerInterface $logger
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Framework\MessageQueue\CallbackInvoker $invoker,
        \Magento\Framework\MessageQueue\MessageEncoder $messageEncoder,
        ResourceConnection $resource,
        \Magento\Framework\MessageQueue\ConsumerConfigurationInterface $configuration,
        LoggerInterface $logger = null
    ) {
        $this->invoker = $invoker;
        $this->messageEncoder = $messageEncoder;
        $this->resource = $resource;
        $this->configuration = $configuration;
        $this->logger = $logger ?: \Magento\Framework\App\ObjectManager::getInstance()->get(LoggerInterface::class);
        parent::__construct($invoker, $messageEncoder, $resource, $configuration, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function process($maxNumberOfMessages = null)
    {
        $queue = $this->configuration->getQueue();

        if (!isset($maxNumberOfMessages)) {
            $queue->subscribe($this->getTransactionCallback($queue));
        } else {
            $this->invoker->invoke($queue, $maxNumberOfMessages, $this->getTransactionCallback($queue));
        }
    }

    /**
     * Decode message and invoke callback method, return reply back for sync processing.
     *
     * @param \Magento\Framework\MessageQueue\EnvelopeInterface $message
     * @param boolean $isSync
     * @return string|null
     * @throws LocalizedException
     */
    private function dispatchMessage(\Magento\Framework\MessageQueue\EnvelopeInterface $message, $isSync = false)
    {
        $properties = $message->getProperties();
        $topicName = $properties['topic_name'];
        $handlers = $this->configuration->getHandlers($topicName);
        $decodedMessage = $this->messageEncoder->decode($topicName, $message->getBody());

        if (isset($decodedMessage)) {
            $messageSchemaType = $this->configuration->getMessageSchemaType($topicName);
            if ($messageSchemaType == CommunicationConfig::TOPIC_REQUEST_TYPE_METHOD) {
                foreach ($handlers as $callback) {
                    $result = call_user_func_array($callback, $decodedMessage);
                    return $this->processSyncResponse($topicName, $result);
                }
            } else {
                foreach ($handlers as $callback) {
                    $result = call_user_func($callback, $decodedMessage);
                    if ($isSync) {
                        return $this->processSyncResponse($topicName, $result);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Validate and encode synchronous handler output.
     *
     * @param string $topicName
     * @param mixed $result
     * @return string
     * @throws LocalizedException
     */
    private function processSyncResponse($topicName, $result)
    {
        if (isset($result)) {
            $this->getMessageValidator()->validate($topicName, $result, false);
            return $this->messageEncoder->encode($topicName, $result, false);
        } else {
            throw new LocalizedException(new Phrase('No reply message resulted in RPC.'));
        }
    }

    /**
     * Send RPC response message.
     *
     * @param \Magento\Framework\MessageQueue\EnvelopeInterface $envelope
     * @return void
     */
    private function sendResponse(\Magento\Framework\MessageQueue\EnvelopeInterface $envelope)
    {
        $messageProperties = $envelope->getProperties();
        $connectionName = $this->getConsumerConfig()
            ->getConsumer($this->configuration->getConsumerName())->getConnection();
        $queue = $this->getQueueRepository()->get($connectionName, $messageProperties['reply_to']);
        $queue->push($envelope);
    }

    /**
     * Get transaction callback. This handles the case of both sync and async.
     *
     * @param QueueInterface $queue
     * @return \Closure
     */
    private function getTransactionCallback(\Magento\Framework\MessageQueue\QueueInterface $queue)
    {
        return function (\Magento\Framework\MessageQueue\EnvelopeInterface $message) use ($queue) {
            /** @var \Magento\Framework\MessageQueue\LockInterface $lock */
            $lock = null;
            try {
                $topicName = $message->getProperties()['topic_name'];
                $topicConfig = $this->getCommunicationConfig()->getTopic($topicName);
                $lock = $this->getMessageController()->lock($message, $this->configuration->getConsumerName());

                if ($topicConfig[CommunicationConfig::TOPIC_IS_SYNCHRONOUS]) {
                    $responseBody = $this->dispatchMessage($message, true);
                    $responseMessage = $this->getEnvelopeFactory()->create(
                        ['body' => $responseBody, 'properties' => $message->getProperties()]
                    );
                    $this->sendResponse($responseMessage);
                } else {
                    $allowedTopics = $this->configuration->getTopicNames();
                    if (in_array($topicName, $allowedTopics)) {
                        $this->dispatchMessage($message);
                    } else {
                        $queue->reject($message);
                        return;
                    }
                }
                $queue->acknowledge($message);
            } catch (\Magento\Framework\MessageQueue\MessageLockException $exception) {
                $queue->acknowledge($message);
            } catch (\Magento\Framework\MessageQueue\ConnectionLostException $e) {
                if ($lock) {
                    $this->resource->getConnection()
                        ->delete($this->resource->getTableName('queue_lock'), ['id = ?' => $lock->getId()]);
                }
            } catch (\Magento\Framework\Exception\NotFoundException $e) {
                $queue->acknowledge($message);
                $this->logger->warning($e->getMessage());
            } catch (\Exception $e) {
                $queue->reject($message, false, $e->getMessage());
                if ($lock) {
                    $this->resource->getConnection()
                        ->delete($this->resource->getTableName('queue_lock'), ['id = ?' => $lock->getId()]);
                }
            }
        };
    }

    /**
     * Get consumer config.
     *
     * @return ConsumerConfig
     *
     * @deprecated 100.2.0
     */
    private function getConsumerConfig()
    {
        if ($this->consumerConfig === null) {
            $this->consumerConfig = \Magento\Framework\App\ObjectManager::getInstance()->get(ConsumerConfig::class);
        }
        return $this->consumerConfig;
    }

    /**
     * Get communication config.
     *
     * @return CommunicationConfig
     *
     * @deprecated 100.2.0
     */
    private function getCommunicationConfig()
    {
        if ($this->communicationConfig === null) {
            $this->communicationConfig = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(CommunicationConfig::class);
        }
        return $this->communicationConfig;
    }

    /**
     * Get queue repository.
     *
     * @return QueueRepository
     *
     * @deprecated 100.2.0
     */
    private function getQueueRepository()
    {
        if ($this->queueRepository === null) {
            $this->queueRepository = \Magento\Framework\App\ObjectManager::getInstance()->get(QueueRepository::class);
        }
        return $this->queueRepository;
    }

    /**
     * Get message controller.
     *
     * @return \Magento\Framework\MessageQueue\MessageController
     *
     * @deprecated 100.1.0
     */
    private function getMessageController()
    {
        if ($this->messageController === null) {
            $this->messageController = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\MessageQueue\MessageController::class);
        }
        return $this->messageController;
    }

    /**
     * Get message validator.
     *
     * @return \Magento\Framework\MessageQueue\MessageValidator
     *
     * @deprecated 100.2.0
     */
    private function getMessageValidator()
    {
        if ($this->messageValidator === null) {
            $this->messageValidator = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\MessageQueue\MessageValidator::class);
        }
        return $this->messageValidator;
    }

    /**
     * Get envelope factory.
     *
     * @return \Magento\Framework\MessageQueue\EnvelopeFactory
     *
     * @deprecated 100.2.0
     */
    private function getEnvelopeFactory()
    {
        if ($this->envelopeFactory === null) {
            $this->envelopeFactory = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\MessageQueue\EnvelopeFactory::class);
        }
        return $this->envelopeFactory;
    }
}