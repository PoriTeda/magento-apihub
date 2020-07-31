<?php

namespace Riki\AdvancedInventory\Plugin\CatalogInventory\Model\Stock;

class ForceWebsiteForStockItem
{
    /**
     * Force website for table cataloginventory_stock_item
     *
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $subject
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
     * @return array
     */
    public function beforeSave(
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $subject,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
    ) {
        $stockItem->setWebsiteId(0);
        return [$stockItem];
    }
}
