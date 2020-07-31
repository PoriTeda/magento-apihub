<?php

namespace Riki\Preorder\Plugin\CatalogInventory\Model\Stock;


class StockItemRepository
{
    /**
     * reset in stock status with pre-order product
     *
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $subject
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
     * @return array
     */
    public function beforeSave(
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $subject,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
    )
    {
        if(
            !$stockItem->getUseConfigBackorders() &&
            $stockItem->getBackorders() == \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION
            && $stockItem->getQty() <= $stockItem->getMinQty()
        )
            $stockItem->setIsInStock(false);

        return [$stockItem];
    }
}