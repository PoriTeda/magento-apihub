<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Observer;

class CatalogProductIsSalableAfter implements \Magento\Framework\Event\ObserverInterface
{

    protected $_coreHelperData = null;
    protected $_modelStock = null;
    protected $_storeManager = null;
    protected $_stockRegistry = null;
    protected $_modelPos = null;

    public function __construct(
        \Wyomind\Core\Helper\Data $coreHelperData,
        \Wyomind\AdvancedInventory\Model\Stock $modelStock,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Wyomind\PointOfSale\Model\PointOfSale $modelPointOfSale
    ) {
    
        $this->_coreHelperData = $coreHelperData;
        $this->_modelStock = $modelStock;
        $this->_storeManager = $storeManager;
        $this->_stockRegistry = $stockRegistry;
        $this->_modelPos = $modelPointOfSale;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $storeId = $this->_storeManager->getStore()->getStoreId();
        $placeIds = $this->_modelPos->getPlacesByStoreId($storeId);
        if ($this->_coreHelperData->getStoreConfig("advancedinventory/settings/enabled")) {
            $rtn = false;
            $product = $observer->getProduct();

            if (in_array($product->getTypeId(), ["downloadable", "virtual"])) {
                return;
            }
            
            if ($product->getDisableAddToCart()) {
                $observer->getSalable()->setIsSalable(false);
                return;
            }
            
            
            if ($product->getStatus() == 2) {
                $observer->getSalable()->setIsSalable(false);
                return;
            }

            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                if (!$this->_stockRegistry->getStockItem($product->getId(), "product_id")->getIsInStock()) {
                    $observer->getSalable()->setIsSalable(false);
                    return;
                }

                $associatedProduct = $product->getTypeInstance()->getUsedProducts($product);
                foreach ($associatedProduct as $child) {
                    $rtn += $this->isAvailable($child, $placeIds);
                    if ($rtn) {
                        break;
                    }
                }
            } else {
                if ($this->_modelStock->isMultiStockEnabledByProductId($product->getId())) {
                    $rtn = $this->isAvailable($product, $placeIds);
                } else {
                    $rtn = null;
                }
            }

            if ($rtn !== null) {
                $observer->getSalable()->setIsSalable($rtn);
            }
        }
    }

    public function isAvailable(
        $product,
        $placeIds
    ) {
    

        foreach ($placeIds->getData() as $pos) {
            $productId = $product->getId();
            $stock = $this->_modelStock->getStockSettings($productId, $pos['place_id']);
            if ($stock->getStockStatus()) {
                return true;
            }
        }
        return false;
    }
}
