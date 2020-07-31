<?php
namespace Riki\Checkout\Observer;

class ToOrderObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            return;
        }

        $order = $observer->getEvent()->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return;
        }

        $order->setCustomerFirstnamekana($quote->getCustomerFirstnamekana());
        $order->setCustomerLastnamekana($quote->getCustomerLastnamekana());
    }
}
