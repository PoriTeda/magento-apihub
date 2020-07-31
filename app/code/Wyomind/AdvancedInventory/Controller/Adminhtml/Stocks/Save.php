<?php

/*
 * Copyright Â© 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Stocks;

/**
 * Index action
 */
class Save extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Stocks
{

    public function execute()
    {
        $journal = $this->_journalHelper;
        try {
            $data = json_decode($this->getRequest()->getPost('data'));
            $storeId = $this->getRequest()->getParam('store_id');
            $isAdmin = $this->getRequest()->getParam('is_admin');


            foreach ($data as $productId => $productData) {
                $stock = $this->_stockModel->getStockSettings($productId, false, array_keys((array) $productData->pos_wh));

                // get qty
                if ($productData->multistock) {
                    $qty = 0;
                    $substract = 0;

                    foreach ($productData->pos_wh as $posId => $pos) {
                        if ($storeId || !$isAdmin) {
                            $posQty = "getQuantity" . $posId;
                            $substract+=$stock->$posQty();
                        }

                        $qty += $pos->qty;
                    }
                    if ($storeId || !$isAdmin) {
                        $qty = $stock->getQty() - $substract + $qty;
                    }
                } else {
                    $qty = $productData->qty;
                }



                if ($productData->multistock) {
                    $data = [
                        "id" => $stock->getItemId(),
                        "product_id" => $productId,
                        "multistock_enabled" => true,
                    ];
                    // Insert / update advancedinventory_item
                    $itemId = $stock->getItemId();
                    if ($stock->getMultistockEnabled() != $productData->multistock) {
                        $this->_journalHelper->insertRow($journal::SOURCE_STOCK, $journal::ACTION_MULTISTOCK, "P#".$productId, ["from" => "off", "to" => "on"]);
                        $this->_itemModel->setData($data)->save();
                        $itemId = $this->_itemModel->getId();
                    }

                    foreach ($productData->pos_wh as $posId => $pos) {
                        $stockId = "getStockId" . $posId;
                        $posQty = "getQuantity" . $posId;
                        $data = [
                            "id" => $stock->$stockId(),
                            "item_id" => $itemId,
                            "place_id" => $posId,
                            "product_id" => $productId,
                            "quantity_in_stock" => $pos->qty,
                        ];

                        if (!$stock->$stockId()) {
                            $posDefault = $this->_posModel->getPlace($posId)->getFirstItem();

                            $data['manage_stock'] = $posDefault->getDefaultStockManagement();
                            $data['backorder_allowed'] = $posDefault->getDefaultAllowBackorder();
                            $data['use_config_setting_for_backorders'] = $posDefault->getDefaultUseDefaultSettingForBackorder();
                        }

                        if ($stock->$posQty() != $pos->qty || !$stock->$stockId()) {
                            $this->_stockModel->load($data['id'])->setData($data)->save();
                            $this->_journalHelper->insertRow($journal::SOURCE_STOCK, $journal::ACTION_STOCK_QTY, "P#".$productId.",W#".$posId, ["from" => $stock->$posQty(), "to" => $pos->qty]);
                        }
                    }
                } elseif ($stock->getMultistockEnabled() != $productData->multistock) { // Delete advancedinventory_item entry
                    $this->_journalHelper->insertRow($journal::SOURCE_STOCK, $journal::ACTION_MULTISTOCK, "P#".$productId, ["from" => "on", "to" => "off"]);
                    $this->_itemModel->setId($stock->getItemId())->delete();
                }



                // Update backorders status
                $inventory = $this->_stockRegistry->getStockItem($productId);
                $stock = $this->_stockModel->getStockSettings($productId);
                if ($productData->multistock) {
                    $inventory->setBackorders($stock->getBackorderableAtStockLevel())->setUseConfigBackorders(0);
                } else {
                    $inventory->setBackorders(0)->setUseConfigBackorders(1);
                }
                // Update is in stock status
                if ($productData->multistock) {
                    if ($this->_coreHelper->getStoreConfig("advancedinventory/settings/auto_update_stock_status") || !$isAdmin) {
                        $stockStatus = $stock->getStockStatus();
                    } else {
                        $stockStatus = (int) $productData->is_in_stock;
                    }
                } else {
                    $stockStatus = (int) $productData->is_in_stock;
                }
                if ($this->_coreHelper->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                    if ($stockStatus != (int) $inventory->getIsInStock()) {
                        $status = ($stockStatus) ? "In stock" : "Out of stock";
                        $previousStatus = ($inventory->getIsInStock()) ? "In stock" : "Out of stock";
                        $this->_journalHelper->insertRow($journal::SOURCE_STOCK, $journal::ACTION_IS_IN_STOCK, "P#".$productId, ["from" => $previousStatus, "to" => $status]);
                        $inventory->setIsInStock($stockStatus);
                    }
                } else {
                    if ($stockStatus != (int) $inventory->getIsInStock()) {
                        $inventory->setIsInStock($stockStatus);
                    }
                }

                // Update qty
                if ($inventory->getQty() != $qty) {
                    $this->_journalHelper->insertRow($journal::SOURCE_STOCK, $journal::ACTION_QTY, "P#".$productId, ["from" => $inventory->getQty(), "to" => $qty]);

                    $inventory->setQty($qty);
                }
                //save
                $inventory->save();
            }
            $this->getResponse()->representJson($this->_objectManager->create('Magento\Framework\Json\Helper\Data')->jsonEncode(["error" => false, "message" => "Data saved"]));
        } catch (\Exception $exception) {
            $this->getResponse()->representJson($this->_objectManager->create('Magento\Framework\Json\Helper\Data')->jsonEncode(["error" => false, "message" => $exception->getMessage()]));
        }
    }
}
