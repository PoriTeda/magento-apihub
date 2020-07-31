<?php

namespace Riki\Checkout\Observer;

class PrepareShippingAddressList implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        if ($quote->getData('is_multiple_shipping')) {
            $order->setNeedToSaveMultipleShippingAddresses(true);
        }
    }
}