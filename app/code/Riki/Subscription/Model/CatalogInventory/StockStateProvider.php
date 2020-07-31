<?php

namespace Riki\Subscription\Model\CatalogInventory;
class StockStateProvider extends \Wyomind\AdvancedInventory\Model\CatalogInventory\StockStateProvider
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    public function __construct(
        \Wyomind\AdvancedInventory\Model\Stock $modelStock,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory,
        \Magento\Framework\Registry $registry
    )
    {
        $this->_registry = $registry;
        parent::__construct($modelStock, $storeManager, $posFactory);
    }

    public function beforeCheckQuoteItemQty(
        $subsject,
        $item
    ) {
        if (!$this->_modelStock->isMultiStockEnabledByProductId($item->getProductId())) {
            return;
        }
        if($this->_registry->registry('cron_store_id')){
            $storeId =  $this->_registry->registry('cron_store_id');
        }else {
            $storeId = $this->_storeManager->getStore()->getStoreId();
        }
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
            $qty += $inventory->getData($qtyInStock);
        }


        $item->setQty($qty);
    }

}