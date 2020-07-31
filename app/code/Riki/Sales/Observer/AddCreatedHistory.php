<?php

namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddCreatedHistory implements ObserverInterface
{

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $order->addStatusHistoryComment('')->setIsCustomerNotified(false);

        return $this;
    }
}