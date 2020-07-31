<?php

namespace Riki\StockPoint\Helper;

use Magento\Framework\App\Area;
use Magento\Setup\Exception;
use Riki\DeliveryType\Model\Delitype as GroupDeliveryType;
use Riki\AdvancedInventory\Model\Assignation;
use Riki\PointOfSale\Model\DataMigration;
use Riki\StockPoint\Model\Api\BuildStockPointPostData;
use Riki\Subscription\Helper\StockPoint\Data;
use \Riki\StockPoint\Logger\StockPointLogger;

class ValidateStockPointProduct extends \Magento\Framework\App\Helper\AbstractHelper
{
    const WH_HITACHI = 'HITACHI';
    const PROFILE_TYPE_TEMP = 'tmp';
    const STOCK_POINT_DELIVERY_EXPLANATION = 1;
    const STOCK_POINT_DELIVERY_EXPLANATION_NOT_ALLOWED = 2;
    const STOCK_POINT_DELIVERY_EXPLANATION_OOS = 3;
    const STOCK_POINT_LEADTIME_INACTIVE = 4;

    /**
     * @var Assignation
     */
    protected $assignation;

    /**
     * @var DataMigration
     */
    protected $dataMigration;

    /**
     * @var Data
     */
    protected $stockPointData;

    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $deliveryTypeHelper;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * type of block will show subscription page FO
     * 0 : not show
     * 1 : stock_point_delivery_explanation
     * 2 : stock_point_delivery_explanation_not_allowed
     * 3 : stock_point_delivery_explanation_oos
     * @var int
     */
    protected $typeShowBlock = 0;

    /**
     * @var null
     */
    /**
     * @var StockPointLogger
     */
    protected $stockPointLogger;

    protected $isStockPoint = null;

    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $helperPromo;

    /**
     * @var bool
     */
    protected $isCleanDataSpCarrier = false;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Riki\ShipLeadTime\Model\LeadtimeFactory
     */
    protected $leadTimeFactory;

    /**
     * ValidateStockPointProduct constructor.
     * @param Assignation $assignation
     * @param DataMigration $dataMigration
     * @param \Riki\DeliveryType\Helper\Data $deliveryTypeHelper
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Riki\Promo\Helper\Data $helperPromo
     * @param \Magento\Framework\App\State $appState
     * @param StockPointLogger $stockPointLogger
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Riki\ShipLeadTime\Model\LeadtimeFactory $leadtimeFactory
     */
    public function __construct(
        Assignation $assignation,
        DataMigration $dataMigration,
        \Riki\DeliveryType\Helper\Data $deliveryTypeHelper,
        \Magento\Framework\App\Helper\Context $context,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\Promo\Helper\Data $helperPromo,
        \Magento\Framework\App\State $appState,
        StockPointLogger $stockPointLogger,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Riki\ShipLeadTime\Model\LeadtimeFactory $leadtimeFactory
    ) {
        parent::__construct($context);
        $this->helperProfile = $helperProfile;
        $this->assignation = $assignation;
        $this->dataMigration = $dataMigration;
        $this->deliveryTypeHelper = $deliveryTypeHelper;
        $this->helperPromo = $helperPromo;
        $this->appState = $appState;
        $this->stockPointLogger = $stockPointLogger;
        $this->regionFactory = $regionFactory;
        $this->leadTimeFactory = $leadtimeFactory;
    }

    /**
     * Check product allow stock point
     * @param $profile
     * @param $product
     * @param $productItems
     * @return bool
     */
    public function checkProductAllowStockPoint($profile, $product, $productItems)
    {
        $data = $profile->getData();
        if (isset($data['stock_point_profile_bucket_id']) && (int)$data['stock_point_profile_bucket_id'] > 0) {
            $allowStockPoint = $product->getData('allow_stock_point');
            $isInStock = $this->checkAllProductInStockWareHouse($productItems);
            $deliveryTypeSP = isset($data['stock_point_delivery_type']) ? $data['stock_point_delivery_type'] : 0;
            $productDeliveryType = $product->getData('delivery_type');
            $isSubCarrier = ($deliveryTypeSP == BuildStockPointPostData::SUBCARRIER) ? true : false;
            $isFo = (in_array($this->appState->getAreaCode(), [Area::AREA_FRONTEND, Area::AREA_WEBAPI_REST])  ) ? true : false;

            if ($allowStockPoint && in_array($productDeliveryType, $this->groupDeliveryAllowAddStockPoint()) && $isInStock) {
                /**
                 * if allow_stock_point = YES,product delivery type group = Normal, product has stock in Hitachi
                 */
                return true;
            } elseif (!$allowStockPoint && $isSubCarrier && $isFo) {
                /**
                 * If allow_stock_point = NO and stock_point_delivery_type = "4: subcarrier"
                 */
                $this->isCleanDataSpCarrier = true;
                return true;
            }

            return false;
        }
        return true;
    }

    public function groupDeliveryAllowAddStockPoint()
    {
        return [GroupDeliveryType::NORMAl, GroupDeliveryType::DM];
    }
    /**
     * disable 2 button stock point if profile main is stock point
     * @param $profileData
     * @return bool
     */
    public function disableButtonSP($profileData)
    {
        if ($profileData->getType() == self::PROFILE_TYPE_TEMP) {
            if (!$this->checkShowStockPointForTemp($profileData)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Check profile exist stock point
     *
     * @param $profileData
     * @return null|string
     */
    public function checkProfileExistStockPoint($profileData)
    {
        if ($profileData) {
            /**
             * Check stock point for profile session
             */
            if ($profileData->getData('stock_point_data') != null &&
                is_array($profileData->getData('stock_point_data'))
            ) {
                return true;
            }
            /**
             * Check profile exist
             */
            if ($profileData->getData('stock_point_profile_bucket_id')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check all product allow stock point
     *
     * @param $productCartItems
     * @param $productId
     * @return bool
     */
    public function checkAllProductAllowStockPoint($productCartItems, &$productId = false)
    {
        if (is_array($productCartItems) && !empty($productCartItems)) {
            foreach ($productCartItems as $product) {
                if (!isset($product['product'])) {
                    return false;
                } elseif (!$product['product']->getData('allow_stock_point')) {
                    $productId = $product['product']->getId();
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Check all product allow warehouse
     *
     * @param $productCartItems
     * @return bool
     */
    public function checkAllProductInStockWareHouse($productCartItems)
    {
        if (is_array($productCartItems) && !empty($productCartItems)) {
            $warehouse = $this->dataMigration->getWarehouseById(
                $this->assignation->getAssignationHelper()->getDefaultPosForStockPoint()
            );

            if ($warehouse) {
                $placeId = $warehouse->getPlaceId();
                foreach ($productCartItems as $product) {
                    if (!$this->_isAllowStockWareHouse($product, $placeId)) {
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param $simulateObject
     * @param $deliveryType
     * @return bool
     */
    protected function checkActiveWareHouse($simulateObject, $deliveryType)
    {
        if ($simulateObject && $simulateObject->getShippingAddress() &&
            $regionId = $simulateObject->getShippingAddress()->getRegionId()
        ) {
            try {
                $prefecture = $this->regionFactory->create()->load($regionId)->getCode();
                $wareHouseCode = self::WH_HITACHI;

                $leadTimeCollection = $this->leadTimeFactory->create()->getCollection()
                    ->addActiveToFilter() // is_avtive = 1
                    ->addFieldToFilter('pref_id', $prefecture)
                    ->addFieldToFilter('delivery_type_code', $deliveryType)
                    ->addFieldToFilter('warehouse_id', $wareHouseCode)
                    ->setPageSize(1);

                if (count($leadTimeCollection)) {
                    return true;
                }
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }
    /**
     * Check stock in warehouse
     *
     * @param $product
     * @param $placeId
     * @return bool
     */
    private function _isAllowStockWareHouse($product, $placeId)
    {
        if (isset($product['product'])) {
            $neededCheckProductIds = $this->_prepareQtyValidationData($product['product'], $product['qty']);
            if (empty($neededCheckProductIds)) {
                /** if product is bundle not has product child */
                return false;
            }

            foreach ($neededCheckProductIds as $productId => $qty) {
                $available = $this->assignation->checkAvailability($productId, $placeId, $qty, null);
                if ($available['status'] < Assignation::STOCK_STATUS_AVAILABLE_BACK_ORDER) {
                    return false;
                }
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * Check show button stock point
     *
     * @param $simulatorOrder
     * @param $deliveryType
     * @param $productCartItems
     * @param $profileData
     * @return bool
     */
    public function isShowStockPoint($simulatorOrder, $deliveryType, $productCartItems, $profileData)
    {
        $isShow = true;
        $profileId = $profileData->getData('profile_id');
        /**
         * Check config on/off stock point
         */
        if (!$this->isEnableStockPointConfig()) {
            $isShow = false;
            $this->writeLogStockPoint($profileId, 'Stock Point is not enable');
            /**
             * Check exist stock point
             */
            if (!$isShow && $this->checkProfileExistStockPoint($profileData)) {
                $isShow = true;
            }
        }

        if ($isShow && $simulatorOrder) {
            /**
             * Delivery type group is Normal
             */
            if ($deliveryType != GroupDeliveryType::NORMAl) {
                $this->typeShowBlock = 0;
                $this->writeLogStockPoint($profileId, 'delivery type group is not Normal');
                return false;
            }

            /**
             * Check payment method = paygent
             */
            if ($profileData->getData('payment_method') != \Bluecom\Paygent\Model\Paygent::CODE) {
                $this->typeShowBlock = 0;
                $this->writeLogStockPoint($profileId, 'payment method is not paygent');
                return false;
            }

            /**
             * All products has "allow_stock_point = YES" in cart
             */
            $productId = '';
            if (!$this->checkAllProductAllowStockPoint($productCartItems, $productId)) {
                $this->typeShowBlock = self::STOCK_POINT_DELIVERY_EXPLANATION_NOT_ALLOWED;
                if ($productId) {
                    $message = ' - Product Id :' . $productId . ' not allow stock point';
                } else {
                    $message = 'All products not allow stock point';
                }
                $this->writeLogStockPoint($profileId, $message);
                return false;
            }

            if (!$this->checkActiveWareHouse($simulatorOrder, $deliveryType)) {
                $this->typeShowBlock = self::STOCK_POINT_LEADTIME_INACTIVE;
                $this->writeLogStockPoint($profileId, 'LeadTime Inactive');
                return false;
            }
            /**
             * All products are in stock on Hitachi
             */
            if (!$this->checkAllProductInStockWareHouse($productCartItems)) {
                $this->typeShowBlock = self::STOCK_POINT_DELIVERY_EXPLANATION_OOS;
                $this->writeLogStockPoint($profileId, 'OOS Hitachi');
                return false;
            }
        } else {
            $isShow = false;
        }
        /**  for case stock point config off */
        if ($isShow) {
            $this->typeShowBlock = self::STOCK_POINT_DELIVERY_EXPLANATION;
        }
        return $isShow;
    }

    /**
     * Write log stock point when not show button
     *
     * @param $profileId
     * @param $message
     */
    public function writeLogStockPoint($profileId, $message)
    {
        $mainProfileId =$this->helperProfile->getMainFromTmpProfile($profileId);
        $this->stockPointLogger->info(
            "Profile id : ". $mainProfileId . " " . $message,
            ['type' => StockPointLogger::LOG_TYPE_DEBUG_SHOW_BUTTON]
        );
    }
    /**
     * Check type block will show on FO
     *
     * @return int
     */
    public function getTypeShowBlock()
    {
        return $this->typeShowBlock;
    }
    /**
     * Check enable config stock point
     *
     * @return mixed|null
     */
    public function isEnableStockPointConfig()
    {
        return $this->scopeConfig->getValue('subscriptioncourse/stockpoint/is_active');
    }

    /**
     * if MAIN profile is stock point, Not allow to choose Stock Point for TEMP profile
     * @param $profileData
     * @return bool
     */
    public function checkShowStockPointForTemp($profileData)
    {
        if ($this->isStockPoint != null) {
            return $this->isStockPoint;
        }
        $mainProfileModel = $this->helperProfile->getProfileMainByProfileTmpId($profileData->getProfileId());
        if ($mainProfileModel != false && $mainProfileModel->getData('stock_point_profile_bucket_id') !== null) {
            /** is stock point */
            $this->isStockPoint = false;
            $this->writeLogStockPoint($profileData->getProfileId(), 'profile have main is stock point');
            return $this->isStockPoint;
        }
        $this->isStockPoint = true;
        return $this->isStockPoint;
    }

    /**
     * Check stock point for profile session
     *
     * @param $profile
     * @return mixed
     */
    public function getStockPointNextDeliveryDate($profile)
    {
        if ($profile) {
            if ($profile->getData('stock_point_data') != null && is_array($profile->getData('stock_point_data'))) {
                $data = $profile->getData('stock_point_data');
                if (isset($data['next_delivery_date'])) {
                    return $data['next_delivery_date'];
                }
            } else {
                return $profile->getData('next_delivery_date');
            }
        }

        return false;
    }

    /**
     * Get stock point time slot
     *
     * @param $profile
     * @return mixed
     */
    public function getStockPointTimeSlot($profile)
    {
        if ($profile) {
            if ($profile->getData('stock_point_data') != null && is_array($profile->getData('stock_point_data'))) {
                $data = $profile->getData('stock_point_data');
                if (isset($data['delivery_time'])) {
                    return (int)$data['delivery_time'];
                }
            }
        }

        return false;
    }

    /**
     * Get address stock point
     *
     * @param $profileData
     * @return array|null
     */
    public function getAddressStockPoint($profileData)
    {
        $addressData = null;
        if ($profileData) {
            /**
             * Load data on session
             */
            if ($profileData->getData('stock_point_data') != null &&
                is_array($profileData->getData('stock_point_data'))
            ) {
                $data = $profileData->getData('stock_point_data');
                if ($data) {
                    /**
                     * If stock_point_delivery_type = "locker" OR "pickup" then the system show Stock Point address
                     * ELSE the system shows customer address that choose for sub profile
                     */
                    $deliveryType = isset($data['delivery_type']) ? $data['delivery_type'] : '';
                    $pickup = ($deliveryType == BuildStockPointPostData::PICKUP) ? true : false;
                    $locker = ($deliveryType == BuildStockPointPostData::LOCKER) ? true : false;
                    if ($deliveryType && ($pickup || $locker)) {
                        $fistName = isset($data['stock_point_firstname']) ? $data['stock_point_firstname'] : '';
                        $fistNameKana = null;
                        if (isset($data['stock_point_firstnamekana'])) {
                            $fistNameKana = $data['stock_point_firstnamekana'];
                        }

                        $lastName = isset($data['stock_point_lastname']) ? $data['stock_point_lastname'] : '';
                        $lastNameKana = null;
                        if (isset($data['stock_point_lastnamekana'])) {
                            $lastNameKana = $data['stock_point_lastnamekana'];
                        }

                        $address = isset($data['stock_point_address']) ? $data['stock_point_address'] : '';
                        $prefecture = isset($data['stock_point_prefecture']) ? $data['stock_point_prefecture'] : '';
                        $postcode = isset($data['stock_point_postcode']) ? $data['stock_point_postcode'] : '';
                        $telephone = isset($data['stock_point_telephone']) ? $data['stock_point_telephone'] : '';
                        $fullAddress = [
                            '〒 ' . $postcode,
                            $prefecture,
                            $address
                        ];
                        $addressData = [
                            'firstName' => $fistName,
                            'firstNameKana' => $fistNameKana,
                            'lastName' => $lastName,
                            'lastNameKana' => $lastNameKana,
                            'addressFull' => implode(' ', $fullAddress),
                            'address' => $address,
                            'prefecture' => $prefecture,
                            'postcode' => $postcode,
                            'telephone' => $telephone,
                        ];
                    }
                }
            }
        }
        return $addressData;
    }

    /**
     * Get data stock point delivery information
     *
     * @param $profileData
     * @return int
     */
    public function getDataStockPointDeliveryInformation($profileData)
    {
        if ($profileData) {
            /**
             * Load data on session profile when call api post stock point
             */
            if ($profileData->getData('stock_point_data') != null &&
                is_array($profileData->getData('stock_point_data'))
            ) {
                $data = $profileData->getData('stock_point_data');
                if (isset($data['comment_for_customer'])) {
                    return $data['comment_for_customer'];
                }
            }
            /**
             * Load data default when profile exist stock point
             */
            $profileBucketId = $profileData->getData('stock_point_profile_bucket_id');
            if ($profileBucketId) {
                return $profileData->getData('stock_point_delivery_information');
            }
        }
    }

    /**
     * Get delivery type stock point
     *
     * @param $deliveryType
     * @return int
     */
    public function getDeliveryTypeStockPoint($deliveryType)
    {
        $deliveryTypeInt = 0;
        switch ($deliveryType) {
            case 'locker':
                $deliveryTypeInt = BuildStockPointPostData::LOCKER;
                break;
            case 'dropoff':
                $deliveryTypeInt = BuildStockPointPostData::DROPOFF;
                break;
            case 'pickup':
                $deliveryTypeInt = BuildStockPointPostData::PICKUP;
                break;
            case 'subcarrier':
                $deliveryTypeInt = BuildStockPointPostData::SUBCARRIER;
                break;
        }
        return $deliveryTypeInt;
    }

    /**
     * Convert data product session
     *
     * @param $productCartItemsSession
     * @return array
     */
    public function convertDataProductCartSession($productCartItemsSession)
    {
        $items = [];
        if (!empty($productCartItemsSession)) {
            foreach ($productCartItemsSession as $product) {
                $items[$product['product_id']] = $product->getData('qty');
            }
        }
        return $items;
    }

    /**
     * Validate delivery type of address
     *
     * @param array $deliveryType
     * @return bool
     */
    public function validateDeliveryTypeAddress(array $deliveryType)
    {
        if (!empty($deliveryType)) {
            if (array_diff($deliveryType, $this->deliveryTypeHelper->getCoolNormalDmTypes())) {
                return false;
            }
            $groupDeliveryType = $this->deliveryTypeHelper->getDeliveryTypeOfCoolNormalDmGroup($deliveryType);
            if ($groupDeliveryType == GroupDeliveryType::NORMAl) {
                return true;
            }
        }
        return false;
    }

    /**
     * Prepare quantity validate data
     *
     * @param $productModel
     * @param $qty
     * @return array
     */
    private function _prepareQtyValidationData($productModel, $qty)
    {
        $neededCheckProductIds = [];

        if ($productModel->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            $bundleOptions = $this->getBundleItems($productModel);

            if (empty($bundleOptions)) {
                return $neededCheckProductIds;
            }
            foreach ($bundleOptions as $bundleOption) {
                $productId = $bundleOption["entity_id"];
                $productQty = $bundleOption["selection_qty"];
                if (isset($neededCheckProductIds[$productId])) {
                    $neededCheckProductIds[$productId] += $productQty * $qty;
                } else {
                    $neededCheckProductIds[$productId] = $productQty * $qty;
                }
            }
        } else {
            if (!isset($neededCheckProductIds[$productModel->getId()])) {
                $neededCheckProductIds[$productModel->getId()] = 0;
            }
            $neededCheckProductIds[$productModel->getId()] += $qty;
        }

        return $neededCheckProductIds;
    }

    /**
     * get all the selection products used in bundle product
     *
     * @param $product
     * @return array
     */
    public function getBundleItems($product)
    {
        $selectionCollection = $product->getTypeInstance(true)
            ->getSelectionsCollection(
                $product->getTypeInstance(true)->getOptionsIds($product),
                $product
            );
        return $selectionCollection->getData();
    }

    /**
     * Check show address of customer or address stock point
     *
     * @param $profileData
     * @return bool
     */
    public function canShowStockPointAddress($profileData)
    {
        if ($profileData && $profileData->getData('stock_point_delivery_type') != null) {
            $stockPointDeliveryType = $profileData->getData('stock_point_delivery_type');
            $pickup = ($stockPointDeliveryType == BuildStockPointPostData::PICKUP) ? true : false;
            $locker = ($stockPointDeliveryType == BuildStockPointPostData::LOCKER) ? true : false;
            if ($pickup || $locker) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check all product allow stock point
     *
     * @param $productCartItems
     * @return bool
     */
    public function checkAllProductIsDeliveryTypeNormal($productCartItems)
    {
        if (is_array($productCartItems) && !empty($productCartItems)) {
            foreach ($productCartItems as $product) {
                if (!isset($product['product'])) {
                    return false;
                } elseif ($product['product']->getData('delivery_type') != GroupDeliveryType::NORMAl) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * is product allowed stock point
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isProductAllowedStockPoint(
        \Magento\Catalog\Model\Product $product
    ) {
        if (!$product->getData('allow_stock_point')) {
            return false;
        }

        return $this->validateDeliveryTypeAddress([$product->getDeliveryType()]);
    }

    /**
     * @param $profileData
     * @return bool
     */
    public function checkProfileStockPointSubCarrier($profileData)
    {
        if ($profileData) {
            $isProfileSp = $profileData && $this->checkProfileExistStockPoint($profileData) ? true : false;
            $deliveryTypeSP = $profileData->getStockPointDeliveryType();
            if ($isProfileSp && $deliveryTypeSP == BuildStockPointPostData::SUBCARRIER) {
                return true;
            }
        }
        return false;
    }

    /**
     * Clean data for stock point carrier
     * @param $objProfileSession
     * @return mixed
     */
    public function cleanDataStockPointSubCarrier($objProfileSession)
    {
        $profileCarrier = $this->checkProfileStockPointSubCarrier($objProfileSession);
        if ($profileCarrier) {
            $hasBucketId = $objProfileSession->getData('stock_point_profile_bucket_id');
            $objProfileSession->setData("stock_point_profile_bucket_id", null)
                ->setData("stock_point_delivery_type", null)
                ->setData("stock_point_delivery_information", null)
                ->setData("stock_point_data", null)
                ->setData("riki_stock_point_id", null)
                ->setData("is_delete_stock_point", true)
                ->setData("delete_profile_has_bucket_id", $hasBucketId)
                ->setData("clean_stockpoint_data_subcarier_flg",true);

            //clean product cart
            foreach ($objProfileSession['product_cart'] as $productId => $product) {
                $objProfileSession['product_cart'][$productId]->setData(
                    'stock_point_discount_rate',
                    0
                );
            }
        }
        return $objProfileSession;
    }

    /**
     * @return bool
     */
    public function canCleanDataSpCarrier()
    {
        return $this->isCleanDataSpCarrier;
    }
}
