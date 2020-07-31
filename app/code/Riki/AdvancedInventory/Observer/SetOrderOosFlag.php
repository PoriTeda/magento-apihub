<?php
namespace Riki\AdvancedInventory\Observer;

use Riki\AdvancedInventory\Model\OutOfStock;

class SetOrderOosFlag implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        $order->setData(OutOfStock::OOS_FLAG, $quote->getData(OutOfStock::OOS_FLAG));
    }
}