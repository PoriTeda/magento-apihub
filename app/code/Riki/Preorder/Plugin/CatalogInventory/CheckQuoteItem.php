<?php

namespace Riki\Preorder\Plugin\CatalogInventory;

class CheckQuoteItem
{
    public function aroundCheckQuoteItemQty(
        \Riki\AdvancedInventory\Model\CatalogInventory\StockStateProvider $subject,
        \Closure $proceed,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem,
        $qty,
        $summaryQty,
        $origQty = 0
    ) {
        $productId = $stockItem->getProductId();

        $key = $productId . '-' . $qty;

        $cacheData = $subject->getCheckedData();

        if (!isset($cacheData[$key])) {

            if ($stockItem->getBackorders() == \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION) {
                /*pass function check availability and continue default process*/
                $subject->setCheckedData($key, \Riki\AdvancedInventory\Model\Assignation::STOCK_STATUS_AVAILABLE_BACK_ORDER);
            }
        }

        return $proceed($stockItem, $qty, $summaryQty, $origQty);
    }
}