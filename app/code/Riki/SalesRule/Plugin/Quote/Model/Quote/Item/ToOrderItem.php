<?php

namespace Riki\SalesRule\Plugin\Quote\Model\Quote\Item;

class ToOrderItem
{

    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional
    ) {
        /** @var $orderItem \Magento\Sales\Model\Order\Item */
        $orderItem = $proceed($item, $additional);

        $orderItem->setAppliedRulesBreakdown($item->getAppliedRulesBreakdown());

        return $orderItem;
    }
}