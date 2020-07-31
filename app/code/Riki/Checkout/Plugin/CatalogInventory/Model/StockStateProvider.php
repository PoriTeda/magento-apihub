<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Riki\Checkout\Plugin\CatalogInventory\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Math\Division as MathDivision;
use Magento\Framework\DataObject\Factory as ObjectFactory;

/**
 * Interface StockStateProvider
 */
class StockStateProvider
{
    public function aroundCheckQuoteItemQty(
        \Magento\CatalogInventory\Model\StockStateProvider $subject,
        \Closure $proceed,
        $stockItem,$qty, $summaryQty, $origQty = 0
    ) {
        $result = $proceed($stockItem,$qty, $summaryQty, $origQty );
        if($result->getHasError() == true){
            // Check minimum
            if($result->getErrorCode() == 'qty_min'){
                $result->setMessage(__('Please select QTY to be more than %1 for %2', $stockItem->getMinSaleQty() * 1,$stockItem->getProductName()));
                $result->setQuoteMessage(__('Please select QTY to be more than %1 for %2', $stockItem->getMinSaleQty() * 1,$stockItem->getProductName()));
            }
            // Check maximum Qty
            if($result->getErrorCode() == 'qty_max'){
                $result->setMessage(__('The product %1 Maximum order QTY goes here %2',$stockItem->getProductName(), $stockItem->getMaxSaleQty() * 1));
                $result->setQuoteMessage(__('The product %1 Maximum order QTY goes here %2',$stockItem->getProductName(), $stockItem->getMaxSaleQty() * 1));
            }
            // Check out of stock
            if($result->getItemUseOldQty() == true){
                $result->setMessage(__('The product %1 is out of stock.',$stockItem->getProductName()));
                $result->setQuoteMessage(__('The product %1 is out of stock.',$stockItem->getProductName()));
            }
        }
        return $result;
    }


}
