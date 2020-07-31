<?php
namespace Riki\Subscription\Helper\StockPoint;

use Magento\Framework\App\Helper\Context;
use Magento\Setup\Exception;
use Riki\StockPoint\Api\StockPointProfileBucketRepositoryInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const STOCK_POINT_ENABLE = "subscriptioncourse/stockpoint/is_active";
    const STOCK_POINT_PUBLIC_KEY = "subscriptioncourse/stockpoint/public_key";
    const STOCK_POINT_POST_URL = "subscriptioncourse/stockpoint/post_url";
    const API_URL = "subscriptioncourse/stockpoint/api_url";
    const KEY_AUTHORIZATION_API = "subscriptioncourse/stockpoint/key_authorization_api";

    const STOCK_ACTIVE = 1;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $assignation;
    /**
     * @var \Riki\PointOfSale\Model\PointOfSaleRepository
     */
    protected $pointOfSaleRepository;
    /**
     * @var \Riki\StockPoint\Api\StockPointDeliveryBucketRepositoryInterface
     */
    protected $stockPointDeliveryBucketRepository;
    /**
     * @var \Riki\StockPoint\Api\StockPointProfileBucketRepositoryInterface
     */
    protected $stockPointProfileBucketRepositoryInterface;
    /**
     * @var \Riki\StockPoint\Model\StockPointDeliveryBucketFactory
     */
    protected $stockPointDeliveryBucketFactory;
    /**
     * @var \Riki\StockPoint\Model\StockPointFactory
     */
    protected $stockPointFactory;
    /**
     * @var \Riki\StockPoint\Model\StockPointProfileBucketFactory
     */
    protected $stockPointProfileBucketFactory;
    /**
     * @var \Riki\StockPoint\Model\StockPointRepository
     */
    protected $stockPointRepository;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;
    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;
    /**
     * @var \Riki\PointOfSale\Model\DataMigration
     */
    protected $dataMigration;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    /**
     * @var \Riki\StockPoint\Model\StockPointProfileBucketRepository
     */
    protected $stockPointProfileBucketRepository;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    protected $arrProduct = [];
    /**
     * @var \Riki\BackOrder\Helper\Data
     */
    protected $backOrderData;

    /**
     * Data constructor.
     * @param Context $context
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\AdvancedInventory\Model\Assignation $assignation
     * @param \Riki\PointOfSale\Model\PointOfSaleRepository $pointOfSaleRepository
     * @param \Riki\StockPoint\Api\StockPointDeliveryBucketRepositoryInterface $stockPointDeliveryBucketRepository
     * @param StockPointProfileBucketRepositoryInterface $stockPointProfileBucketRepositoryInterface
     * @param \Riki\StockPoint\Model\StockPointDeliveryBucketFactory $stockPointDeliveryBucketFactory
     * @param \Riki\StockPoint\Model\StockPointFactory $stockPointFactory
     * @param \Riki\StockPoint\Model\StockPointProfileBucketFactory $stockPointProfileBucketFactory
     * @param \Riki\StockPoint\Model\StockPointRepository $stockPointRepository
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Riki\PointOfSale\Model\DataMigration $dataMigration
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\BackOrder\Helper\Data $backOrderData
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\AdvancedInventory\Model\Assignation $assignation,
        \Riki\PointOfSale\Model\PointOfSaleRepository $pointOfSaleRepository,
        \Riki\StockPoint\Api\StockPointDeliveryBucketRepositoryInterface $stockPointDeliveryBucketRepository,
        StockPointProfileBucketRepositoryInterface $stockPointProfileBucketRepositoryInterface,
        \Riki\StockPoint\Model\StockPointDeliveryBucketFactory $stockPointDeliveryBucketFactory,
        \Riki\StockPoint\Model\StockPointFactory $stockPointFactory,
        \Riki\StockPoint\Model\StockPointProfileBucketFactory $stockPointProfileBucketFactory,
        \Riki\StockPoint\Model\StockPointRepository $stockPointRepository,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\PointOfSale\Model\DataMigration $dataMigration,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\BackOrder\Helper\Data $backOrderData,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->assignation = $assignation;
        $this->pointOfSaleRepository = $pointOfSaleRepository;
        $this->stockPointDeliveryBucketRepository = $stockPointDeliveryBucketRepository;
        $this->stockPointProfileBucketRepositoryInterface = $stockPointProfileBucketRepositoryInterface;
        $this->stockPointDeliveryBucketFactory = $stockPointDeliveryBucketFactory;
        $this->stockPointFactory = $stockPointFactory;
        $this->stockPointProfileBucketFactory = $stockPointProfileBucketFactory;
        $this->stockPointRepository = $stockPointRepository;
        $this->profileRepository = $profileRepository;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->dataMigration = $dataMigration;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->resourceConnection = $resourceConnection;
        $this->backOrderData = $backOrderData;
    }

    /**
     * @return bool
     */
    public function isEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $isEnabled = $this->scopeConfig->getValue(self::STOCK_POINT_ENABLE, $storeScope);
        return $isEnabled;
    }

    /**
     * get public key of config
     */
    public function getPublicKey()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $key = $this->scopeConfig->getValue(self::STOCK_POINT_PUBLIC_KEY, $storeScope);
        return $key;
    }
    /**
     * get url redirect post
     */
    public function getUrlPost()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $url = $this->scopeConfig->getValue(self::STOCK_POINT_POST_URL, $storeScope);
        return $url;
    }

    /**
     *  get array product id
     * @param $arrProductCart
     * @return array
     */
    public function getArrProductId($arrProductCart)
    {
        if (empty($arrProductCart)) {
            return [];
        }
        $arrProductId = [];
        foreach ($arrProductCart as $item) {
            $arrProductId[] = $item['product_id'];
        }
        return $arrProductId;
    }

    /**
     *  Validate attribute allow_stock_point = true
     *
     * @param $productIds
     * @param $arrProductCartSession
     * @return bool
     */
    public function validateAllProductAllowStockPoint($productIds, $arrProductCartSession)
    {
        $query = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productIds, 'in')
            ->create();
        $productCollection = $this->productRepository->getList($query);
        if ($productCollection->getTotalCount()>0) {
            $arrProductCart = $this->validateStockPointProduct->convertDataProductCartSession($arrProductCartSession);
            foreach ($productCollection->getItems() as $product) {
                if (!$product->getData('allow_stock_point') && $product->getData('parent_item_id')==null) {
                    return false;
                }
                $this->arrProduct[$product->getId()]['product'] = $product;
                $this->arrProduct[$product->getId()]['qty'] = $arrProductCart[$product->getId()];
            }
        }
        return true;
    }

    /**
     * Check delivery type address
     *
     * @param $arrProductCartSession
     * @return bool
     */
    public function isDeliveryTypeNormalDm($arrProductCartSession)
    {
        $deliveryType = [];
        foreach ($arrProductCartSession as $product) {
            if ($product->getParentItemId()) {
                continue;
            }
            $productId = $product->getProductId();
            if (isset($this->arrProduct[$productId]['product']) &&
                $this->arrProduct[$productId]['product'] instanceof \Magento\Catalog\Model\Product
            ) {
                $dataType = $this->arrProduct[$productId]['product']->getData('delivery_type');
                $deliveryType[$dataType] = $dataType;
            }
        }

        if (!empty($deliveryType)) {
            if ($this->validateStockPointProduct->validateDeliveryTypeAddress($deliveryType)) {
                return true;
            }
        }
        return false;
    }

    /**
     * check 4 condition
     *
     * @param $objProfileSession
     * @return bool
     */
    public function checkShowButtonStockPoint($objProfileSession)
    {
        $arrProductCartSession = $objProfileSession->getProductCartData();
        $productIds = $this->getArrProductId($arrProductCartSession);
        $profileId = $objProfileSession->getData('profile_id');

        $isShow = true;
        if (!$this->isEnable()) {
            $isShow = false;
            /**
             * Check exist stock point
             */
            if ($this->validateStockPointProduct->checkProfileExistStockPoint($objProfileSession)) {
                $isShow = true;
            }
        }

        if ($isShow && $arrProductCartSession && $profileId) {
            /**
             * Check all allow stock point
             */
            $errorStockPoint = $this->validateAllProductAllowStockPoint($productIds, $arrProductCartSession);
            if (!$errorStockPoint) {
                return false;
            }

            /**
             * All products are in stock on Hitachi
             */
            if (!empty($this->arrProduct)) {
                if (!$this->validateStockPointProduct->checkAllProductInStockWareHouse($this->arrProduct)) {
                    return false;
                }
            } else {
                return false;
            }

            /**
             * Check payment method = paygent
             */
            if ($objProfileSession->getData('payment_method') != \Bluecom\Paygent\Model\Paygent::CODE) {
                return false;
            }

            /**
             * Check delivery type address
             */
            if (!$this->isDeliveryTypeNormalDm($arrProductCartSession)) {
                return false;
            }
        }

        return $isShow;
    }

    /**
     * Call api register delivery
     *
     * @param $arrData
     * @return bool|null
     */
    public function callAPIRegisterDelivery($arrData)
    {
        $arrData = $this->buildStockPointPostData->convertDataBeforeCallApi($arrData);
        $data =  $this->buildStockPointPostData->callAPIRegisterDelivery($arrData);
        $isCallApiSuccess =  $this->buildStockPointPostData->checkCallApiSuccess();
        if ($isCallApiSuccess && isset($data['data'])) {
            return $data['data'];
        }
        return false;
    }
    
    /**
     * Call API to update delivery in case of delivery_type = subcarirer 
     * 
     * @input mixed data array
     * @returns boolean
     */ 
    public function callAPIUpdateDelivery($inputData) {
        $arrData = $this->buildStockPointPostData->convertDataBeforeCallApi($inputData);
        $data =  $this->buildStockPointPostData->callAPIUpdateDelivery($arrData);
        $isCallApiSuccess =  $this->buildStockPointPostData->checkCallApiSuccess();
        if ($isCallApiSuccess && isset($data['data'])) {
            return $data['data'];
        }
        return false;
    }

    /**
     * Get address stock point
     *
     * @param $profileId
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAddressStockPoint($profileId)
    {
        $stockPoint = $this->getStockPointByProfileId($profileId);
        if ($stockPoint) {
            $address = $stockPoint->getFirstname().' '.$stockPoint->getLastname(). ', ' .$stockPoint->getStreet()
                .', '.$stockPoint->getRegionId(). ', ' . $stockPoint->getPostcode() . ', '. $stockPoint->getTelephone();
            return $address;
        }
        return false;
    }

    /**
     * @param $profileId
     * @return bool|\Riki\StockPoint\Model\StockPoint
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStockPointByProfileId($profileId)
    {
        $profileModel = $this->profileRepository->get($profileId);
        if ($profileModel->getProfileId() && $profileModel->getStockPointProfileBucketId()) {
            return $this->getStockPointByBucketId($profileModel->getStockPointProfileBucketId());
        }
        return false;
    }

    /**
     * @param $bucketId
     * @return bool|\Riki\StockPoint\Model\StockPoint
     */
    public function getStockPointByBucketId($bucketId)
    {
        $bucketModel = $this->stockPointProfileBucketFactory->create()
            ->load((int)$bucketId);
        if ($bucketModel->getProfileBucketId() && $bucketModel->getStockPointId()) {
            $stockPointId = $bucketModel->getStockPointId();
            return $this->getStockPoint($stockPointId);
        }
        return false;
    }

    /**
     * @param $stockPointId
     * @return \Riki\StockPoint\Model\StockPoint
     */
    public function getStockPoint($stockPointId)
    {
        $stockPointModel = $this->stockPointFactory->create()
            ->load($stockPointId);
        return $stockPointModel;
    }
    /**
     * @param $stockPointId
     * @param $externalBucketId
     * @return \Magento\Framework\DataObject|\Riki\StockPoint\Model\StockPointProfileBucket
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveBucket($stockPointId, $externalBucketId)
    {
        $profileBucket = $this->stockPointProfileBucketRepositoryInterface->getProfileBucketById($externalBucketId);
        if ($profileBucket) {
            return $profileBucket;
        } else {
            $bucketModel = $this->stockPointProfileBucketRepositoryInterface->createProfileBucket(
                $stockPointId,
                $externalBucketId
            );
            return $bucketModel;
        }
    }

    /**
     * @param $deliveryType
     * @return false|int
     */
    public function convertDeliveryTypeToInt($deliveryType)
    {
        $mapping= [
            \Riki\Subscription\Model\Profile\Profile::LOCKER => 'LOCKER',
            \Riki\Subscription\Model\Profile\Profile::PICKUP => 'PICKUP',
            \Riki\Subscription\Model\Profile\Profile::DROPOFF => 'DROPOFF',
            \Riki\Subscription\Model\Profile\Profile::SUBCARRIER => 'SUBCARRIER'
            ];
        $upperDeliveryType = strtoupper($deliveryType);

        return array_search($upperDeliveryType, $mapping);
    }

    /**
     * @param $profileId
     * @return bool|mixed|null
     * @throws \Zend_Json_Exception
     */
    public function removeFromBucket($profileId)
    {
        $data = $this->buildStockPointPostData->removeFromBucket($profileId);
        return $data;
    }

    /**
     * @param $stockPointId
     * @param $deliveryDate
     * @param $profileBucketId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDeliveryBucketIdByStockPointIdAndDeliveryDate($stockPointId, $deliveryDate, $profileBucketId)
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('stock_point_id', $stockPointId)
            ->addFilter('delivery_date', $deliveryDate)
            ->setPageSize(1)
            ->create();

        $deliveryInfo = $this->stockPointDeliveryBucketRepository->getList($criteria);

        if ($deliveryInfo->getTotalCount()) {
            foreach ($deliveryInfo->getItems() as $item) {
                return $item->getId();
            }
        }

        $stockPointData = $this->getStockPoint($stockPointId);

        if (!$stockPointData) {
            return false;
        }

        /*create new delivery bucket data*/
        $deliveryBucket = $this->stockPointDeliveryBucketFactory->create();
        $deliveryBucket->setData($stockPointData->getData());
        $deliveryBucket->setData('stock_point_id', $stockPointId);
        $deliveryBucket->setData('delivery_date', $deliveryDate);
        $deliveryBucket->setData('profile_bucket_id', $profileBucketId);

        try {
            $deliveryBucket->save();
            return $deliveryBucket->getId();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $profileId
     * @return array|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getArrDataAddressStockPoint($profileId)
    {
        $stockPoint = $this->getStockPointByProfileId($profileId);
        $arrData = null;
        if ($stockPoint) {
            $prefecture = $this->buildStockPointPostData->getRegionNameById($stockPoint->getRegionId());
            $arrAddr = [
                '〒 ' . $stockPoint->getPostcode(),
                $prefecture,
                $stockPoint->getStreet()
            ];
            $arrData = [
                'firstName' => $stockPoint->getFirstname(),
                'firstNameKana' => $stockPoint->getFirstnameKana(),
                'lastName' => $stockPoint->getLastname(),
                'lastNameKana' => $stockPoint->getLastnameKana(),
                'addressFull' => implode(" ", $arrAddr),
                'address' => $stockPoint->getStreet(),
                'prefecture' => $prefecture,
                'postcode' => $stockPoint->getPostcode(),
                'telephone' => $stockPoint->getTelephone(),
            ];
        }
        return $arrData;
    }

    /**
     * Validate add product to stock point
     *
     * @param $productId
     * @param $qty
     * @return mixed
     */
    public function validateAddProductToStockPoint($product, $qty)
    {
        /** validateEnableAddProductToStockPoint */
        if (!$product->getData('allow_stock_point')) {
            return false;
        }
        /** delivery_Type = normal or DM*/
        $arrDeliveryType = [
            \Riki\DeliveryType\Model\Delitype::NORMAl,
            \Riki\DeliveryType\Model\Delitype::DM
        ];
        if (!in_array($product->getDeliveryType(), $arrDeliveryType)) {
            return false;
        }

        $warehouse = $this->dataMigration->getWarehouseByCode(
            \Riki\StockPoint\Helper\ValidateStockPointProduct::WH_HITACHI
        );
        $wareHouseId = $warehouse->getPlaceId();
        if (!$wareHouseId) {
            return false;
        }
        try {
            $productModel = $this->productRepository->getById($product->getId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }

        if ($productModel->getTypeId() == "bundle") {
            $items = $this->validateStockPointProduct->getBundleItems($productModel);
            foreach ($items as $item) {
                $itemId = $item["product_id"];
                $itemQty = (int)$item["selection_qty"] * $qty;
                $validate= $this->validateInStockWareHouse($itemId, $wareHouseId, $itemQty);
                if (!$validate) {
                    return false;
                }
            }
        } else {
            $available = $this->validateInStockWareHouse($product->getId(), $wareHouseId, $qty);
            if (!$available) {
                return false;
            }
        }

        return true;
    }

    /**
     * check instock warehouse
     * @param $productId
     * @param $wareHouseId
     * @param $qty
     * @return bool
     */
    public function validateInStockWareHouse($productId, $wareHouseId, $qty)
    {
        $available = $this->assignation->checkAvailability($productId, $wareHouseId, $qty, null);
        /*stock is not enough*/
        if ($available['status'] <= \Riki\AdvancedInventory\Model\Assignation::STOCK_STATUS_AVAILABLE_BACK_ORDER) {
            return false;
        }

        return true;
    }
}
