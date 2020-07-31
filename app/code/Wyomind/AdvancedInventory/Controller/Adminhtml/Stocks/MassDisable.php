<?php

/*
 * Copyright Â© 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Stocks;

/**
 * Index action
 */
class MassDisable extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Stocks
{

    public function execute()
    {


        $journal = $this->_journalHelper;

        try {
            $productIds = $this->getRequest()->getPost('product_id');

            foreach ($productIds as $productId) {
                $item = $this->_itemModel->loadByProductId($productId);
                if ($item->getMultistockEnabled()) {
                    $item->delete();
                    $stock = $this->_stockModel->getStockSettings($productId);
                    $inventory = $this->_stockRegistry->getStockItem($productId);
                    $inventory
                            ->setBackorders(0)
                            ->setUseConfigBackorders(1);
                    if ($this->_coreHelper->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                        $inventory->setIsInStock($stock->getStockStatus());
                    }
                    $inventory->save();

                    $this->_journalHelper->insertRow($journal::SOURCE_STOCK, $journal::ACTION_MULTISTOCK, "P#$productId", ["from" => "on", "to" => "off"]);
                }
            }
        } catch (\Exception $exception) {
            $this->messageManager->addError($exception->getMessage());
        }
        $this->_redirect('*/*/index');
    }
}
