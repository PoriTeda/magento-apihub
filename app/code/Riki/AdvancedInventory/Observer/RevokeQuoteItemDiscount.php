<?php

namespace Riki\AdvancedInventory\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RevokeQuoteItemDiscount implements ObserverInterface
{
    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $item = $observer->getItem();
        if ($item->getOosUniqKey()) {
            $discount = $observer->getDiscount();
            $total = $observer->getTotal();

            $total->addTotalAmount($discount->getCode(), $item->getDiscountAmount());
            $total->addBaseTotalAmount($discount->getCode(), $item->getBaseDiscountAmount());
        }
    }
}
