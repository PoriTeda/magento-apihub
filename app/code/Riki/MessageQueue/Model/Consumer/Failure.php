<?php
namespace Riki\MessageQueue\Model\Consumer;

use Magento\Framework\Logger\Monolog;
use Riki\Framework\Helper\Logger\LoggerBuilder;
use Riki\MessageQueue\Api\FailureItemInterface;
use Riki\MessageQueue\Exception\MessageLocalizedException;
use Riki\MessageQueue\Model\ResourceModel\QueueLock;

class Failure
{
    const FAILURE_TOPIC_NAME = 'failure.update';

    /**
     * @var
     */
    protected $loggers = [];

    /**
     * @var LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * @var FailureExecutorInterface[]
     */
    protected $executors = [];

    /**
     * @var QueueLock
     */
    protected $queueLock;

    /**
     * Failure constructor.
     * @param LoggerBuilder $loggerBuilder
     * @param QueueLock $queueLock
     * @param array $executors
     */
    public function __construct(
        LoggerBuilder $loggerBuilder,
        QueueLock $queueLock,
        $executors = []
    ) {
        $this->loggerBuilder = $loggerBuilder;
        $this->queueLock = $queueLock;
        $this->executors = $executors;
    }

    /**
     * @param FailureItemInterface $item
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processMessage(FailureItemInterface $item)
    {
        $entityId = $item->getEntityId();

        $executorName = $item->getExecutor();

        $logger = $this->getLogger($executorName);

        $logger->info(__('START: %1', $entityId));

        try {
            if (isset($this->executors[$executorName])) {
                $this->executors[$executorName]->process($entityId);
            }
        } catch (MessageLocalizedException $e) {
            $logger->critical($e);
            throw $e;
        } catch (\Exception $e) {
            $logger->critical($e);
        }

        $this->queueLock->deleteLock(self::FAILURE_TOPIC_NAME, $item->getEntityId(), $item->getExecutor());

        return;
    }

    /**
     * @param $executor
     * @return Monolog
     * @throws \Exception
     */
    public function getLogger($executor)
    {
        if (!isset($this->loggers[$executor])) {
            $this->loggers[$executor] = $this->loggerBuilder
                ->setName('Riki_FailureMQ')
                ->setFileName($executor . '.log')
                ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();
        }

        return $this->loggers[$executor];
    }
}
