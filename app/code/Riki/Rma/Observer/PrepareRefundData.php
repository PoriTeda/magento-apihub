<?php

namespace Riki\Rma\Observer;

use Magento\Framework\Event\ObserverInterface;

class PrepareRefundData implements ObserverInterface
{
    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $returnHelper;

    /**
     * Constructor.
     *
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Riki\Rma\Helper\Refund $refundHelper
     * @param \Riki\Rma\Helper\Data $returnHelper
     */
    public function __construct(
        \Riki\Rma\Helper\Amount $amountHelper,
        \Riki\Rma\Helper\Refund $refundHelper,
        \Riki\Rma\Helper\Data $returnHelper
    ) {
        $this->amountHelper = $amountHelper;
        $this->refundHelper = $refundHelper;
        $this->returnHelper = $returnHelper;
    }

    /**
     * Set value to refund_allowed, refund_method field before a RMA is saved.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $rma = $observer->getRma();

        if (!$rma->getId() && $rma instanceof \Magento\Rma\Model\Rma) {
            // Set value to refund_allowed field.
            if ($this->amountHelper->isAllowedRefund($rma)) {
                $rma->setData('refund_allowed', 1);
            }

            // Get refund method.
            $refundMethods = $this->refundHelper->getRefundMethodsByPaymentMethod(
                $this->returnHelper->getRmaOrderPaymentMethodCode($rma),
                $rma
            );

            // Set value to refund_method field.
            if (!empty($refundMethods) && empty($rma->getRefundMethod())) {
                $defaultRefundMethod = current(array_keys($refundMethods));
                $rma->setData('refund_method', $defaultRefundMethod);
            }
        }
    }
}
