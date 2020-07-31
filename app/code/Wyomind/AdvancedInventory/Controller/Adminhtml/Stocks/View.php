<?php

/*
 * Copyright Â© 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\Stocks;

/**
 * Index action
 */
class View extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Stocks
{

    public function execute()
    {
        $websites = $this->storeManagerInterface->getWebsites();
        foreach ($websites as $website) {
            $w[$website->getId()] = [];
            foreach ($website->getGroups() as $group) {
                $g[$group->getId()] = [];
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $w[$website->getId()][] = $store->getId();
                    $g[$group->getId()][] = $store->getId();
                }
            }
        }

        $productId = $this->getRequest()->getParam("id");
        $output = "<ul>";
        foreach ($websites as $website) {
            $inventory = $this->_stockModel->getStockByProductIdAndStoreIds($productId, $w[$website->getId()]);

            $output.="<li id='website-" . $website->getId() . "' class='jstree-closed'>" . $website->getName() . " (" . $inventory['qty'] . ")";
            $output.="<ul>";
            foreach ($website->getGroups() as $group) {
                $inventory = $this->_stockModel->getStockByProductIdAndStoreIds($productId, $g[$group->getId()]);

                $output.="<li id='group-" . $group->getId() . "' class='jstree-closed'>" . $group->getName() . " (" . $inventory['qty'] . ")";
                $stores = $group->getStores();
                $output.="<ul>";
                foreach ($stores as $store) {
                    $inventory = $this->_stockModel->getStockByProductIdAndStoreIds($productId, $store->getId());

                    $output.="<li id='store-" . $store->getId() . "'>" . $store->getName() . " (" . $inventory['qty'] . ")";
                    $output.="<ul>";
                    foreach ($this->_posModel->getPlacesByStoreId($store->getId()) as $pos) {
                        $inventory = $this->_stockModel->getStockByProductIdAndPlaceId($productId, $pos->getPlaceId());
                        $output.="<li id='pos-" . $pos->getPlaceId() . "'>" . $pos->getName() . " (" . $inventory['quantity_in_stock'] . ")</li>";
                    }
                    $output.="</ul>";
                    $output.="</li>";
                }
                $output.="</ul>";
                $output.="</li>";
            }
            $output.="</ul>";
            $output.="</li>";
        }
        $output.="</ul>";
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents($output);
    }
}
