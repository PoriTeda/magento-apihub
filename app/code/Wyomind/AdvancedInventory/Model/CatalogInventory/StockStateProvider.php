<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model\CatalogInventory;

class StockStateProvider
{

    protected $_modelStock = null;
    protected $_posFactory = null;
    protected $_storeManager = null;

    public function __construct(
        \Wyomind\AdvancedInventory\Model\Stock $modelStock,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory
    ) {
        $this->_modelStock = $modelStock;
        $this->_posFactory = $posFactory;
        $this->_storeManager = $storeManager;
    }

    public function beforeCheckQuoteItemQty(
        $subsject,
        $item
    ) {
        
        if (!$this->_modelStock->isMultiStockEnabledByProductId($item->getProductId())) {
            return;
        }

        $storeId = $this->_storeManager->getStore()->getStoreId();
        $places = $this->_posFactory->create()->getPlacesByStoreId($storeId);
        $placeIds = [];
        foreach ($places as $place) {
            $placeIds[] = $place->getPlaceId();
        }
        $inventory = $this->_modelStock->getStockSettings($item->getProductId(), false, $placeIds);

        $qty = 0;
        $item->setBackorders($inventory->getBackorderableAtStockLevel());
        foreach ($places as $place) {
            $qtyInStock = 'quantity_' . $place->getPlaceId() . "";
            $qty += $inventory[$qtyInStock];
        }

        
        $item->setQty($qty);
    }
}
