<?php
namespace Riki\BackOrder\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const BACK_ORDER_OVER_LIMIT_MESSAGE = 'We don\'t have as many quantity as you requested.';

    const NO_BACK_ORDER = 0;
    const BACK_ORDER_OVER_LIMIT = 3;
    const BACK_ORDER_IN_STOCK = 4;
    const BACK_ORDER_OUT_OF_STOCK = 100;

    const BACK_ORDER_NO_ERROR_CODE = 0;

    protected $_stockModel;

    protected $_datetime;

    /**
     * @var \Wyomind\AdvancedInventory\Model\ResourceModel\Product\Collection
     */
    protected $_stockProductCollection;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_subscriptionCourseFactory;

    protected $_productIdsToStockInfo = [];

    /* @var \Wyomind\AdvancedInventory\Model\StockFactory */
    protected $_stockFactory;

    /* @var \Wyomind\PointOfSale\Model\PointOfSaleFactory */
    protected $_posFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Catalog\Helper\Product  */
    protected $productHelper;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    protected $_placeIdsByStore = [];

    /**
     * Global variable is used to cached product data
     *
     * @var array
     */
    protected $productData = [];

    /**
     * Global variable is used to cached product stock status
     *
     * @var array
     */
    protected $productStockStatus = [];

    /**
     * Data constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     * @param \Wyomind\AdvancedInventory\Model\StockFactory $stockFactory
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Wyomind\AdvancedInventory\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Wyomind\AdvancedInventory\Model\Stock $modelStock
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $course
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
     * @param \Riki\Catalog\Model\StockState $stockState
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Wyomind\AdvancedInventory\Model\StockFactory $stockFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Wyomind\AdvancedInventory\Model\Stock $modelStock,
        \Riki\SubscriptionCourse\Model\CourseFactory $course,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Catalog\Model\StockState $stockState
    ) {
        $this->_storeManager = $storeManagerInterface;
        $this->_posFactory = $pointOfSaleFactory;
        $this->_stockFactory = $stockFactory;
        $this->_stockModel = $modelStock;
        $this->_stockProductCollection = $productCollectionFactory;
        $this->_datetime = $datetime;
        $this->_subscriptionCourseFactory = $course;
        $this->productHelper = $productHelper;
        $this->productRepository = $productRepository;
        $this->stockState = $stockState;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public function getDateTimeObj(){
      return $this->_datetime;
    }

    /**
     * @return \Magento\Catalog\Helper\Product
     */
    public function getProductHelper()
    {
        return $this->productHelper;
    }

    /**
     * get back order status for a specific product
     *
     * @param $productId
     * @param int $buyQty
     * @return mixed
     */
    public function getBackOrderStatusByProductId($productId, $buyQty = 0){

        $backOrderInfo = $this->getBackOrderInfoByProductId($productId, $buyQty);

        return $backOrderInfo['type'];
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return int
     */
    public function getBackOrderStatusByQuoteItem(\Magento\Quote\Model\Quote\Item $item){

        return $this->getBackOrderStatusByProductId($item->getProductId(), $item->getQty());
    }

    /**
     * Product has config back order.
     *
     * @param $productId
     * @param $storeId
     *
     * @return bool
     */
    public function isConfigBackOrder($productId, $storeId)
    {
        $isBackOrderConfig = false;

        if(!isset($this->_placeIdsByStore[$storeId])){
            $this->_placeIdsByStore[$storeId] = $this->_posFactory->create()->getPlacesByStoreId($storeId);
        }

        $places = $this->_placeIdsByStore[$storeId];
        foreach ($places as $place) {
            $stockInfo = $this->getStockInfo($productId, $place->getId());
            if ($stockInfo->getData('backorderable_at_stock_level') == 1) {
                return true;
            }
        }
        return $isBackOrderConfig;
    }

    /**
     * Get Stock Info
     *
     * @param $productId
     * @param $placeId
     * @param array $placeIds
     * @param bool $itemId
     *
     * @return \Magento\Framework\DataObject
     */
    public function getStockInfo($productId, $placeId, $placeIds = [], $itemId = false)
    {
        $stockSetting = $this->_stockFactory->create()->getStockSettings($productId, $placeId, $placeIds, $itemId);
        $stockInfo = $this->getStockInfoByProductId($productId);
        if ($stockInfo->getWarehousesData()) {
            foreach ($stockInfo->getWarehousesData() as $wareHouse) {
                if ($placeId && $placeId == $wareHouse->getPlaceId()) {
                    return $stockSetting->setData('ware_house_data', $wareHouse);
                }
            }
        }
        return $stockSetting;
    }


    /**
     * get default stock info for specific product
     *
     * @param $productId
     * @return mixed
     */
    public function getStockInfoByProductId($productId){

        if(!isset($this->_productIdsToStockInfo[$productId])){

            $stockSetting = $this->_stockProductCollection->create()->getStockSettings(
                $productId,
                false,
                false,
                []
            );

            if (!$stockSetting->getMultistockEnabled()) {
                $stockSetting->setBackorderableAtStockLevel($stockSetting->getDefaultBackorderableAtStockLevel());
                $stockSetting->setManagedAtStockLevel($stockSetting->getDefaultManagedAtStockLevel());
            }

            if (
                $stockSetting->getManagedAtProductLevel() &&
                $stockSetting->getManagedAtStockLevel()
            ) {
                if ($stockSetting->getMultistockEnabled()) {
                    if ($stockSetting->getBackorderableAtStockLevel()) {

                        $warehouseData = $this->_stockModel->getCollection()
                            ->addFieldToFilter('product_id', $productId);

                        $stockSetting->setWarehousesData($warehouseData);
                    }
                }
            }

            $this->_productIdsToStockInfo[$productId] = $stockSetting;
        }

        return $this->_productIdsToStockInfo[$productId];

    }

    /**
     * @param $productId
     * @param $buyQty
     * @return array|mixed
     */
    public function getBackOrderInfoByProductId($productId, $buyQty)
    {
        $cacheKey = $productId.'_'.$buyQty;

        if (!isset($this->productStockStatus[$cacheKey])) {
            $type = self::NO_BACK_ORDER;

            $product = $this->getProductById($productId);

            if ($product) {
                /*validate stock for this product*/
                $canAssigned = $this->stockState->canAssigned(
                    $product,
                    $buyQty,
                    $this->stockState->getPlaceIds()
                );

                if (!$canAssigned) {
                    $type = self::BACK_ORDER_OUT_OF_STOCK;
                }
            }

            $this->productStockStatus[$cacheKey] = ['type'  =>  $type, 'first_date'    =>  null];
        }

        return $this->productStockStatus[$cacheKey];
    }

    /**
     * @param $warehouse
     * @param $buyQty
     * @param $minQty
     * @return array
     */
    protected function getBackOrderInfoByWarehouse($warehouse, $buyQty, $minQty)
    {
        if ($warehouse->getQuantityInStock() - $buyQty < $minQty) {
            if ($warehouse->getBackorderAllowed()
                && $warehouse->getBackorderExpire() >= $this->_datetime->date('Y-m-d')
            ) {
                if ((int)$warehouse->getBackorderLimit() == 0
                    || ($warehouse->getQuantityInStock() - $buyQty + $warehouse->getBackorderLimit()) >= $minQty
                ) {
                    return [
                        'type'  =>  self::BACK_ORDER_IN_STOCK,
                    ];
                } else {
                    return [
                        'type'  =>  self::BACK_ORDER_OVER_LIMIT
                    ];
                }
            } else {
                if ($warehouse->getManageStock()) {
                    return [
                        'type'  =>  self::BACK_ORDER_OUT_OF_STOCK
                    ];
                }
            }
        }

        return [
            'type'  =>  self::NO_BACK_ORDER
        ];
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @return null|string
     */
    public function getHanpukaiDeliveryDateFromCart(\Magento\Quote\Api\Data\CartInterface $cart){
        $subscriptionCourseId = $cart->getData(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID);

        $hanpukaiDeliveryDate = null;

        if($subscriptionCourseId){
            /** @var \Riki\SubscriptionCourse\Model\Course $subscriptionCourse */
            $subscriptionCourse = $this->_subscriptionCourseFactory->create()->load($subscriptionCourseId);

            if(
                $subscriptionCourse->getId() &&
                $subscriptionCourse->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI &&
                !$subscriptionCourse->getHanpukaiDeliveryDateAllowed()
            ){
                if($subscriptionCourse->getHanpukaiFirstDeliveryDate()){
                    $hanpukaiDeliveryDate = $this->_datetime->date('Y-m-d', $subscriptionCourse->getHanpukaiFirstDeliveryDate());
                }else{
                    $hanpukaiDeliveryDate = $this->_datetime->date('Y-m-d');
                }
            }
        }

        return $hanpukaiDeliveryDate;
    }

    /**
     * @param $backOrderStatus
     * @return bool|mixed
     */
    public function isAvailableStock($backOrderStatus){
        if($backOrderStatus == self::BACK_ORDER_OVER_LIMIT)
            return false;

        if($backOrderStatus == self::BACK_ORDER_OUT_OF_STOCK){
            return $this->scopeConfig->getValue(
                'cataloginventory/order_options/allow_create_order_out_of_stock',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }

        return true;
    }

    /**
     * Get stocks info of product
     *
     * @param $productId
     * @param $storeId
     *
     * @return array
     */
    public function getStocks($productId, $storeId)
    {
        $stocks = [];
        $places = $this->_posFactory->create()->getPlacesByStoreId($storeId);
        foreach ($places as $place) {
            $stocks[$place->getId()] = $this->getStockInfo($productId, $place->getId());
        }

        return $stocks;
    }

    /**
     * Get product by id
     *
     * @param $productId
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductById($productId)
    {
        $cacheKey = 'product_'.$productId;

        if (!isset($this->productData[$cacheKey])) {
            try {
                $this->productData[$cacheKey] = $this->productRepository->getById($productId);
            } catch (\Exception $e) {
                return false;
            }
        }

        return $this->productData[$cacheKey];
    }
}
