<?php

namespace Riki\Sales\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class OrderItemCollectionUnserializeFields implements ObserverInterface
{
    /**
     * Un-serialize fields
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $collection = $observer->getOrderItemCollection();
        foreach ($collection->getItems() as $item) {
            $collection->getResource()->unserializeFields($item);
        }
    }
}
