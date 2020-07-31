<?php

namespace Bluecom\PaymentFee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SetFreeFeeFlag implements ObserverInterface
{
    /**
     * @param EventObserver $observer observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($observer->getEvent()->getData('rule')->getFreeCodCharge()) {
            $observer->getEvent()->getData('address')->setFreeSurchargeFee(true);
        }
    }
}
