<?php

/*
 * Copyright Â© 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Stocks;

/**
 * Index action
 */
class MassEnable extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Stocks
{

    public function execute()
    {
        $journal = $this->_journalHelper;

        try {
            $productIds = $this->getRequest()->getPost('product_id');
            
            $pointofsale = $this->_posModel->getPlaces();
            foreach ($productIds as $productId) {
                if (!$this->_helperData->isProductAllowed($productId)) {
                    continue;
                }
                $item = $this->_itemModel->loadByProductId($productId);
              
                if (!$item->getMultistockEnabled()) {
                    $data = [
                        "id" => null,
                        "product_id" => $productId,
                        "multistock_enabled" => true,
                    ];
                    $this->_journalHelper->insertRow($journal::SOURCE_STOCK, $journal::ACTION_MULTISTOCK, "P#$productId", ["from" => "off", "to" => "on"]);

                    $item->setData($data)->save();

                    $inventory = $this->_stockRegistry->getStockItem($productId);


                    $qty = $inventory->getQty();

                    foreach ($pointofsale as $pos) {
                        $posId = $pos->getId();
                        $stock = clone $this->_stockModel;

                        $data = [
                            "id" => null,
                            "item_id" => $item->getId(),
                            "place_id" => $posId,
                            "product_id" => $productId,
                            "quantity_in_stock" => $qty,
                        ];



                        $data['manage_stock'] = $pos->getDefaultStockManagement();
                        $data['backorder_allowed'] = $pos->getDefaultAllowBackorder();
                        $data['use_config_setting_for_backorders'] = $pos->getDefaultUseDefaultSettingForBackorder();

                        $stock->setData($data)->save();
                        $this->_journalHelper->insertRow($journal::SOURCE_STOCK, $journal::ACTION_STOCK_QTY, "P#$productId", ["from" => 0, "to" => $qty]);
                        $qty = 0;
                    }
                    if ($this->_coreHelper->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                        $stock = $this->_stockModel->getStockSettings($productId);
                        $inventory->setIsInStock($stock->getIsInStock());
                    }

                    $inventory->setBackorders($inventory->getBackorderableAtStockLevel())->setUseConfigBackorders(0)->save();
                }
            }
        } catch (\Exception $exception) {
            $this->messageManager->addError($exception->getMessage());
        }
        $this->_redirect('*/*/index');
    }
}
