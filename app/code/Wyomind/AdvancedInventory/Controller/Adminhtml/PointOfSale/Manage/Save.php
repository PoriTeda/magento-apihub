<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Controller\Adminhtml\PointOfSale\Manage;

class Save
{

    protected $_resource;
    protected $_connection;
    protected $_posModel;
    protected $_messageManager;
    protected $_requestInterface;
    protected $_itemModel;
    protected $_modelStockFactory = null;
    protected $_stockRegistry;
    protected $_coreHelper;
    protected $_helperData;
    protected $_journalHelper;

    public function __construct(
        \Wyomind\PointOfSale\Model\PointOfSale $posModel,
        \Magento\Framework\App\ResourceConnection $resource,
        \Wyomind\AdvancedInventory\Model\Item $itemModel,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Stock\CollectionFactory $stockCollectionFactory,
        \Wyomind\AdvancedInventory\Model\StockFactory $modelStockFactory,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Wyomind\AdvancedInventory\Helper\Journal $journalHelper,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_posModel = $posModel;
        $this->_resource = $resource;
        $this->_messageManager = $messageManager;
        $this->_requestInterface = $requestInterface;
        $this->_itemModel = $itemModel;
        $this->_stockCollectionFactory = $stockCollectionFactory;
        $this->_modelStockFactory = $modelStockFactory;
        $this->_stockRegistry = $stockRegistry;
        $this->_coreHelper = $coreHelper;
        $this->_helperData = $helperData;
        $this->_journalHelper = $journalHelper;
    }

    protected function _getWriteConnection()
    {
        if (null === $this->_connection) {
            $this->_connection = $this->_resource->getConnection('write');
        }
        return $this->_connection;
    }

    public function afterExecute(
        $subject,
        $return
    ) {

        $journal = $this->_journalHelper;
        $placeId = $this->_requestInterface->getPost("place_id");

        if (!$placeId) {
            $placeId = $this->_posModel->getLastInsertedId();
        }
        
        try {
            if ($this->_requestInterface->getPost("posupdate") || ($this->_requestInterface->getPost('manage_inventory_backup') == 0 && $this->_requestInterface->getPost('manage_inventory') == 1)) {
                // update after pos update

                $defaultStockManagement = (int) $this->_requestInterface->getPost('default_stock_management');
                $defaultUseDefaultSettingForBackorder = (int) $this->_requestInterface->getPost('default_use_default_setting_for_backorder');
                $defaultAllowBackorder = (int) $this->_requestInterface->getPost('default_allow_backorder');
                $this->_itemModel->getCollection()->updateAfterPosUpdate($defaultStockManagement, $defaultUseDefaultSettingForBackorder, $defaultAllowBackorder, $placeId);
                
                
                $stocks = $this->_itemModel->getCollection();
                foreach ($stocks as $stock) {
                    $inventory = $this->_modelStockFactory->create()->getStockSettings($stock->getProductId());

                    if ($this->_coreHelper->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                        $catalogInventory = $this->_stockRegistry->getStockItem($stock->getProductId(), "product_id");
                        $catalogInventory->setIsInStock($inventory->getStockStatus());
                        $catalogInventory->setBackorders($inventory->getBackorderableAtStockLevel())->setUseConfigBackorders(0);
                        $catalogInventory->save();
                    }
                }
                $this->_journalHelper->insertRow($journal::SOURCE_POS, $journal::ACTION_MASS_UPDATE, "W#$placeId", ["from" => "Action", "to" => "Mass update pos/wh"]);

                $this->_messageManager->addSuccess(__("Stocks settings have been updated."));
            }

            if (!$this->_requestInterface->getPost('manage_inventory')) {
                $this->_posModel->setId($placeId)->setUseAssignationRules(0)->save();

                $stocks = $this->_stockCollectionFactory->create()->addFieldToFilter('place_id', ["eq" => $placeId]);
                foreach ($stocks as $stock) {
                    $stock->delete();
                    $inventory = $this->_modelStockFactory->create()->getStockSettings($stock->getProductId());

                    if ($this->_coreHelper->getStoreConfig("advancedinventory/settings/auto_update_stock_status")) {
                        $catalogInventory = $this->_stockRegistry->getStockItem($stock->getProductId(), "product_id");
                        $catalogInventory->setIsInStock($inventory->getStockStatus());
                        $catalogInventory->setQty($inventory->getQuantityInStock());
                        $catalogInventory->setBackorders($inventory->getBackorderableAtStockLevel())->setUseConfigBackorders(0);
                        $catalogInventory->save();
                    }
                }
                $this->_journalHelper->insertRow($journal::SOURCE_POS, $journal::ACTION_MASS_UPDATE, "W#$placeId", ["from" => "Action", "to" => "Mass disable stock management"]);


                $this->_messageManager->addSuccess(__('Inventory management disabled'));
            }

            if ($this->_modelStockFactory->create()->reindex()) {
                $this->_messageManager->addSuccess(__('Index updated `advancedinventory_index`'));
            }
        } catch (\Exception $e) {
            $this->_messageManager->addError(__('Error while updating data') . '<br/><br/>' . $e->getMessage());
        }
        return $return;
    }
}
