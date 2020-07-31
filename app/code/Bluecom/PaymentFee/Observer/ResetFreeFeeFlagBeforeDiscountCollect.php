<?php

namespace Bluecom\PaymentFee\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ResetFreeFeeFlagBeforeDiscountCollect implements ObserverInterface
{
    /**
     * @param Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $observer->getEvent()
            ->getData('shipping_assignment')
            ->getShipping()
            ->getAddress()
            ->setFreeSurchargeFee(false);
    }
}
