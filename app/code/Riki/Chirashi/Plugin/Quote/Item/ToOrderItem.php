<?php
namespace Riki\Chirashi\Plugin\Quote\Item;

class ToOrderItem
{
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional = []
    ) {
        $orderItem = $proceed($item, $additional);
        $orderItem->setChirashi($item->getChirashi());
        return $orderItem;
    }
}
