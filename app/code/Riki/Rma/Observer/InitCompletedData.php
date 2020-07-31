<?php
namespace Riki\Rma\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Riki\Rma\Api\Data\Rma\RefundStatusInterface;
use Riki\Rma\Helper\Refund as RefundHelper;
use Riki\Rma\Model\RmaManagement;

/**
 * Class InitCompletedData
 * @package Riki\Rma\Observer
 */
class InitCompletedData implements ObserverInterface
{
    /**
     * @var RefundHelper
     */
    protected $refundHelper;

    /**
     * InitCompletedData constructor.
     * @param RefundHelper $refundHelper
     */
    public function __construct(
        RefundHelper $refundHelper
    ) {
        $this->refundHelper = $refundHelper;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /* @var $rma \Riki\Rma\Model\Rma */
        $rma = $observer->getRma();

        if ($rma->getData(RmaManagement::IS_APPROVE_REQUESTED_FLAG_NAME)) {
            if ($rma->getRefundAllowed() && $rma->getTotalReturnAmountAdjusted()) {
                if (is_null($rma->getRefundMethod())) {
                    $refundMethods = $this->refundHelper->getRefundMethodsByPaymentMethod(
                        $rma->getOrderPaymentMethod(),
                        $rma
                    );
                    $rma->setRefundMethod(key($refundMethods));
                }

                $rma->setRefundStatus(RefundStatusInterface::WAITING_APPROVAL);
            }
        }
    }
}
