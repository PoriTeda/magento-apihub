<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Riki_Stock
 * @package  Riki\ProductStockStatus\Helper
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
namespace Riki\ProductStockStatus\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\TestFramework\Event\Magento;
use Riki\ProductStockStatus\Model\StockStatusFactory;
use Magento\CatalogInventory\Model\StockStateFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Class StockData
 *
 * @category Riki_Stock
 * @package  Riki\ProductStockStatus\Helper
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://shop.nestle.jp
 */
class StockData extends AbstractHelper
{
    const ENV_FO = 'FO';
    const ENV_BO = 'BO';
    protected $searchBuilder;
    /**
     * @var array $stockStatus
     */
    protected $stockStatus = [];

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Wyomind\Core\Helper\Data
     */
    protected $helperCore;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;
    /**
     * @var StockStatusFactory
     */
    protected $stockStatusModel;
    /**
     * @var StockStateFactory
     */
    protected $stockState;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var ProductRepository
     */
    protected $productRepository;
    /**
     * @var \Riki\AdvancedInventory\Model\StockFactory
     */
    protected $stockFactory;
    /**
     * @var \Riki\AdvancedInventory\Helper\Assignation
     */
    protected $assignationHelper;

    /* default warehouse for FO */
    protected $defaultFoPos;

    protected $stocksSettingData = [];

    /**
     * StockData constructor.
     * @param \Riki\AdvancedInventory\Model\StockFactory $stockFactory
     * @param \Magento\Framework\App\Helper\Context $context
     * @param StockStatusFactory $stockStatusFactory
     * @param StockStateFactory $stockState
     * @param \Magento\Framework\Registry $registry
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Wyomind\Core\Helper\Data $helperCore
     * @param \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
     */
    public function __construct(
        \Riki\AdvancedInventory\Model\StockFactory $stockFactory,
        \Magento\Framework\App\Helper\Context $context,
        StockStatusFactory $stockStatusFactory,
        StockStateFactory $stockState,
        \Magento\Framework\Registry $registry,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Wyomind\Core\Helper\Data $helperCore,
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
    ) {
        parent::__construct($context);

        $this->stockStatusModel = $stockStatusFactory;
        $this->stockState = $stockState;
        $this->registry = $registry;
        $this->productRepository = $productRepository;
        $this->searchBuilder = $searchCriteriaBuilder;
        $this->stockFactory = $stockFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resource = $resource;
        $this->helperCore = $helperCore;
        $this->assignationHelper = $assignationHelper;
    }

    /**
     * generate stock status
     */
    public function generateStockStatus()
    {
        if (empty($this->stockStatus)) {
            $statusItems = $this->stockStatusModel->create()->getCollection()->getItems();

            foreach ($statusItems as $item) {
                $this->stockStatus[$item->getData('status_id')]= $item->getData();
            }
        }
    }

    /**
     * get default pos for FO
     *
     * @return int|mixed
     */
    public function getDefaultFoPos()
    {
        if (!$this->defaultFoPos) {
            $this->defaultFoPos = $this->assignationHelper->getDefaultPosForFo();
        }
        return $this->defaultFoPos;
    }

    /**
     * Get Stock Message.
     *
     * @param \Magento\Catalog\Model\Product $product Product
     * @return array
     */
    public function getStockStatusMessage(
        \Magento\Catalog\Model\Product $product
    ) {
        $this->generateStockStatus();

        $typeId = $product->getTypeId();

        /*current store id*/
        $currentStore = $product->getStoreId();
        
        if ($typeId =="simple") {
            $statusId = $product->getData('stock_display_type');

            if ($statusId && isset($this->stockStatus[$statusId])) {
                /* get stock setting for product */
                $stockSetting = $this->getProductStockSettingsByPlaceIdsAndStoreId(
                    $product->getId(),
                    $this->getDefaultFoPos(),
                    $currentStore
                );

                $stockModel = $this->stockStatus[$statusId];

                $threshold = (int) $stockModel['threshold'];

                $qty = (int) $stockSetting->getQuantityInStock();

                /*product is allowed back order*/
                if ($stockSetting->getBackorderableAtProductLevel()) {
                    $qty += (int) $stockSetting->getBackorderLimitInStock();
                }

                return $this->getMessage($qty, $product->getData('min_qty'), $threshold, $stockModel);
            }
        } else {
            return $this->getStockStatusForBundleProduct($product, $currentStore);
        }
        return [];
    }

    /**
     * Get Outstock Message.
     *
     * @return string
     */
    public function getOutstockMessage()
    {
        $product = $this->registry->registry('current_product');
        if ($product && $product->getId()) {
            $statusId = $product->getData('stock_display_type');
        } else {
            $statusId = 0;
        }
        if ($statusId) {
            $stockModel = $this->stockStatusModel->create()->load($statusId);

            return $stockModel->getData('outstock_message');
        }
    }

    /**
     * Get current product
     *
     * @return bool|mixed
     */
    public function getProduct()
    {
        $product = $this->registry->registry('current_product');
        if ($product && $product->getId()) {
            return $product;
        }
        return false;
    }

    public function getProductUnitDisplay($product)
    {
        if ($product->getCaseDisplay() == 1) {
            return ['ea' => __('EA')];
        } elseif ($product->getCaseDisplay() == 2) {
            return ['cs' => __('CS').'('.$product->getUnitQty().' '.__('EA').')'];
        } elseif ($product->getCaseDisplay() == 3) {
            return ['ea' => __('EA'),'cs' => __('CS').'('.$product->getUnitQty().' '.__('EA').')'];
        } else {
            return ['ea' => __('EA')];
        }
    }

    /**
     * @param $product
     * @return string
     */
    public function getOutStockMessageByProduct($product)
    {
        $this->generateStockStatus();

        if ($product && $product->getId()) {
            $statusId = $product->getData('stock_display_type');
        } else {
            $statusId = 0;
        }
        if ($statusId && isset($this->stockStatus[$statusId])) {
            $stockModel = $this->stockStatus[$statusId];

            return $stockModel['outstock_message'];
        }
        return '';
    }

    public function getStockStatusByEnv(
        \Magento\Catalog\Model\Product $product,
        $env = self::ENV_FO
    ) {
        if ($env == self::ENV_FO) {
            $stockSettings = $this->getProductStockSettingsByPlaceIdsAndStoreId(
                $product->getId(),
                $this->getDefaultFoPos(),
                $product->getStoreId()
            );
        } else {
            $stockSettings = $this->getProductStockSettingsByStoreId(
                $product->getId(),
                $product->getStoreId()
            );
        }

        return $this->getStockStatus($product, $stockSettings);
    }

    /**
     * Get Stock Status.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param $stockSettings
     * @return \Magento\Framework\Phrase
     */
    public function getStockStatus(\Magento\Catalog\Model\Product $product, $stockSettings)
    {
        $this->generateStockStatus();

        $statusId = $product->getData('stock_display_type');

        if ($statusId && isset($this->stockStatus[$statusId])) {
            $stockModel = $this->stockStatus[$statusId];

            $threshold = (int)$stockModel['threshold'];

            $qty = (int)$stockSettings->getQuantityInStock();

            if ($qty <= 0) {
                return __('Out of stock');
            }

            if ($qty <= $threshold) {
                return __('Low stock');
            }
        }

        return __('In stock');
    }

    /**
     * @param null $idsProductToFilter
     * @return $this|bool
     */
    public function getCategoryProducts($idsProductToFilter = null)
    {
        try {
            $productCollections = $this->productCollectionFactory->create()->addAttributeToSelect('*');

            if ($idsProductToFilter) {
                $productCollections->addFieldToFilter("entity_id", ['in' => $idsProductToFilter]);
            }

            $connection = $this->resource->getConnection();
            $advancedInventoryStock = $connection->getTableName("advancedinventory_stock");
            $advancedInventoryItem = $connection->getTableName("advancedinventory_item");
            $cataloginventoryStockItem = $connection->getTableName("cataloginventory_stock_item");
            $minQty = $this->helperCore->getStoreConfig("cataloginventory/item_options/min_qty");
            $backOrders = $this->helperCore->getStoreConfig("cataloginventory/item_options/backorders");

            $productCollections->getSelect()
                ->joinLeft(
                    ["cataloginventory_stock_item" => $cataloginventoryStockItem],
                    'e.entity_id=cataloginventory_stock_item.product_id',
                    [
                        "is_in_stock" => "is_in_stock",
                        "qty" => "qty",
                        "is_qty_decimal" => "is_qty_decimal",
                    ],
                    null,
                    'left'
                )
                ->joinLeft(
                    ['advancedinventory_item' => $advancedInventoryItem],
                    'e.entity_id=advancedinventory_item.product_id',
                    [
                        "item_id" => "id",
                        "multistock_enabled" => "multistock_enabled",
                    ],
                    null,
                    'left'
                );

            $productCollections->getSelect()->joinLeft(
                ['advancedinventory_stock' => $advancedInventoryStock],
                'e.entity_id=advancedinventory_stock.product_id ',
                [
                    "quantity_in_stock" => new \Zend_Db_Expr(
                        "SUM(
                            IF(advancedinventory_stock.manage_stock=1 
                                AND advancedinventory_stock.backorder_allowed=1,
                                advancedinventory_stock.backorder_limit,
                                0
                            )
                        )"
                    ),
                    "backorder_limit_in_stock" => new \Zend_Db_Expr(
                        "SUM(
                            IF(advancedinventory_stock.manage_stock=1 
                                AND advancedinventory_stock.backorder_allowed=1,
                                advancedinventory_stock.backorder_limit,
                                0
                            )
                        )"
                    ),
                    "backorder_delivery_date_allowed_in_stock" => new \Zend_Db_Expr(
                        "SUM(
                            IF( advancedinventory_stock.manage_stock=1 
                                AND advancedinventory_stock.backorder_allowed=1 
                                AND advancedinventory_stock.place_id=1, 
                                advancedinventory_stock.backorder_delivery_date_allowed, 
                                NULL
                            )
                        )"
                    )
                ],
                null,
                'left'
            );
            $productCollections->getSelect()->columns(
                [
                    'backorderable_at_product_level' => new \Zend_Db_Expr(
                        "IF(use_config_backorders=1," . $backOrders . ",backorders)"
                    ),
                    'is_limit_back_order' =>  new \Zend_Db_Expr(
                        "MIN(IF(advancedinventory_stock.manage_stock=0,1,IF(
                                    advancedinventory_stock.use_config_setting_for_backorders=1,1,IF(
                                        advancedinventory_stock.backorder_limit=0,0,1
                                    )
                                )
                            )
                        )"
                    ),
                    'min_qty' => new \Zend_Db_Expr(
                        "IF(use_config_min_qty=1," . $minQty . ",cataloginventory_stock_item.min_qty)"
                    ),
                ]
            )->group("e.entity_id");

            return  $productCollections;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get product stock settings by place ids and store id
     *
     * @param $productId
     * @param $placeIds
     * @param $storeId
     * @return \Magento\Framework\DataObject|mixed
     */
    public function getProductStockSettingsByPlaceIdsAndStoreId($productId, $placeIds, $storeId)
    {
        /** @var \Riki\AdvancedInventory\Model\Stock $stockModel */
        $stockModel = $this->stockFactory->create();

        /*get stock settings for FO with default pos*/
        return $stockModel->getStockSettingsByPlaceIdsAndStoreId($productId, $placeIds, $storeId);
    }

    /**
     * Get product stock settings by place id and store id
     *
     * @param $productId
     * @param $storeId
     * @return \Magento\Framework\DataObject|mixed
     */
    public function getProductStockSettingsByStoreId($productId, $storeId)
    {
        $key = $productId . '-' . $storeId;

        if (!isset($this->stocksSettingData[$key])) {
            /** @var \Riki\AdvancedInventory\Model\Stock $stockModel */
            $stockModel = $this->stockFactory->create();

            /*get stock settings for FO with default pos*/
            $this->stocksSettingData[$key] = $stockModel->getStockSettingsByStoreId($productId, $storeId);
        }

        return $this->stocksSettingData[$key];
    }


    /**
     * Get message
     *
     * @param $qty
     * @param $minQty
     * @param $threshold
     * @param $stockModel
     * @return array
     */
    protected function getMessage($qty, $minQty, $threshold, $stockModel)
    {
        if ($qty < $minQty) {
            return [
                'class' => 'error',
                'message' => $stockModel['outstock_message']
            ];
        }

        if ($qty > $threshold) {
            return [
                'class' => 'instock-threshold',
                'message' => $stockModel['sufficient_message']
            ];
        } else {
            return [
                'class' => 'low-threshold',
                'message' => $stockModel['short_message']
            ];
        }
    }

    /**
     * get stock status - message for bundle product
     *
     * @param $product
     * @param $currentStore
     * @return array
     */
    protected function getStockStatusForBundleProduct($product, $currentStore)
    {
        $this->generateStockStatus();

        // for bundle or configurable products
        $stockParentModel = false;

        $productStockDisplayType = $product->getData('stock_display_type');

        if (!empty($productStockDisplayType)) {
            $stockParentModel = isset($this->stockStatus[$productStockDisplayType])
                ?$this->stockStatus[$productStockDisplayType]
                :false;
        }

        if (empty($stockParentModel)) {
            return [];
        }

        if (!$product->getIsSalable()) {
            return [
                'class' => 'error',
                'message' => $stockParentModel['outstock_message']
            ];
        }

        $typeInstance = $product->getTypeInstance();

        $requiredChildrenIds = $typeInstance->getChildrenIds($product->getId(), true);

        $flagMessageSufficient = true;

        $productIds = [];

        foreach ($requiredChildrenIds as $childIds) {
            $productIds += $childIds;
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('stock_display_type')
            ->addIdFilter($productIds);
        $productItems = $productCollection->getItems();

        foreach ($requiredChildrenIds as $childIds) {
            foreach ($childIds as $childId) {
                if (isset($productItems[$childId])
                    && ($statusId = $productItems[$childId]->getData('stock_display_type'))
                ) {
                    if (isset($this->stockStatus[$statusId])) {
                        $stockModel = $this->stockStatus[$statusId];

                        $threshold = (int)$stockModel['threshold'];

                        $productChildStockSettings = $this->getProductStockSettingsByPlaceIdsAndStoreId(
                            $childId,
                            $this->getDefaultFoPos(),
                            $currentStore
                        );

                        $qty = (int)$productChildStockSettings->getQuantityInStock();

                        /*product is allowed back order*/
                        if ($productChildStockSettings->getBackorderableAtProductLevel()) {
                            $qty += (int) $productChildStockSettings->getBackorderLimitInStock();
                        }

                        if ($qty <= $threshold) {
                            $flagMessageSufficient = false;
                        }
                    }
                } else {
                    $flagMessageSufficient = false;
                }
            }
        }

        if ($flagMessageSufficient) {
            return [
                'class' => 'instock-threshold',
                'message' => $stockParentModel['sufficient_message'],
            ];
        } else {
            return [
                'class' => 'low-threshold',
                'message' => $stockParentModel['short_message'],
            ];
        }
    }
}
