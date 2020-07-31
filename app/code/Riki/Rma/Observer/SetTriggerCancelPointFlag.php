<?php

namespace Riki\Rma\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class SetTriggerCancelPointFlag implements ObserverInterface
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $rmaHelper;

    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SetTriggerCancelPointFlag constructor.
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Riki\Rma\Helper\Amount $amountHelper,
        LoggerInterface $logger
    )
    {
        $this->amountHelper = $amountHelper;
        $this->rmaHelper = $amountHelper->getDataHelper();
        $this->logger = $logger;
    }

    /**
     * When a RMA is changed, trigger cancel point flag has to be re-calculated.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var \Riki\Rma\Model\Rma $rma */
        $rma = $observer->getRma();

        if ($rma->getData('in_set_trigger_cancel_point_flag')) {
            return;
        }

        $statuses = $this->rmaHelper->getStageTwoStatuses();

        if (!$rma->getId()) {
            $triggerCancelPointFlag = 0;
            if ($rma->canTriggerCancelPoint() && ($rma->getIsFull() || $rma->getDueToConsumer())) {
                $triggerCancelPointFlag = 1;
            }
            $rma->isTriggerCancelPoint($triggerCancelPointFlag);
        } elseif ($rma->getData('is_closed')) {
            $rma->isTriggerCancelPoint(0);
        } elseif ($rma->isTriggerCancelPoint()
            && $rma->getIsPartial()
            && ($rma->getDueToNestle() || $this->amountHelper->isFreeReturn($rma))
        ) {
            // remove the flag
            $rma->isTriggerCancelPoint(0);

            // set the flag again on other return
            $siblingRmas = $rma->getSiblingRmas();
            foreach ($siblingRmas as $siblingRma) {
                if ($siblingRma->getIsPartial() && $siblingRma->getDueToNestle()) {
                    continue;
                }

                if (!in_array($siblingRma->getReturnStatus(), $statuses)) {
                    continue;
                }

                $siblingRma->isTriggerCancelPoint(1);
                $siblingRma->setSource($rma);

                // Sibling RMAs will be save under current RMA's transaction
                $siblingRma->setData('in_set_trigger_cancel_point_flag', true)->save();

                break;
            }
        } elseif (!$rma->isTriggerCancelPoint()
            && $rma->canTriggerCancelPoint()
            && ($rma->getIsFull() || $rma->getDueToConsumer())
            && in_array($rma->getReturnStatus(), $statuses)
        ) {
            // set the flag
            $rma->isTriggerCancelPoint(1);
        }
    }
}
