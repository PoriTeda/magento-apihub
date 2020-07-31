<?php

namespace Riki\StockPoint\Plugin\Quote\Model\Quote\Item\ToOrderItem;

class CopyStockPointDataToOrderItem
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
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = $proceed($item, $additional);

        $orderItem->setData(
            'stock_point_applied_discount_rate',
            $item->getData('stock_point_applied_discount_rate')
        );

        $orderItem->setData(
            'stock_point_applied_discount_amount',
            $item->getData('stock_point_applied_discount_amount')
        );

        return $orderItem;
    }
}
