<?php

namespace Riki\AdvancedInventory\Model;

class Stock extends \Wyomind\AdvancedInventory\Model\Stock implements \Riki\AdvancedInventory\Api\Data\StockInterface
{
    const ADVANCED_INVENTORY_MAXIMUM_CART_STOCK = 'advancedinventory_riki_inventory/order_stock/maximum_cart_stock';
    const MORE_THAN_TOTAL_NUMBER_ITEM_ERROR_MESSAGE = 'Please limit the total number of items you order at one time to 99 pieces or less';
    const BACKORDER_UNLIMIT_QTY = 9999999;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * Stock constructor.
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Wyomind\AdvancedInventory\Model\Item $itemModel
     * @param \Wyomind\AdvancedInventory\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory
     * @param \Wyomind\AdvancedInventory\Model\ResourceModel\Stock\CollectionFactory $stockFactory
     * @param \Wyomind\AdvancedInventory\Model\ResourceModel\Item\CollectionFactory $itemFactory
     * @param \Wyomind\AdvancedInventory\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Wyomind\Core\Helper\Data $helperCore
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $abstractResource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $abstactDb
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
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
        $this->functionCache = $functionCache;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        parent::__construct(
            $context,
            $registry,
            $itemModel,
            $pointOfSaleCollectionFactory,
            $posFactory,
            $stockFactory,
            $itemFactory,
            $productCollectionFactory,
            $resource,
            $helperCore,
            $abstractResource,
            $abstactDb,
            $data
        );
    }

    /**
     * @return mixed
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * @param $qty
     * @return $this
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * fixed bug set stock status for back order case
     *
     * @param bool|false $productId
     * @param bool|false $placeId
     * @param array $placeIds
     * @param bool|false $itemId
     * @return mixed
     */
    public function getStockSettings(
        $productId = false,
        $placeId = false,
        $placeIds = [],
        $itemId = false
    ) {

        $cacheKey = [];
        array_push($cacheKey, $productId);

        if ($placeId) {
            array_push($cacheKey, $placeId);
        } else {
            array_push($cacheKey, 0);
        }

        if (!empty($placeIds)) {
            array_push($cacheKey, implode("-", $placeIds));
        } else {
            array_push($cacheKey, 0);
        }

        if (!empty($itemId)) {
            array_push($cacheKey, $itemId);
        } else {
            array_push($cacheKey, 0);
        }

        $cacheTags = ['stock_update_qty_' . $productId];
        $cacheKey['cacheTag'] = $cacheTags;

        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $inventory = $this->_productCollectionFactory->create()
            ->getStockSettings($productId, $placeId, $itemId, $placeIds);

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
                    } else {
                        $checkStock = false;
                        if (!empty($placeIds)) {
                            foreach ($placeIds as $placeIdValue) {
                                if ($inventory->getData('is_in_stock_' . $placeIdValue)) {
                                    $checkStock = true;
                                    $inventory->setStockStatus(true);
                                    break;
                                }
                            }
                        } else {
                            $checkStock = $inventory->getIsInStockAtStockLevel();
                            $inventory->setStockStatus($inventory->getIsInStockAtStockLevel());
                        }

                        if (!$checkStock) {
                            if ($inventory->getBackorderableAtStockLevel()) {
                                // Check back order limit
                                $getDataBackOrder = $this->getStockBackOrderByProductId($productId);
                                $checkStock = false;
                                if (!empty($getDataBackOrder)) {
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
                        }
                    }
                } else {
                    $inventory->setStockStatus($inventory->getIsInStock());
                }
            } else { // si pas de multistock
                if ((float) $inventory->getQuantityInStock() > (float)$inventory->getMinQty()
                    || $inventory->getBackorderableAtProductLevel() && $autoStock
                ) {
                    $inventory->setStockStatus(true);
                } else {
                    $inventory->setStockStatus($inventory->getIsInStock()==1);
                }
            }
        }

        $this->functionCache->store($inventory, $cacheKey);

        return $inventory;
    }

    /**
     * Get stock settings for update
     *
     * @param bool $productId
     * @param bool $placeId
     * @param array $placeIds
     * @param bool $itemId
     *
     * @return \Magento\Framework\DataObject
     */
    public function getStockSettingsForUpdate(
        $productId = false,
        $placeId = false,
        $placeIds = [],
        $itemId = false
    ) {
        // need improve to better
        $this->_registry->unregister('get_stock_settings_for_update');
        $this->_registry->register('get_stock_settings_for_update', 1);

        $this->functionCache->invalidateByCacheTag('stock_update_qty_' . $productId);

        $result = $this->getStockSettings($productId, $placeId, $placeIds, $itemId);

        $this->_registry->unregister('get_stock_settings_for_update');
        return $result;
    }

    /**
     * Get stock settings by place id and store id
     *
     * @param bool $productId
     * @param bool $placeId
     * @param array $placeIds
     * @param bool $itemId
     * @param bool $storeId
     * @return \Magento\Framework\DataObject|mixed
     */
    public function getStockSettingsByPlaceIdAndStoreId(
        $productId = false,
        $placeId = false,
        $placeIds = [],
        $itemId = false,
        $storeId = false
    ) {
        if (!$storeId) {
            return $this->repairStockSettings(
                $this->getStockSettings($productId, $placeId, $placeIds, $itemId)
            );
        }

        /** @var \Wyomind\PointOfSale\Model\PointOfSale $pointOfSales */
        $pointOfSales = $this->_posFactory->create();

        /*place data which is supported for this store*/
        $places = $pointOfSales->getPlacesByStoreId($storeId);

        foreach ($places as $pos) {
            if ($pos->getId() == $placeId) {
                return $this->repairStockSettings(
                    $this->getStockSettings($productId, $placeId, $placeIds, $itemId)
                );
            }
        }

        return new \Magento\Framework\DataObject();
    }

    /**
     * Get stock settings by place ids and store id
     *
     * @param int $productId
     * @param [] $placeIds
     * @param int $storeId
     * @return \Magento\Framework\DataObject|mixed
     */
    public function getStockSettingsByPlaceIdsAndStoreId($productId, $placeIds, $storeId)
    {
        $placeIdsByStoreId = [];

        if (!$storeId) {
            $placeIdsByStoreId = $placeIds;
        } else {

            /** @var \Wyomind\PointOfSale\Model\PointOfSale $pointOfSales */
            $pointOfSales = $this->_posFactory->create();

            /*place data which is supported for this store*/
            $places = $pointOfSales->getPlacesByStoreId($storeId);

            foreach ($places as $pos) {
                if (in_array($pos->getId(), $placeIds)) {
                    array_push($placeIdsByStoreId, $pos->getId());
                }
            }
        }

        if (!empty($placeIdsByStoreId)) {
            return $this->repairStockSettingsByPlaceIds(
                $this->getStockSettings($productId, false, $placeIdsByStoreId, false),
                $placeIdsByStoreId
            );
        }

        return new \Magento\Framework\DataObject();
    }

    /**
     * Get stock setting by store id
     *
     * @param bool $productId
     * @param bool $storeId
     * @return \Magento\Framework\DataObject|mixed
     */
    public function getStockSettingsByStoreId($productId = false, $storeId = false)
    {
        $placesList = [];

        if (!empty($storeId)) {
            /** @var \Wyomind\PointOfSale\Model\PointOfSale $pointOfSales */
            $pointOfSales = $this->_posFactory->create();

            /*place data which is supported for this store*/
            $places = $pointOfSales->getPlacesByStoreId($storeId);

            foreach ($places as $pos) {
                array_push($placesList, $pos->getId());
            }
        }

        if (!empty($placesList)) {
            return $this->getStockSettingsByPlaceIdsAndStoreId($productId, $placesList, false);
        }

        return new \Magento\Framework\DataObject();
    }

    /**
     * Repair stock setting
     *
     * @param $stockSettings
     * @return mixed
     */
    private function repairStockSettings($stockSettings)
    {
        /*warehouse is disabled*/
        if (!$stockSettings->getManagedAtStockLevel()) {
            $stockSettings->setStockStatus(false);
            return $stockSettings;
        }

        return $this->repairQuantityInStock($stockSettings);
    }

    /**
     * Repair quantity in stock for stock settings
     *
     * @param $stockSettings
     * @return mixed
     */
    private function repairQuantityInStock($stockSettings)
    {
        /*pre order product*/
        if ($stockSettings->getBackorderableAtProductLevel()
            == \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION
        ) {
            /*quantity at product level*/
            $quantityInStock = $stockSettings->getQty();
            /*replace quantity instock for case pre-order*/
            $stockSettings->setQuantityInStock($quantityInStock);

            if ($quantityInStock <= 0) {
                $stockSettings->setStockStatus(false);
            }

            return $stockSettings;
        }
        /*back order is allowed for this warehouse*/
        if ($stockSettings->getBackorderableAtStockLevel()) {
            /*back order is not expired at this warehouse*/
            if (!$this->isExpiredDate($stockSettings->getBackorderexpireAtStockLevel())) {
                /*back order limit is 0 - unlimited*/
                if ($stockSettings->getBackorderLimitInStock() == 0) {
                    /*replace back order limit to new value*/
                    $stockSettings->setBackorderLimitInStock(self::BACKORDER_UNLIMIT_QTY);
                }

                $warehouseQty = $stockSettings->getQuantityInStock() + $stockSettings->getBackorderLimitInStock();

                if ($warehouseQty <= 0) {
                    $stockSettings->setStockStatus(false);
                }
            } else {
                $stockSettings->setBackorderableAtStockLevel(false);
            }
        }
        return $stockSettings;
    }

    /**
     * {@inheritdoc}
     *
     * @param $productId
     *
     * @return boolean|int
     */
    public function isMultiStockEnabledByProductId($productId)
    {
        if ($this->functionCache->has($productId)) {
            return $this->functionCache->load($productId);
        }

        $result = parent::isMultiStockEnabledByProductId($productId);

        $this->functionCache->store($result, $productId);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param $productId
     * @param $placeId
     *
     * @return mixed
     */
    public function getStockByProductIdAndPlaceId(
        $productId,
        $placeId
    ) {
        $cacheTags = ['stock_update_qty_' . $productId];
        $cacheKey = [$productId, $placeId, 'cacheTag' => $cacheTags];
        if ($this->functionCache->has($cacheKey)) {
            return $this->functionCache->load($cacheKey);
        }

        $result = parent::getStockByProductIdAndPlaceId($productId, $placeId);

        $this->functionCache->store($result, $cacheKey);

        return $result;
    }

    /**
     * repair stock settings again
     *      some field of stockSettings data are got based on all warehouse
     *      this function will repair data again, to exclude unnecessary warehouse data
     *
     * @param $stockSettings
     * @param $placeIds
     * @return mixed
     */
    private function repairStockSettingsByPlaceIds($stockSettings, $placeIds)
    {
        if (!$stockSettings->getStockStatus()) {
            return $stockSettings;
        }

        if (empty($placeIds)) {
            return $stockSettings;
        }

        /*pre order product*/
        if ($stockSettings->getBackorderableAtProductLevel()
            == \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION
        ) {
            /*quantity at product level*/
            $quantityInStock = $stockSettings->getQty();

            /*for this case - current quantity in stock is 0, need to be set it again by qty at stock level*/
            $stockSettings->setQuantityInStock($quantityInStock);

            /*this flag is used to validate product stock*/
            $stockSettings->setManagedAtStockLevel(true);

            if ($quantityInStock <= 0) {
                $stockSettings->setStockStatus(false);
            }

            return $stockSettings;
        }

        $stockStatus = false;
        $quantityInStock = 0;
        $backorderableAtProductLevel = 0;
        $backorderLimitInStock = 0;
        $backorderableAtStockLevel = 0;

        foreach ($placeIds as $placeId) {
            /**
             * stock management has disabled at this warehouse -
             * or stock data has been not created yet from advancedinventory_stock table
             */
            if (empty($stockSettings->getData('manage_stock_' . $placeId))) {
                continue;
            }

            $stockAtWarehouseLevel = (int) $stockSettings->getData('quantity_' . $placeId);

            /*is in stock at warehouse level*/
            if ($stockSettings->getData('is_in_stock_' . $placeId)) {
                /*the case that product is in stock at any pos*/
                $stockStatus = true;

                $quantityInStock += $stockAtWarehouseLevel;
            }

            /*is allowed back order at warehouse level*/
            if ($stockSettings->getData('backorders_' .$placeId)) {
                if (!$this->isExpiredDate($stockSettings->getData('backorders_expire_'.$placeId))) {
                    $backorderableAtStockLevel = 1;
                    $backorderableAtProductLevel = 1;

                    /*back order limit at warehouse*/
                    $backorderLimitAtWarehouse = (int) $stockSettings->getData('backorders_limit_' .$placeId);

                    /*back order limit is 0 - unlimited*/
                    if ($backorderLimitAtWarehouse == 0) {
                        $backorderLimitAtWarehouse = self::BACKORDER_UNLIMIT_QTY;
                    }

                    /**
                     * for case qty at warehouse is less than 0,
                     * it means qty has been deducted for back order limit
                     * so for this case, limit will be calculate again
                     */
                    if ($stockAtWarehouseLevel < 0) {
                        $backorderLimitAtWarehouse += $stockAtWarehouseLevel;
                    }
                    /**
                     * product - back order limit in stock
                     *      is total of back order limit at all warehouse
                     */
                    $backorderLimitInStock += $backorderLimitAtWarehouse;

                    if ($backorderLimitInStock > self::BACKORDER_UNLIMIT_QTY) {
                        $backorderLimitInStock = self::BACKORDER_UNLIMIT_QTY;
                    }
                }
            }
        }

        if ($backorderableAtProductLevel && $backorderLimitInStock > 0) {
            $stockStatus = true;
        }

        $stockSettings->setData('quantity_in_stock', $quantityInStock);
        $stockSettings->setData('stock_status', $stockStatus);
        $stockSettings->setData('backorderable_at_product_level', $backorderableAtProductLevel);
        $stockSettings->setData('backorder_limit_in_stock', $backorderLimitInStock);
        $stockSettings->setData('backorderable_at_stock_level', $backorderableAtStockLevel);

        return $stockSettings;
    }

    /**
     * check date is expired
     *
     * @param $date
     * @return bool
     */
    public function isExpiredDate($date)
    {
        $today = $this->dateTime->timestamp($this->timezone->date()->format('Y-m-d'));

        $comparedDate = $this->dateTime->timestamp($date);

        if ($today > $comparedDate) {
            return true;
        }

        return false;
    }
}
