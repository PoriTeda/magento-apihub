<?php

namespace Riki\MessageQueue;

use Magento\Framework\MessageQueue\QueueInterface;

class CallbackInvoker extends \Magento\Framework\MessageQueue\CallbackInvoker
{

    /**
     * @var Helper\QueueDataHelper
     */
    private $queueDataHelper;

    public function __construct(
        \Riki\MessageQueue\Helper\QueueDataHelper $queueDataHelper
    )
    {
        $this->queueDataHelper = $queueDataHelper;
    }

    /**
     * Run short running process
     *
     * @param QueueInterface $queue
     * @param int $maxNumberOfMessages
     * @param \Closure $callback
     * @return void
     */
    public function invoke(QueueInterface $queue, $maxNumberOfMessages, $callback)
    {
        for ($i = $maxNumberOfMessages; $i > 0; $i--) {
            do {
                if ($this->queueDataHelper->isDisable()) {
                    break 2;
                }
                $message = $queue->dequeue();
            } while ($message === null && (sleep(1) === 0));
            $callback($message);
        }
    }
}