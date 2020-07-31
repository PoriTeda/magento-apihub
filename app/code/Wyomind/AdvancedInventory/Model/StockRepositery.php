<?php

/**
 * Copyright Â© 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model;

class StockRepositery implements \Wyomind\AdvancedInventory\Api\StockRepositeryInterface
{

    protected $_modelStock;
    protected $_jsonHelperData;
    protected $_modelPos;
    protected $_modelItem = null;
    protected $_helperData = null;
    protected $_journalHelper = null;
    protected $_stockRegistry = null;
    protected $_coreHelper = null;

    public function __construct(
        \Wyomind\AdvancedInventory\Model\Stock $modelStock,
        \Wyomind\PointOfSale\Model\PointOfSale $modelPos,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Wyomind\AdvancedInventory\Model\Item $modelItem,
        \Magento\Framework\Json\Helper\Data $jsonHelperData,
        \Wyomind\AdvancedInventory\Helper\Journal $journalHelper,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry
    ) {
        $this->_modelStock = $modelStock;
        $this->_jsonHelperData = $jsonHelperData;
        $this->_modelPos = $modelPos;
        $this->_modelItem = $modelItem;
        $this->_helperData = $helperData;
        $this->_journalHelper = $journalHelper;
        $this->_stockRegistry = $stockRegistry;
        $this->_coreHelper = $coreHelper;
    }

    public function getAllPointOfSaleAndWarehouse()
    {
        $data = $this->_modelPos->getPlaces()->getData();
        return $this->_jsonHelperData->jsonEncode($data);
    }

    public function getPointOfSaleAndWarehouseByStoreId($storeId)
    {
        $data = $this->_modelPos->getPlacesByStoreId($storeId)->getData();
        return $this->_jsonHelperData->jsonEncode($data);
    }

    public function getStockByProductId($productId)
    {

        $data = $this->_modelStock->getStockSettings($productId)->getData();
        return $this->_jsonHelperData->jsonEncode($data);
    }

    public function getStockByProductIdAndPlaceIds(
        $productId,
        $placeIds
    ) {
        $return = $this->_modelStock->getStockSettings($productId, null, $placeIds)->getData();
        return $this->_jsonHelperData->jsonEncode($return);
    }

    public function getStockByProductIdAndStoreIds(
        $productId,
        $storeIds
    ) {

        $return = $this->_modelStock->getStockByProductIdAndStoreIds($productId, $storeIds)->getData();
        return $this->_jsonHelperData->jsonEncode($return);
    }

    public function updateStock(
        $productId,
        $multistockEnabled = 1,
        $placeId = false,
        $manageStock = true,
        $quantityInStock = 0,
        $backorderAllowed = 0,
        $useConfigSettingForBackorders = 1
    ) {


        $journal = $this->_journalHelper;
        $item = $this->_modelItem->loadByProductId($productId);

        $data = [
            "id" => $item->getId(),
            "product_id" => $productId,
            "multistock_enabled" => $multistockEnabled,
        ];

        // Insert / update advancedinventory_item
        $itemId = $item->getId();
        if ($item->getMultistockEnabled() != $multistockEnabled) {
            $from = ($item->getMultistockEnabled()) ? "on" : "off";
            $to = ($multistockEnabled) ? "on" : "off";
            $this->_journalHelper->insertRow($journal::SOURCE_API, $journal::ACTION_MULTISTOCK, "P#$productId", ["from" => "$from", "to" => "$to"]);
            if ($multistockEnabled) {
                $this->_modelItem->setData($data)->save();
                $itemId = $this->_modelItem->getId();
            } else {
                $this->_modelItem->setData($data)->delete();
                return true;
            }
        }

        if ($multistockEnabled) {
            $stock = $this->_modelStock->getStockByProductIdAndPlaceId($productId, $placeId);

            $data = [
                "id" => $stock->getId(),
                "item_id" => $itemId,
                "place_id" => $placeId,
                "product_id" => $productId,
                "quantity_in_stock" => $quantityInStock,
                "manage_stock" => $manageStock,
                "backorder_allowed" => $backorderAllowed,
                "use_config_setting_for_backorders" => $useConfigSettingForBackorders
            ];

            $updated = false;

            if ($stock->getQuantityInStock() != $quantityInStock) {
                $updated = true;
                $this->_journalHelper->insertRow($journal::SOURCE_API, $journal::ACTION_STOCK_QTY, "P#$productId,W#$placeId", ["from" => $stock->getQuantityInStock(), "to" => $quantityInStock]);
            }
            if ($stock->getManageStock() != $manageStock) {
                $updated = true;
                $this->_journalHelper->insertRow($journal::SOURCE_API, $journal::ACTION_STOCK_MANAGE_QTY, "P#$productId,W#$placeId", ["from" => $stock->getManageStock(), "to" => $manageStock]);
            }
            if ($stock->getBackorderAllowed() != $backorderAllowed) {
                $updated = true;
                $this->_journalHelper->insertRow($journal::SOURCE_API, $journal::ACTION_STOCK_BACKORDERS, "P#$productId,W#$placeId", ["from" => $stock->getBackorderAllowed(), "to" => $backorderAllowed]);
            }
            if ($stock->getUseConfigSettingForBackorders() != $useConfigSettingForBackorders) {
                $updated = true;
                $this->_journalHelper->insertRow($journal::SOURCE_API, $journal::ACTION_STOCK_USE_CONFIG_BACKORDERS, "P#$productId,W#$placeId", ["from" => $stock->getUseConfigSettingForBackorders(), "to" => $useConfigSettingForBackorders]);
            }
            if ($updated) {
                $this->_modelStock->load($data['id'])->setData($data)->save();
            }
        }
        return true;
    }

    public function updateInventory(
        $productId,
        $stockStatus = true
    ) {
        $journal = $this->_journalHelper;
        $inventory = $this->_stockRegistry->getStockItem($productId);
        $stock = $this->_modelStock->getStockSettings($productId);

        $data = [];
        if ($stock->getMultistockEnabled()) {
            $inventory->setUseConfigBackorders(false);
            $inventory->setBackorders($stock->getBackorderableAtStockLevel());
            // Update qty
            if ($inventory->getQty() != $stock->getQuantityInStock()) {
                $this->_journalHelper->insertRow($journal::SOURCE_API, $journal::ACTION_QTY, "P#$productId", ["from" => $inventory->getQty(), "to" => $stock->getQuantityInStock()]);
                $inventory->setQty($stock->getQuantityInStock());
            }
            // Update is in stock status
            if ($this->_coreHelper->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                $stockStatus = $stock->getStockStatus();
                if ($stockStatus != $inventory->getIsInStock()) {
                    $to = ($stockStatus) ? "In stock" : "Out of stock";
                    $from = ($inventory->getIsInStock()) ? "In stock" : "Out of stock";
                    $this->_journalHelper->insertRow($journal::SOURCE_API, $journal::ACTION_IS_IN_STOCK, "P#$productId", ["from" => $from, "to" => $to]);
                    $inventory->setIsInStock($stockStatus);
                }
            } else {
                $inventory->setIsInStock($stockStatus);
            }
        } else {
            $inventory->setUseConfigBackorders(true);
            $inventory->setBackorders(false);
            $inventory->setIsInStock($stockStatus);
        }

        $inventory->save();
        return true;
    }
}
