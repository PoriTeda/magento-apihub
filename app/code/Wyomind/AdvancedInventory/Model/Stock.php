<?php

/*
 * Copyright Â© 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model;

class Stock extends \Magento\Framework\Model\AbstractModel
{

    protected $_stockRegistry = [];
    protected $_stockRegistryEnabled = true;
    protected $_connection = null;
    protected $_itemModel = null;
    protected $_posFactory = null;
    protected $_stockFactory = null;
    protected $_itemFactory = null;
    protected $_productCollectionFactory = null;
    protected $_resourceConnection = null;
    protected $_pointOfSaleCollectionFactory = null;
    protected $_helperCore = null;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Wyomind\AdvancedInventory\Model\Item $itemModel,
        \Wyomind\AdvancedInventory\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Stock\CollectionFactory $stockFactory,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Item\CollectionFactory $itemFactory,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Wyomind\Core\Helper\Data $helperCore,
        \Magento\Framework\Model\ResourceModel\AbstractResource $abstractResource = null,
        \Magento\Framework\Data\Collection\AbstractDb $abstactDb = null,
        array $data = []
    ) {
        $this->_helperCore = $helperCore;
        $this->_resourceConnection = $resource;
        $this->_itemModel = $itemModel;
        $this->_pointOfSaleCollectionFactory = $pointOfSaleCollectionFactory;
        $this->_posFactory = $posFactory;
        $this->_stockFactory = $stockFactory;
        $this->_itemFactory = $itemFactory;
        $this->_productCollectionFactory = $productCollectionFactory;

        parent::__construct($context, $registry, $abstractResource, $abstactDb, $data);
    }

    public function _construct()
    {

        $this->_init('Wyomind\AdvancedInventory\Model\ResourceModel\Stock');
    }

    protected function _getWriteConnection()
    {
        if (null === $this->_connection) {
            $this->_connection = $this->getResource()->getConnection('write');
        }
        return $this->_connection;
    }

    public function getStockByProductIdAndStoreIds(
        $productId,
        $storeIds
    ) {
        $result = $this->_pointOfSaleCollectionFactory->create()->getStockByProductIdAndStoreId($productId, $storeIds);
        return $result;
    }

    public function getStockByProductIdAndPlaceId(
        $productId,
        $placeId
    ) {

        $collection = $this->_stockFactory->create()
                ->addFieldToFilter('main_table.product_id', ['eq' => $productId])
                ->addFieldToFilter('place_id', ['eq' => $placeId]);

        return $collection->getFirstItem();
    }

    public function getStockBackOrderByProductId($productId)
    {
        $stockCollection = $this->getCollection()
            ->addFieldToFilter('product_id', $productId)
            ->addFieldToFilter('backorder_allowed', 1);

        return $stockCollection->getData();
    }

    public function isMultiStockEnabledByProductId($productId)
    {
        $collection = $this->_itemFactory->create()
                ->addFieldToFilter('product_id', ['eq' => $productId]);

        return $collection->getFirstItem()->getMultistockEnabled();
    }

    public function reindex()
    {
        $advancedinventoryStock = $this->_resourceConnection->getTableName("advancedinventory_stock");
        $pointofsale = $this->_resourceConnection->getTableName("pointofsale");
        $advancedinventoryItem = $this->_resourceConnection->getTableName("advancedinventory_item");

        $fields = [];
        $sql = "CREATE OR REPLACE VIEW advancedinventory_index AS ( SELECT product_id,";
        $pos = $this->_posFactory->create()->getCollection();
        foreach ($pos as $p) {
            $fields[] = "(SELECT quantity_in_stock FROM $advancedinventoryStock WHERE place_id=" . $p->getPlaceId() . " AND item_id=$advancedinventoryItem.id) AS quantity_" . $p->getPlaceId() . "";
            $fields[] = "(SELECT manage_stock FROM $advancedinventoryStock WHERE place_id=" . $p->getPlaceId() . " AND item_id=$advancedinventoryItem.id) AS manage_stock_" . $p->getPlaceId() . "";
            $fields[] = "(SELECT backorder_allowed FROM $advancedinventoryStock WHERE place_id=" . $p->getPlaceId() . " AND item_id=$advancedinventoryItem.id) AS backorder_allowed_" . $p->getPlaceId() . "";
            $fields[] = "(SELECT use_config_setting_for_backorders FROM $advancedinventoryStock WHERE place_id=" . $p->getPlaceId() . " AND item_id=$advancedinventoryItem.id) AS use_config_setting_for_backorders_" . $p->getPlaceId() . "";
            $fields[] = "(SELECT id FROM $advancedinventoryStock WHERE place_id=" . $p->getPlaceId() . " AND item_id=$advancedinventoryItem.id) AS stock_id_" . $p->getPlaceId() . "";

            $fields[] = "(SELECT default_stock_management FROM $pointofsale WHERE place_id=" . $p->getPlaceId() . " ) AS default_stock_management_" . $p->getPlaceId() . "";
            $fields[] = "(SELECT default_use_default_setting_for_backorder FROM $pointofsale WHERE place_id=" . $p->getPlaceId() . " ) AS default_use_default_setting_for_backorder_" . $p->getPlaceId() . "";
            $fields[] = "(SELECT default_allow_backorder FROM $pointofsale WHERE place_id=" . $p->getPlaceId() . " ) AS default_allow_backorder_" . $p->getPlaceId() . "";
        }
        $sql.=implode(",", $fields);
        $sql.=" FROM $advancedinventoryItem GROUP BY id )";


        if (count($pos) && $this->_getWriteConnection()->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    public function getStockSettings(
        $productId = false,
        $placeId = false,
        $placeIds = [],
        $itemId = false
    ) {
        if ($placeId) {
            $ids = $placeId;
        } elseif (count($placeIds)) {
            $ids = implode("-", $placeIds);
        } else {
            $ids = 0;
        }
        if (!isset($this->_stockRegistry[$productId][$ids]) || !$this->_stockRegistryEnabled) {
            $inventory = $this->_productCollectionFactory->create()->getStockSettings($productId, $placeId, $itemId, $placeIds);

            if (!$inventory->getMultistockEnabled() && $placeId) {
                $inventory->setBackorderableAtStockLevel($inventory->getDefaultBackorderableAtStockLevel());
                $inventory->setManagedAtStockLevel($inventory->getDefaultManagedAtStockLevel());
            }
            $autoStock = ($this->_helperCore->getStoreConfig("advancedinventory/settings/auto_update_stock_status"));

            // si pas de qty gérer
            if (!$inventory->getManagedAtProductLevel() && !$inventory->getManagedAtStockLevel()) {
                $inventory->setStockStatus(true);
            } else { // si qy gérée
                // si multistock géré
                if ($inventory->getMultistockEnabled()) {
                    if ($autoStock) {
                        if ($inventory->getQuantityInStock() > $inventory->getMinQty()) {
                            $inventory->setStockStatus(true);
                        } elseif ($inventory->getBackorderableAtStockLevel()) {
                            // Check back order limit
                            $getDataBackOrder = $this->getStockBackOrderByProductId($productId);
                            $checkStock = false;
                            if (count($getDataBackOrder) > 0) {
                                foreach ($getDataBackOrder as $key => $item) {
                                    $backOrderLimitAtWareHouse = (int)$item['backorder_limit'];
                                    $qtyBackOrderAtWareHouse = (int)$item['quantity_in_stock'];
                                    $checkStockAtWareHouse = $backOrderLimitAtWareHouse + $qtyBackOrderAtWareHouse;
                                    if ($backOrderLimitAtWareHouse == 0 || $checkStockAtWareHouse > 0) {
                                        $checkStock = true;
                                        break;
                                    } if ($backOrderLimitAtWareHouse != 0 && $checkStockAtWareHouse <= 0) {
                                        $checkStock = false;
                                    }
                                }
                            }
                            $inventory->setStockStatus($checkStock);
                        } else {
                            $inventory->setStockStatus(false);
                        }
                    } else {
                        $inventory->setStockStatus($inventory->getIsInStock());
                    }
                } else { // si pas de multistock
                    if ((float) $inventory->getQuantityInStock() > (float)$inventory->getMinQty() || $inventory->getBackorderableAtProductLevel() && $autoStock) {
                        $inventory->setStockStatus(true);
                    } else {
                        $inventory->setStockStatus($inventory->getIsInStock()==1);
                    }
                }
            }
            $this->_stockRegistry[$productId][$ids] = $inventory;
        }
        return $this->_stockRegistry[$productId][$ids];
    }
}
