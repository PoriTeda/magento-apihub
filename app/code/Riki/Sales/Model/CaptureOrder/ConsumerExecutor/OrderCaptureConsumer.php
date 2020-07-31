<?php
namespace Riki\Sales\Model\CaptureOrder\ConsumerExecutor;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\MessageQueue\Config\Data as MessageQueueConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\ConnectionLostException;
use Magento\Framework\Communication\ConfigInterface as CommunicationConfig;
use Magento\Framework\MessageQueue\ConsumerConfigurationInterface;
use Magento\Framework\MessageQueue\ConsumerInterface;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\QueueInterface;
use Magento\Framework\MessageQueue\QueueRepository;
use Riki\MessageQueue\Exception\MessageLocalizedException;
use Magento\Framework\MessageQueue\CallbackInvoker;

/**
 * A MessageQueue Consumer to handle receiving a message.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderCaptureConsumer implements ConsumerInterface
{
    /**
     * @var MessageQueueConfig
     */
    private $messageQueueConfig;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var ConsumerConfigurationInterface
     */
    private $configuration;

    /**
     * @var QueueRepository
     */
    private $queueRepository;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var CallbackInvoker
     */
    private $invoker;

    /**
     * OrderCaptureConsumer constructor.
     * @param MessageQueueConfig $messageQueueConfig
     * @param MessageEncoder $messageEncoder
     * @param QueueRepository $queueRepository
     * @param State $state
     */
    public function __construct(
        MessageQueueConfig $messageQueueConfig,
        MessageEncoder $messageEncoder,
        ConsumerConfigurationInterface $configuration,
        QueueRepository $queueRepository,
        CallbackInvoker $invoker,
        State $state
    ) {
        $this->messageQueueConfig = $messageQueueConfig;
        $this->messageEncoder = $messageEncoder;
        $this->configuration = $configuration;
        $this->queueRepository = $queueRepository;
        $this->invoker = $invoker;
        $this->appState = $state;
    }

    /**
     * {@inheritdoc}
     */
    public function process($maxNumberOfMessages = null)
    {
        $this->appState->setAreaCode(Area::AREA_CRONTAB);

        $queue = $this->configuration->getQueue();

        if (!isset($maxNumberOfMessages)) {
            $queue->subscribe($this->getTransactionCallback($queue));
        } else {
            $this->invoker->invoke($queue, $maxNumberOfMessages, $this->getTransactionCallback($queue));
        }
    }

    /**
     * Decode message and invoke callback method
     *
     * @param EnvelopeInterface $message
     * @param bool $isSync
     * @return void
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
     * Run short running process
     *
     * @param QueueInterface $queue
     * @param int $maxNumberOfMessages
     * @return void
     */
    private function run(QueueInterface $queue, $maxNumberOfMessages)
    {
        $count = $maxNumberOfMessages
            ? $maxNumberOfMessages
            : $this->configuration->getMaxMessages() ?: 1;

        $transactionCallback = $this->getTransactionCallback($queue);
        for ($i = $count; $i > 0; $i--) {
            $message = $queue->dequeue();
            if ($message === null) {
                break;
            }
            $transactionCallback($message);
        }
    }

    /**
     * Run process in the daemon mode
     *
     * @param QueueInterface $queue
     * @return void
     */
    private function runDaemonMode(QueueInterface $queue)
    {
        $callback = $this->getTransactionCallback($queue);

        $queue->subscribe($callback);
    }

//    /**
//     * @return QueueInterface
//     * @throws LocalizedException
//     */
//    private function getQueue()
//    {
//        $queueName = $this->configuration->getQueueName();
//        $consumerName = $this->configuration->getConsumerName();
//        $connectionName = $this->messageQueueConfig->getConnectionByConsumer($consumerName);
//        $queue = $this->queueRepository->get($connectionName, $queueName);
//
//        return $queue;
//    }

    /**
     * @param QueueInterface $queue
     * @return \Closure
     */
    private function getTransactionCallback(QueueInterface $queue)
    {
        return function (EnvelopeInterface $message) use ($queue) {
            try {
                $this->dispatchMessage($message);
                $queue->acknowledge($message);
            } catch (ConnectionLostException $e) {
            } catch (MessageLocalizedException $e) {
            } catch (\Exception $e) {
                $queue->reject($message);
            }
        };
    }
}
