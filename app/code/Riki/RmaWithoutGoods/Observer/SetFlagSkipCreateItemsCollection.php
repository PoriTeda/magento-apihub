<?php

namespace Riki\RmaWithoutGoods\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetFlagSkipCreateItemsCollection implements ObserverInterface
{
    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $rmaData = $observer->getData('rma_data');
        $rmaData->setData('skip_create_items_collection', $rmaData->getData('is_without_goods'));
    }
}
