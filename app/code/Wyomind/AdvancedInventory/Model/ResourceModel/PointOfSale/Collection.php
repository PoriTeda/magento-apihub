<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model\ResourceModel\PointOfSale;

class Collection extends \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection
{
    
    public function getStockByProductIdAndStoreId($productId, $storeIds)
    {
        
        $connection = $this->_resource;
        
        $advancedinventoryStock = $connection->getTable('advancedinventory_stock');
        $advancedinventoryItem = $connection->getTable('advancedinventory_item');
        
        $this->getSelect()
                ->joinLeft(["lsp" => $advancedinventoryItem], "lsp.product_id = $productId")
                ->joinLeft(
                    [
                    "stocks" => $advancedinventoryStock],
                    "stocks.place_id = main_table.place_id AND stocks.product_id='$productId'",
                    [
                    "qty" => "SUM(stocks.quantity_in_stock )"
                    ]
                );
        if (!is_array($storeIds)) {
            $this->getSelect()
                    ->where("FIND_IN_SET(" . $storeIds . ",main_table.store_id) ")->group('product_id');
        } else {
            $where = [];
            foreach ($storeIds as $storeId) {
                $where[] = "FIND_IN_SET(" . $storeId . ",main_table.store_id) ";
            }
            if (!empty($where)) {
                $this->getSelect()->where(implode(' OR ', $where));
            }
            $this->getSelect()->group('product_id');
        }
        $this->getSelect()->limit(1);

        return $this->getFirstItem();
    }
}
