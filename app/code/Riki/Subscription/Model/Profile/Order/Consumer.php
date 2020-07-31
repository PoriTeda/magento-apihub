<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Model\Profile\Order;

use Magento\Framework\MessageQueue\CallbackInvoker;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\ConnectionLostException;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface;
use Magento\Framework\MessageQueue\MessageValidator;
use Magento\Framework\MessageQueue\QueueRepository;
use Magento\Framework\MessageQueue\QueueInterface;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Communication\ConfigInterface as CommunicationConfig;
use Psr\Log\LoggerInterface;

/**
 * Class Consumer
 * @package Riki\Subscription\Model\Profile\Order
 */
class Consumer extends \Magento\Framework\MessageQueue\Consumer
{
    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var ConsumerConfigurationInterface
     */
    private $configuration;

    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Riki\Subscription\Logger\LoggerPublishMessageQueue
     */
    protected $loggerQueue;

    /**
     * @var \Riki\Subscription\Model\Email\ProfilePaymentMethodErrorBusiness
     */
    protected $profilePaymentMethodErrorBusinessEmail;

    /**
     * @var CallbackInvoker
     */
    private $invoker;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MessageValidator
     */
    private $messageValidator;

    protected $newrelic;

    /**
     * Consumer constructor.
     * @param MessageQueueConfig $messageQueueConfig
     * @param MessageEncoder $messageEncoder
     * @param QueueRepository $queueRepository
     * @param ResourceConnection $resource
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Logger\LoggerPublishMessageQueue $messageQueue
     * @param \Riki\Subscription\Model\Email\ProfilePaymentMethodErrorBusiness $profilePaymentMethodErrorBusiness
     * @param \Riki\Subscription\Helper\Newrelic $newrelic
     */
    public function __construct(
        CallbackInvoker $invoker,
        MessageEncoder $messageEncoder,
        ResourceConnection $resource,
        ConsumerConfigurationInterface $configuration,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Logger\LoggerPublishMessageQueue $loggerPublishMessageQueue,
        \Riki\Subscription\Model\Email\ProfilePaymentMethodErrorBusiness $profilePaymentMethodErrorBusiness,
        \Riki\Subscription\Helper\Newrelic $newrelic,
        LoggerInterface $logger = null
    ) {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $om->get('Magento\Framework\App\State')->setAreaCode('crontab');
        $this->invoker = $invoker;
        $this->messageEncoder = $messageEncoder;
        $this->resource = $resource;
        $this->configuration = $configuration;
        $this->profileFactory = $profileFactory;
        $this->loggerQueue = $loggerPublishMessageQueue;
        $this->profilePaymentMethodErrorBusinessEmail = $profilePaymentMethodErrorBusiness;
        $this->newrelic = $newrelic;
        $this->logger = $logger ?: \Magento\Framework\App\ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ConsumerConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function process($maxNumberOfMessages = null)
    {
        $queue = $this->configuration->getQueue();

        $this->newrelic->ignoreTransaction();
        if (!isset($maxNumberOfMessages)) {
            $queue->subscribe($this->getTransactionCallback($queue));
        } else {
            $this->invoker->invoke($queue, $maxNumberOfMessages, $this->getTransactionCallback($queue));
        }
    }

    /**
     * Decode message and invoke callback method, return reply back for sync processing.
     *
     * @param EnvelopeInterface $message
     * @param boolean $isSync
     * @return string|null
     * @throws LocalizedException
     */
    private function dispatchMessage(EnvelopeInterface $message, $isSync = false)
    {
        $properties = $message->getProperties();
        $topicName = $properties['topic_name'];
        $handlers = $this->configuration->getHandlers($topicName);
        $decodedMessage = $this->messageEncoder->decode($topicName, $message->getBody());
        $consumerName = $this->configuration->getConsumerName();

        if (isset($decodedMessage)) {
            $messageSchemaType = $this->configuration->getMessageSchemaType($topicName);
            if ($messageSchemaType == CommunicationConfig::TOPIC_REQUEST_TYPE_METHOD) {
                foreach ($handlers as $callback) {
                    $result = call_user_func_array($callback, $decodedMessage);
                    return $this->processSyncResponse($topicName, $result);
                }
            } else {
                foreach ($handlers as $callback) {
                    $result = call_user_func($callback, $decodedMessage, 0, $consumerName);
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
     * Get message validator.
     *
     * @return MessageValidator
     *
     * @deprecated 100.2.0
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
     * @param QueueInterface $queue
     * @return \Closure
     */
    private function getTransactionCallback(QueueInterface $queue)
    {
        return function (EnvelopeInterface $message) use ($queue) {

            $this->newrelic->startTransaction();
            $this->newrelic->setNewRelicTransactionName('queue/generateSubscriptionOrder');
            $this->addCustomAttribute($message);

            try {
                $this->dispatchMessage($message);
                $queue->acknowledge($message);
            } catch (ConnectionLostException $e) {
                $this->updateProfile($message);
            } catch (\Exception $e) {
                if (preg_match('#SQLSTATE\[HY000\]: [^:]+: 1205[^\d]#', $e->getMessage())
                    || preg_match('#SQLSTATE\[40001\]: [^:]+: 1213[^\d]#', $e->getMessage())
                ) {
                    $queue->reject($message, true);
                } else {
                    $this->updateProfile($message);
                    $queue->reject($message, false);
                }
            } catch (\Error $e) {
                $this->loggerQueue->info($e->getMessage());
                $this->loggerQueue->info('The message is invalid');
                $queue->reject($message, false);
            }
            $this->profilePaymentMethodErrorBusinessEmail
                ->getVariables()
                ->setData('batchMode', 1);
            $this->profilePaymentMethodErrorBusinessEmail->send();

            $this->newrelic->endTransaction();
        };
    }

    /**
     * Reset value of column "publish_message" of profile failed to generate order
     * if: profile has version then reset value on version profile
     * else: reset on main profile
     *
     * @param $profileId
     */
    private function updateProfile($message)
    {
        $profileId  = null;
        $body = \Zend_Json::decode($message->getBody());
        foreach ($body['items'] as $profileObject) {
            $profileId = $profileObject['profile_id'];
        }
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $version = $om->create('\Riki\Subscription\Helper\Profile\Data')
            ->checkProfileHaveVersion($profileId);
        if ($version) {
            $profileModel = $this->profileFactory->create()->load($version);
        } else {
            $profileModel = $this->profileFactory->create()->load($profileId);
        }
        if ($profileModel->getId()) {
            $profileModel->setData('publish_message', 0);
            $this->loggerQueue->info('The message of profile # ' . $profileId . ' failed to run on queue.');
            try {
                $profileModel->save();
            } catch (\Exception $e) {
                $this->loggerQueue->critical($e);
            }
        }
    }

    /**
     * @param $message
     */
    public function addCustomAttribute($message)
    {
        $profileId = null;
        $data = json_decode($message->getBody(),true);
        if (isset($data['items'][0]['profile_id'])) {
            $profileId = $data['items'][0]['profile_id'];
            $this->newrelic->addCustomParameter('profileId', $profileId);
        }
    }
}