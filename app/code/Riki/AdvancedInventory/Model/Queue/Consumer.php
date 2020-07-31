<?php

namespace Riki\AdvancedInventory\Model\Queue;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\CallbackInvoker;
use Magento\Framework\MessageQueue\Consumer\ConfigInterface;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\LockInterface;
use Magento\Framework\MessageQueue\MessageController;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\MessageLockException;
use Magento\Framework\MessageQueue\MessageValidator;
use Magento\Framework\MessageQueue\QueueRepository;
use \Magento\Framework\MessageQueue\QueueInterface;
use Magento\Framework\Communication\ConfigInterface as CommunicationConfig;
use Magento\Framework\Phrase;
use Riki\AdvancedInventory\Api\Data\OutOfStock\QueueExecuteInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaInterface;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use \Magento\Framework\MessageQueue\Config\Data as MessageQueueConfig;
use \Magento\Framework\MessageQueue\ConnectionLostException;
use \Magento\Framework\MessageQueue\Config\Converter as MessageQueueConfigConverter;
use \Magento\Framework\Exception\ValidatorException;
use Riki\AdvancedInventory\Api\OutOfStockManagementInterface;
use Riki\AdvancedInventory\Exception\AssignationException;
use Bluecom\Paygent\Exception\PaygentAuthorizedException;
use Riki\AdvancedInventory\Exception\RestrictedOrderStatusException;
use Riki\AdvancedInventory\Model\OutOfStock;

class Consumer implements \Magento\Framework\MessageQueue\ConsumerInterface
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
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    private $dbTransaction;

    /**
     * @var \Riki\AdvancedInventory\Helper\Logger
     */
    private $loggerHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Riki\AdvancedInventory\Model\OutOfStock\Repository
     */
    private $outOfStockRepository;

    protected $outOfStockManagement;

    /**
     * @var \Riki\Subscription\Helper\Newrelic
     */
    protected $newrelic;

    /**
     * @var ConfigInterface
     */
    private $consumerConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var AreaList
     */
    protected $areList;

    /**
     * List of authorized fail OOS item
     *
     * @var array
     */
    protected $authorizeFailureOosItems = [];

    /**
     * @var CommunicationConfig
     */
    private $communicationConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $outOfStockHelper;

    public function __construct(
        CallbackInvoker $invoker,
        MessageEncoder $messageEncoder,
        ResourceConnection $resource,
        ConsumerConfigurationInterface $configuration,
        QueueRepository $queueRepository,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Riki\AdvancedInventory\Helper\Logger $loggerHelper,
        \Magento\Framework\Registry $registry,
        \Riki\AdvancedInventory\Model\OutOfStock\Repository $outOfStockRepository,
        OutOfStockManagementInterface $outOfStockManagement,
        \Riki\Subscription\Helper\Newrelic $newrelic,
        ScopeConfigInterface $scopeConfig,
        State $state,
        AreaList $areaList,
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper,
        LoggerInterface $logger = null
    ) {
        $this->invoker = $invoker;
        $state->setAreaCode(Area::AREA_CRONTAB);
        $this->messageEncoder = $messageEncoder;
        $this->resource = $resource;
        $this->configuration = $configuration;
        $this->queueRepository = $queueRepository;
        $this->dbTransaction = $dbTransaction;
        $this->loggerHelper = $loggerHelper;
        $this->registry = $registry;
        $this->outOfStockRepository = $outOfStockRepository;
        $this->outOfStockManagement = $outOfStockManagement;
        $this->newrelic = $newrelic;
        $this->logger = $logger ?: \Magento\Framework\App\ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->scopeConfig = $scopeConfig;
        $this->state = $state;
        $this->areList = $areaList;
        $this->outOfStockHelper = $outOfStockHelper;
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

            if (!empty($this->authorizeFailureOosItems)) {
                $area = $this->areList->getArea($this->state->getAreaCode());
                $area->load(AreaInterface::PART_TRANSLATE);
                $this->loggerHelper->getOosLogger()->info('Sending authorize failure email after 3 times retry');
                $this->outOfStockManagement->sendAuthorizeFailureEmail($this->authorizeFailureOosItems);
                $this->authorizeFailureOosItems = [];
            }
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
     * @param EnvelopeInterface $envelope
     * @return void
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
     * Get transaction callback. This handles the case of both sync and async.
     *
     * @param QueueInterface $queue
     * @return \Closure
     */
    private function getTransactionCallback(QueueInterface $queue)
    {
        return function (EnvelopeInterface $message) use ($queue) {
            /** @var LockInterface $lock */
            $lock = null;
            $this->newrelic->startTransaction();
            $this->newrelic->setNewRelicTransactionName('queue/generateOosOrder');
            $this->addCustomAttribute($message);

            // NED-7986
            if(!$this->outOfStockHelper->allowGenerateOrder($this->loggerHelper)) {
                $queue->reject($message, true);
                return;
            }

            try {
                $this->dbTransaction->beginTransaction();
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
                        $queue->reject($message, false);
                        return;
                    }
                }

                $queue->acknowledge($message);
                $this->dbTransaction->commit();
            } catch (MessageLockException $exception) {
                $queue->acknowledge($message);
            } catch (\Magento\Framework\MessageQueue\ConnectionLostException $e) {
                if ($lock) {
                    $this->resource->getConnection()
                        ->delete($this->resource->getTableName('queue_lock'), ['id = ?' => $lock->getId()]);
                }

                $this->dbTransaction->rollBack();
                $this->loggerHelper->getOosLogger()->critical($e);
            } catch (\Magento\Framework\Exception\NotFoundException $e) {
                $queue->acknowledge($message);
                $this->logger->warning($e->getMessage());

            } catch (RestrictedOrderStatusException $e) {
                $this->dbTransaction->rollBack();
                $queue->reject($message, false, $e->getMessage());
                $this->loggerHelper->getOosLogger()->warning($e);
                $this->resetQueueExecuteFlagForOutOfStockItem(QueueExecuteInterface::ERROR);
            } catch (\Magento\Framework\Exception\ValidatorException $e) {
                $this->dbTransaction->rollBack();
                $queue->reject($message, false, $e->getMessage());
                $this->loggerHelper->getOosLogger()->warning($e);
                $this->resetQueueExecuteFlagForOutOfStockItem();
            } catch (\Riki\AdvancedInventory\Exception\AssignationException $e) {
                $this->dbTransaction->rollBack();
                $queue->reject($message, false, $e->getMessage());
                $this->loggerHelper->getOosLogger()->warning($e);
                $this->resetQueueExecuteFlagForOutOfStockItem();
            } catch (\Bluecom\Paygent\Exception\PaygentAuthorizedException $e) {
                $this->dbTransaction->rollBack();
                $queue->reject($message, false, $e->getMessage());
                $this->loggerHelper->getOosLogger()->warning($e);
                $this->_updateAuthorizeTimes();

                if ($this->_canReauthorize()) {
                    $this->resetQueueExecuteFlagForOutOfStockItem();
                } else {
                    $this->resetQueueExecuteFlagForOutOfStockItem(QueueExecuteInterface::ERROR);
                    $this->_addAuthorizeFailureOos($e->getMessage());
                }
            } catch (LocalizedException $e) {
                $this->dbTransaction->rollBack();
                $queue->reject($message, false, $e->getMessage());
                $this->loggerHelper->getOosLogger()->critical($e);

                $oosQuote = $this->getCurrentOosQuote();
                if ($oosQuote instanceof \Magento\Quote\Model\Quote
                    && $oosQuote->getHasError()
                ) {
                    $this->resetQueueExecuteFlagForOutOfStockItem();
                } else {
                    $this->resetQueueExecuteFlagForOutOfStockItem(QueueExecuteInterface::ERROR);
                }

            } catch (\ErrorException $e) {
                $this->dbTransaction->rollBack();
                $queue->reject($message, false, $e->getMessage());
                $this->loggerHelper->getOosLogger()->critical($e);
                $this->resetQueueExecuteFlagForOutOfStockItem();
            } catch (\Exception $e) {
                $this->dbTransaction->rollBack();
                $queue->reject($message, false, $e->getMessage());
                $this->loggerHelper->getOosLogger()->critical($e);
                $this->resetQueueExecuteFlagForOutOfStockItem();
            }

            $this->newrelic->endTransaction();
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
            $this->consumerConfig = \Magento\Framework\App\ObjectManager::getInstance()->get(ConfigInterface::class);
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
     * @return MessageController
     *
     * @deprecated 100.1.0
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
     * Get envelope factory.
     *
     * @return EnvelopeFactory
     *
     * @deprecated 100.2.0
     */
    private function getEnvelopeFactory()
    {
        if ($this->envelopeFactory === null) {
            $this->envelopeFactory = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(EnvelopeFactory::class);
        }
        return $this->envelopeFactory;
    }

    /**
     * Get current oos
     *
     * @return \Riki\AdvancedInventory\Model\OutOfStock[]|null
     */
    public function getCurrentOos()
    {
        return $this->registry->registry('current_oos_generating');
    }

    /**
     * Get current oos quote
     *
     * @return \Magento\Quote\Model\Quote|null
     */
    public function getCurrentOosQuote()
    {
        return $this->registry->registry('current_oos_quote_generated');
    }

    /**
     * Change out of stock item queue_execute flag
     *
     * @param bool $queueExecute
     */
    private function resetQueueExecuteFlagForOutOfStockItem($queueExecute = false)
    {
        if (!$queueExecute) {
            $queueExecute = new \Zend_Db_Expr('NULL');
        }

        $oosList = $this->getCurrentOos();
        if ($oosList) {
            foreach ($oosList as $oos) {
                if ($oos instanceof OutOfStock) {
                    $oos->setData('queue_execute', $queueExecute);
                    try {
                        $this->outOfStockRepository->save($oos);
                    } catch (\Exception $e) {
                        $this->loggerHelper->getOosLogger()->info($e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function _canReauthorize()
    {
        $oosItems = $this->getCurrentOos();

        if ($oosItems) {
            $maximumAuthorizeTimes = $this->_getMaximumAuthorizeTimes();
            foreach ($oosItems as $oosItem) {
                if ($oosItem instanceof OutOfStock) {
                    $authorizedTime = (int)$oosItem->getAdditionalInformation(
                        OutOfStock::ADDITIONAL_INFORMATION_AUTHORIZE_TIMES
                    );
                    if ($authorizedTime >= $maximumAuthorizeTimes) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @return $this
     */
    private function _updateAuthorizeTimes()
    {
        $oosItems = $this->getCurrentOos();

        if ($oosItems) {
            foreach ($oosItems as $oosItem) {
                if ($oosItem instanceof OutOfStock) {
                    $authorizedTime = (int)$oosItem->getAdditionalInformation(
                        OutOfStock::ADDITIONAL_INFORMATION_AUTHORIZE_TIMES
                    );

                    $oosItem->setAdditionalInformation(
                        OutOfStock::ADDITIONAL_INFORMATION_AUTHORIZE_TIMES,
                        $authorizedTime + 1
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @param $message
     * @return $this
     */
    private function _addAuthorizeFailureOos($message)
    {
        $oosItems = $this->getCurrentOos();

        if ($oosItems) {
            foreach ($oosItems as $oosItem) {
                if ($oosItem instanceof OutOfStock) {
                    $this->authorizeFailureOosItems[$oosItem->getId()] = $message;
                }
            }
        }

        return $this;
    }

    /**
     * @return int
     */
    private function _getMaximumAuthorizeTimes()
    {
        return (int)$this->scopeConfig->getValue(
            OutOfStock::XML_PATH_MAXIMUM_ADDITIONAL_INFORMATION_AUTHORIZE_TIMES
        );
    }

    /**
     * @param $message
     */
    public function addCustomAttribute($message)
    {
        $message = json_decode($message->getBody(), true);
        if (isset($message['oos_model_id'])) {
            $this->newrelic->addCustomParameter('oosModelId', $message['oos_model_id']);
        }
    }
}
