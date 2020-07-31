<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Riki\ThirdPartyImportExport\Model\ExportNextDelivery;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\CallbackInvoker;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\LockInterface;
use Magento\Framework\MessageQueue\MessageController;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\MessageLockException;
use Magento\Framework\MessageQueue\MessageValidator;
use Magento\Framework\MessageQueue\QueueInterface;
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
     * @var ConsumerConfigurationInterface
     */
    private $configuration;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var CallbackInvoker
     */
    private $invoker;

    /**
     * @var MessageController
     */
    private $messageController;

    /**
     * @var QueueRepository
     */
    private $queueRepository;

    /**
     * @var EnvelopeFactory
     */
    private $envelopeFactory;

    /**
     * @var MessageValidator
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
     * {@inheritdoc}
     */
    public function __construct(
        CallbackInvoker $invoker,
        MessageEncoder $messageEncoder,
        ResourceConnection $resource,
        ConsumerConfigurationInterface $configuration,
        LoggerInterface $logger = null
    ) {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $om->get(\Magento\Framework\App\State::class)->setAreaCode('crontab');

        parent::__construct($invoker, $messageEncoder, $resource, $configuration, $logger);

        $this->invoker = $invoker;
        $this->messageEncoder = $messageEncoder;
        $this->resource = $resource;
        $this->configuration = $configuration;
        $this->logger = $logger ?: \Magento\Framework\App\ObjectManager::getInstance()->get(LoggerInterface::class);
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
     * {@inheritdoc}
     */
    private function dispatchMessage(EnvelopeInterface $message, $isSync = false)
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
                $consumerName = $this->configuration->getConsumerName();

                foreach ($handlers as $callback) {
                    $result = call_user_func($callback, $decodedMessage, $consumerName);
                    if ($isSync) {
                        return $this->processSyncResponse($topicName, $result);
                    }
                }
            }
        }
        return null;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    private function sendResponse(EnvelopeInterface $envelope)
    {
        $messageProperties = $envelope->getProperties();
        $connectionName = $this->getConsumerConfig()
            ->getConsumer($this->configuration->getConsumerName())->getConnection();
        $queue = $this->getQueueRepository()->get($connectionName, $messageProperties['reply_to']);
        $queue->push($envelope);
    }

    /**
     * {@inheritdoc}
     */
    private function getTransactionCallback(QueueInterface $queue)
    {
        return function (EnvelopeInterface $message) use ($queue) {
            /** @var LockInterface $lock */
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
            } catch (MessageLockException $exception) {
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
     * {@inheritdoc}
     */
    private function getConsumerConfig()
    {
        if ($this->consumerConfig === null) {
            $this->consumerConfig = \Magento\Framework\App\ObjectManager::getInstance()->get(ConsumerConfig::class);
        }
        return $this->consumerConfig;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    private function getQueueRepository()
    {
        if ($this->queueRepository === null) {
            $this->queueRepository = \Magento\Framework\App\ObjectManager::getInstance()->get(QueueRepository::class);
        }
        return $this->queueRepository;
    }

    /**
     * {@inheritdoc}
     */
    private function getMessageController()
    {
        if ($this->messageController === null) {
            $this->messageController = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(MessageController::class);
        }
        return $this->messageController;
    }

    /**
     * {@inheritdoc}
     */
    private function getMessageValidator()
    {
        if ($this->messageValidator === null) {
            $this->messageValidator = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(MessageValidator::class);
        }
        return $this->messageValidator;
    }

    /**
     * {@inheritdoc}
     */
    private function getEnvelopeFactory()
    {
        if ($this->envelopeFactory === null) {
            $this->envelopeFactory = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(EnvelopeFactory::class);
        }
        return $this->envelopeFactory;
    }
}
