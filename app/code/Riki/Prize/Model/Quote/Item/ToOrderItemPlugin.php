<?php
namespace Riki\Prize\Model\Quote\Item;

class ToOrderItemPlugin
{
    /**
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param $additional
     * @return \Magento\Sales\Model\Order\Item
     */
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional
    ) {
        /** @var $orderItem \Magento\Sales\Model\Order\Item */
        $orderItem = $proceed($item, $additional);
        /** @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $item;


        if ($quoteItem->hasData('prize_id')) {
            $orderItem->setData('prize_id', $quoteItem->getData('prize_id'));
            if ($quoteItem->getData('prize_id')) {
                /*set free of charge = 1 at sales order item for prize and winer case*/
                $orderItem->setData('free_of_charge', 1);
            }
        }

        return $orderItem;
    }
}
