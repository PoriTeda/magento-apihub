<?php

namespace Riki\Preorder\Model\Stock;

class StockStateProvider extends \Magento\CatalogInventory\Model\StockStateProvider
{
    const BACKORDERS_PREORDER = 101;

    public function verifyStock(\Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem)
    {
        if ($stockItem->getQty() === null && $stockItem->getManageStock()) {
            return false;
        }

        if ( $stockItem->getBackorders() == \Magento\CatalogInventory\Model\Stock::BACKORDERS_NO
            || $stockItem->getBackorders() == self::BACKORDERS_PREORDER
        ) {
            if( $stockItem->getQty() <= $stockItem->getMinQty() )
            {
                return false;
            }
        }
        return true;
    }
}
