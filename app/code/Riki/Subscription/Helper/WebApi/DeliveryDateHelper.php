<?php
namespace Riki\Subscription\Helper\WebApi;

use Riki\Customer\Model\Address\AddressType;
use Riki\Subscription\Block\Frontend\Profile\Edit as ProfileEditBlock;
use Riki\Subscription\Helper\Order\Data as SubscriptionHelperOrderData;
use Riki\Subscription\Model\ProductCart\ProductCart;
use Riki\Subscription\Model\Profile\Profile;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Api\Filter;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;
use Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership as Membership;

class DeliveryDateHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CACHE_PREFIX = "landing_";

    const TYPE_DELIVERY_DATE = "delivery_date";

    const TYPE_SHIPPING_ADDRESS = "shipping_address";

    const TYPE_STOCKPOINT = "stockpoint";

    const TYPE_NONCE = "nonce";

    const ADDRESS_LINK_EDIT_HOME_NO_COMPANY
        = 'subscriptionprofiledit/subscription_profile_edit_customer_address/subscription_profile_edit_address_home_no_company_name';
    const ADDRESS_LINK_EDIT_HOME_HAVE_COMPANY
        = 'subscriptionprofiledit/subscription_profile_edit_customer_address/subscription_profile_edit_address_home_have_company_name';
    const ADDRESS_LINK_EDIT_AMBASSADOR_COMPANY
        = 'subscriptionprofiledit/subscription_profile_edit_customer_address/subscription_profile_edit_address_ambassador_company';
    const SUBCSCRIBER_X_DAY = 'mypage_subscriber_block/subscriber_block/x_day';
    const SUBCSCRIBER_Y_DAY = 'mypage_subscriber_block/subscriber_block/y_day';

    /* @var \Riki\Subscription\Helper\Data */
    protected $_subHelperData;

    /**
     * @var \Riki\Subscription\Model\Profile\FreeGift
     */
    protected $_freeGiftManagement;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $addressCustomer;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_subCourseModel;

    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $deliveryHelper;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_helperProfile;

    /**
     * @var \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    protected $calculateDeliveryDateHelper;

    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $_deliveryDate;

    /**
     * @var \Riki\SalesRule\Helper\CouponHelper
     */
    protected $_couponHelper;

    /**
     * @var \Riki\Subscription\Model\ProfileCacheRepository
     */
    protected $profileCacheRepository;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;
    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $_subCourseHelper;
    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $helperSimulator;
    /**
     * @var \Riki\Subscription\Helper\Indexer\Data
     */
    protected $profileIndexerHelper;
    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_customerAddressRepository;

    /**
     * @var \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper
     */
    protected $deliveryDateGenerateHelper;

    /**
     * @var \Riki\Subscription\Model\Profile\WebApi\ProfileRepository
     */
    protected $profileRepository;

    /**
     * @var \Magento\Framework\App\CacheInterface
     * @since 101.0.0
     */
    protected $_cache;
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $_imageBuilder;

    /**
     * @var
     */
    protected $simpleCache = [];

    /**
     * @var \Bluecom\Paygent\Model\PaygentHistory
     */
    protected $paygentHistory;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Address\CollectionFactory
     */
    protected $customerAddressCollectionFactory;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    /**
     * @var \Riki\Subscription\Helper\Profile\Controller\Save
     */
    protected $profileSaveHelper;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;
    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileApiRepository;
    /**
     * @var Profile
     */
    protected $profileModel;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\CollectionFactory
     */
    private $productCartCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
     */
    protected $productCollectionFactory;
    /**
     * @var \Magento\Framework\Pricing\Adjustment\AdjustmentInterface
     */
    protected $adjustmentCalculator;
    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $localeFormat;
    private $priceCurrency;
    private $searchCriteriaBuilder;
    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    private $stockPointDataHelper;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\Subscription\Helper\Data $subHelperData,
        \Riki\Subscription\Model\Profile\FreeGift $freeGiftManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Address $addressCustomer,
        \Riki\SubscriptionCourse\Model\Course $subCourseModel,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\DeliveryType\Helper\Data $deliveryHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDateHelper,
        \Riki\DeliveryType\Model\DeliveryDate $_deliveryDate,
        \Riki\SalesRule\Helper\CouponHelper $couponHelper,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository,
        \Magento\Framework\Registry $_registry,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\SubscriptionCourse\Helper\Data $_subCourseHelper,
        \Riki\Subscription\Helper\Order\Simulator $helperSimulator,
        \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $_customerAddressRepository,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper,
        \Riki\Subscription\Model\Profile\WebApi\ProfileRepository $profileRepository,
        \Magento\Framework\App\CacheInterface $_cache,
        SerializerInterface $serializer,
        \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder,
        \Bluecom\Paygent\Model\PaygentHistory $paygentHistory,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $customerAddressCollectionFactory,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\Subscription\Helper\Profile\Controller\Save $profileSaveHelper,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileApiRepository,
        Profile $profileModel,
        \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\CollectionFactory $productCartCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointDataHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->_subHelperData = $subHelperData;
        $this->_freeGiftManagement = $freeGiftManagement;
        $this->_customerFactory = $customerFactory;
        $this->addressCustomer = $addressCustomer;
        $this->_subCourseModel = $subCourseModel;
        $this->_productRepository = $productRepository;
        $this->deliveryHelper = $deliveryHelper;
        $this->_localeDate = $localeDate;
        $this->profileFactory = $profileFactory;
        $this->_helperProfile = $helperProfile;
        $this->calculateDeliveryDateHelper = $calculateDeliveryDateHelper;
        $this->_deliveryDate = $_deliveryDate;
        $this->_couponHelper = $couponHelper;
        $this->profileCacheRepository = $profileCacheRepository;
        $this->_registry = $_registry;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->_subCourseHelper = $_subCourseHelper;
        $this->helperSimulator = $helperSimulator;
        $this->profileIndexerHelper = $profileIndexerHelper;
        $this->_customerAddressRepository = $_customerAddressRepository;
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        $this->profileRepository = $profileRepository;
        $this->_cache = $_cache;
        $this->serializer = $serializer;
        $this->_imageBuilder = $_imageBuilder;
        $this->paygentHistory = $paygentHistory;
        $this->paymentHelper = $paymentHelper;
        $this->customerAddressCollectionFactory = $customerAddressCollectionFactory;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->profileSaveHelper = $profileSaveHelper;
        $this->courseFactory = $courseFactory;
        $this->profileApiRepository = $profileApiRepository;
        $this->profileModel = $profileModel;
        $this->productCartCollectionFactory = $productCartCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->adjustmentCalculator = $adjustmentCalculator;
        $this->localeFormat = $localeFormat;
        $this->priceCurrency = $priceCurrency;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->stockPointDataHelper = $stockPointDataHelper;
        $this->customerRepository = $customerRepository;
        $this->directoryList = $directoryList;
        parent::__construct($context);
    }

    public function getListProductByAddressAndByDeliveryType($simulatorOrder, $profileCache)
    {
        /** Validate rule applied if coupon not can applied then remove it */
        $this->getListRuleIdsApplied($simulatorOrder);

        $changeShippingAddressId = '';
        if ($profileCache->getData('new_shipping_address_id')) {
            $changeShippingAddressId = $profileCache->getData('new_shipping_address_id');
        }
        $arrProductCat = $profileCache->getData("product_cart");
        $freeGifts = $this->_subHelperData->getFreeGifts($simulatorOrder);
        if (sizeof($freeGifts)) {
            $arrProductCat = $this->_freeGiftManagement->addFreeGiftsToCartProfile($arrProductCat, $freeGifts);
        }
        $arrReturn = [];
        foreach ($arrProductCat as $key => $objProductData) {
            if ($objProductData->getData(Profile::PARENT_ITEM_ID) == 0) {
                $addressId = $objProductData->getData(Profile::SHIPPING_ADDRESS_ID);
                if ($changeShippingAddressId != '') {
                    $addressId = $changeShippingAddressId;
                }
                $objCustomer = $this->_customerFactory->create()->load($profileCache->getData("customer_id"));
                $objAddress = $objCustomer->getAddressById($addressId);
                if (is_null($objAddress->getId())) {
                    if ($objCustomer->getDefaultShippingAddress() instanceof \Magento\Customer\Model\Address) {
                        $addressId = $objCustomer->getDefaultShippingAddress()->getId();
                    } else {
                        $arrObjectAddress = $objCustomer->getAddresses();

                        if (count($arrObjectAddress) > 0) {
                            $addressId = array_keys($arrObjectAddress)[0];
                        } else {
                            return $arrReturn;
                        }
                    }
                }
                // Check this address is exists or not. If it is not exists. Choose default for customer

                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->loadProductModelFromProfileItem($objProductData, $profileCache);

                if (!$product) {
                    continue;
                }

                $deliveryType = $product->getData("delivery_type");


                if (!isset($arrReturn[$addressId][$deliveryType])) {
                    /** @var \Magento\Customer\Model\Address $objAddress */
                    $objAddress = $this->addressCustomer->load($addressId);
                    $addressName = $objAddress->getCustomAttribute('riki_nickname')->getValue();
                    $arrReturn[$addressId][$deliveryType]['name'] = $addressName;

                    $arrReturn[$addressId][$deliveryType]['info'] = [
                        $objAddress->getStreetLine(1),
                        $objAddress->getCity(),
                        $objAddress->getPostcode(),
                        $objAddress->getRegion()
                    ];

                    if ($objProductData->getData('delivery_date') != null) {
                        $arrReturn[$addressId][$deliveryType]['delivery_date'] = [
                            'next_delivery_date' => $objProductData->getData('delivery_date'),
                            'time_slot' => $objProductData->getData('delivery_time_slot'),
                        ];
                    } else {
                        $courseModel = $this->_subCourseModel->load($profileCache->getData('course_id'));
                        if ($courseModel->getData('allow_change_next_delivery_date') == 0) {
                            $arrReturn[$addressId][$deliveryType]['delivery_date'] = [
                                'next_delivery_date' => $profileCache->getData('next_delivery_date'),
                                'time_slot' => $objProductData->getData('delivery_time_slot'),
                            ];
                        } else {
                            $arrReturn[$addressId][$deliveryType]['delivery_date'] = [
                                'next_delivery_date' => $objProductData->getData('delivery_date'),
                                'time_slot' => $objProductData->getData('delivery_time_slot'),
                            ];
                        }
                    }
                }

                // 3) return

                $product->setFinalPrice(null);

                $amount = $this->getRenderPrice($product, $objProductData->getData('qty'));

                $arrReturn[$addressId][$deliveryType]['product'][] = [
                    'name' => !$objProductData->getData('is_free_gift') ? $product->getData("name") : $objProductData->getData('name'),
                    'price' => $product->getData("price"),
                    'qty' => $objProductData->getData('qty'),
                    'unit_case' => $objProductData->getData('unit_case'),
                    'unit_qty' => $objProductData->getData('unit_qty'),
                    'gw_id' => $objProductData->getData('gw_id'),
                    'gift_message_id' => $objProductData->getData('gift_message_id'),
                    'instance' => $product,
                    'productcat_id' => $objProductData->getData('cart_id'),
                    'productcartInstance' => $objProductData,
                    'amount' => !$objProductData->getData('is_free_gift') ? $amount : 0,
                    'is_free_gift' => $objProductData->getData('is_free_gift'),
                    'is_spot' => $objProductData->getData('is_spot'),
                    'is_addition' => $objProductData->getData('is_addition')
                ];
            }
        }

        return $arrReturn;
    }

    public function getListProductByAddressAndByDeliveryTypeNoSimulate($profileCache){
        /** Validate rule applied if coupon not can applied then remove it */

        $changeShippingAddressId = '';
        if ($profileCache->getData('new_shipping_address_id')) {
            $changeShippingAddressId = $profileCache->getData('new_shipping_address_id');
        }
        $arrProductCat = $profileCache->getData("product_cart");
        $arrReturn = [];
        foreach ($arrProductCat as $key => $objProductData) {
            if ($objProductData->getData(Profile::PARENT_ITEM_ID) == 0) {
                $addressId = $objProductData->getData(Profile::SHIPPING_ADDRESS_ID);
                if ($changeShippingAddressId != '') {
                    $addressId = $changeShippingAddressId;
                }
                $objCustomer = $this->_customerFactory->create()->load($profileCache->getData("customer_id"));
                $objAddress = $objCustomer->getAddressById($addressId);
                if (is_null($objAddress->getId())) {
                    if ($objCustomer->getDefaultShippingAddress() instanceof \Magento\Customer\Model\Address) {
                        $addressId = $objCustomer->getDefaultShippingAddress()->getId();
                    } else {
                        $arrObjectAddress = $objCustomer->getAddresses();

                        if (count($arrObjectAddress) > 0) {
                            $addressId = array_keys($arrObjectAddress)[0];
                        } else {
                            return $arrReturn;
                        }
                    }
                }
                $product = $this->loadProductModelFromProfileItem($objProductData, $profileCache);
                if (!$product) {
                    continue;
                }

                $deliveryType = $product->getData("delivery_type");
                if (!isset($arrReturn[$addressId][$deliveryType])) {
                    $objAddress = $this->addressCustomer->load($addressId);
                    $addressName = $objAddress->getCustomAttribute('riki_nickname')->getValue();
                    $arrReturn[$addressId][$deliveryType]['name'] = $addressName;

                    $arrReturn[$addressId][$deliveryType]['info'] = [
                        $objAddress->getStreetLine(1),
                        $objAddress->getCity(),
                        $objAddress->getPostcode(),
                        $objAddress->getRegion()
                    ];

                    if ($objProductData->getData('delivery_date') != null) {
                        $arrReturn[$addressId][$deliveryType]['delivery_date'] = [
                            'next_delivery_date' => $objProductData->getData('delivery_date'),
                            'time_slot' => $objProductData->getData('delivery_time_slot'),
                        ];
                    } else {
                        $courseModel = $this->_subCourseModel->load($profileCache->getData('course_id'));
                        if ($courseModel->getData('allow_change_next_delivery_date') == 0) {
                            $arrReturn[$addressId][$deliveryType]['delivery_date'] = [
                                'next_delivery_date' => $profileCache->getData('next_delivery_date'),
                                'time_slot' => $objProductData->getData('delivery_time_slot'),
                            ];
                        } else {
                            $arrReturn[$addressId][$deliveryType]['delivery_date'] = [
                                'next_delivery_date' => $objProductData->getData('delivery_date'),
                                'time_slot' => $objProductData->getData('delivery_time_slot'),
                            ];
                        }
                    }
                }
                $product->setFinalPrice(null);
                $amount = $this->getRenderPrice($product, $objProductData->getData('qty'));
                $arrReturn[$addressId][$deliveryType]['product'][] = [
                    'name' => !$objProductData->getData('is_free_gift') ? $product->getData("name") : $objProductData->getData('name'),
                    'price' => $product->getData("price"),
                    'qty' => $objProductData->getData('qty'),
                    'unit_case' => $objProductData->getData('unit_case'),
                    'unit_qty' => $objProductData->getData('unit_qty'),
                    'gw_id' => $objProductData->getData('gw_id'),
                    'gift_message_id' => $objProductData->getData('gift_message_id'),
                    'instance' => $product,
                    'productcat_id' => $objProductData->getData('cart_id'),
                    'productcartInstance' => $objProductData,
                    'amount' => !$objProductData->getData('is_free_gift') ? $amount : 0,
                    'is_free_gift' => $objProductData->getData('is_free_gift'),
                    'is_spot' => $objProductData->getData('is_spot'),
                    'is_addition' => $objProductData->getData('is_addition')
                ];
            }
        }

        return $arrReturn;
    }

    public function getRenderPrice(\Magento\Catalog\Model\Product $product, $productQty = 1)
    {
        $amount = $this->_subHelperData->getProductPriceInProfileEditPage($product, $productQty);

        $unitQty = 1;
        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            $unitQty = $product->getUnitQty() ? $product->getUnitQty() : 1;
        }

        return $amount * $unitQty;
    }

    public function loadProductModelFromProfileItem($objProductData, $profile)
    {
        $productId = $objProductData->getData('product_id');
        try {
            $product = $this->_productRepository->getById($productId);
        } catch (\Exception $e) {
            return null;
        }

        $product->setData(
            ProductCart::PROFILE_STOCK_POINT_DISCOUNT_RATE_KEY,
            $objProductData->getData('stock_point_discount_rate')
        );

        $product->setData(
            SubscriptionHelperOrderData::SUBSCRIPTION_PROFILE_ID_FIELD_NAME,
            $profile->getProfileId()
        );
        $product->setData(
            SubscriptionHelperOrderData::IS_STOCK_POINT_PROFILE,
            ($profile->getData(SubscriptionHelperOrderData::PROFILE_STOCK_POINT_BUCKET_ID)
                && !$profile->getData('is_delete_stock_point')
            )
            || $profile->getData('riki_stock_point_id')
        );

        $product->setData(
            ProductCart::IS_SIMULATOR_PROFILE_ITEM_KEY,
            true
        );

        return $product;
    }

    public function groupDataByDeliveryType($deliveryData)
    {
        // Group it
        foreach ($deliveryData as $addressId => $arrOfDeliveryType) {
            $arrDeliveryType = array_keys($arrOfDeliveryType);

            foreach ($arrOfDeliveryType as $deliveryType => $arrInfo) {
                $deliveryTypeEdited = $this->deliveryHelper->getDeliveryTypeNameInAllowGroup(
                    $deliveryType,
                    $arrDeliveryType
                );

                if ($deliveryTypeEdited == $deliveryType) {
                    continue;
                }

                if (!isset($deliveryData[$addressId][$deliveryTypeEdited])) {
                    $deliveryData[$addressId][$deliveryTypeEdited] = $arrInfo;
                } else {
                    $deliveryData[$addressId][$deliveryTypeEdited]['product'] = array_merge(
                        $deliveryData[$addressId][$deliveryTypeEdited]['product'],
                        $deliveryData[$addressId][$deliveryType]['product']
                    );

                    unset($deliveryData[$addressId][$deliveryType]); // Remove after group.
                }
            }
        }

        return $deliveryData;
    }

    public function calculateAvailableEndDate($profileCache)
    {
        $currentDate = $this->_localeDate->date()->format('Y-m-d');
        $lastDeliveryDate = $this->getLastDeliveryDate($profileCache);
        $calendarPeriod = $this->calculateDeliveryDateHelper->getEditProfileCalendarPeriod() ?: 0;
        $maxCalendarPeriod = $this->_deliveryDate->getMaximumEditProfileCalendarPeriod();
        $maxAvailableDate = strtotime($maxCalendarPeriod . " month", strtotime($lastDeliveryDate));
        $maxDateTimestamp = strtotime($currentDate);
        if (strtotime($currentDate) < $maxAvailableDate) {
            $maxDateTimestamp = strtotime($calendarPeriod . " day", $maxDateTimestamp);
            if ($maxDateTimestamp > $maxAvailableDate) {
                $maxDateTimestamp = $maxAvailableDate;
            }
        }

        return $this->_localeDate->scopeDate(null, date('Y-m-d', $maxDateTimestamp));
    }

    public function getLastDeliveryDate($profileModel)
    {
        $profileId = $profileModel->getProfileId();
        $orderTimes = $profileModel->getData('order_times');

        if ($profileModel->getData('type') == 'tmp') {
            $orderTimes -= 1;
        }


        if ($profileModel->getData('type') == 'tmp') {
            $nextDeliveryDateOfMain = $this->profileFactory->create()->load($profileId)->getNextDeliveryDate();
            $lastDeliveryDate = date(
                'Y-m-d',
                strtotime($nextDeliveryDateOfMain)
            );
        } else {

            $lastDeliveryDate = $this->_helperProfile->getLastOrderDeliveryDateOfProfile($profileId, $orderTimes);
            if (is_null($lastDeliveryDate)) {
                $lastDeliveryDate = date(
                    'Y-m-d',
                    strtotime(
                        '-' . $profileModel->getData('frequency_interval')
                        . " "
                        . $profileModel->getData('frequency_unit'),
                        $this->_localeDate->scopeTimeStamp()
                    )
                );
            }
        }

        return $lastDeliveryDate;
    }

    public function getNextDeliveryDateOfMain($profileCache)
    {
        $profileId = $profileCache->getProfileId();
        $profileLink = $this->_helperProfile->getTmpProfile($profileId);
        if ($profileLink && $profileLink->getId()) {
            $profileOriginModel = $this->profileFactory->create()->load($profileId, null, true);
            if ($profileOriginModel->getId()) {
                return $profileOriginModel->getData('next_delivery_date');
            }
        }
        return null;
    }

    public function calculateAvailableStartDate($checkCalendar)
    {
        $startDate = time();
        foreach ($checkCalendar as $date) {
            if (strtotime($date) > $startDate) {
                $startDate = strtotime($date);
            }
        }

        return $this->_localeDate->scopeDate(null, date('Y-m-d', $startDate + 86400));
    }

    public function getListRuleIdsApplied($orderSimulator)
    {
        if (!$orderSimulator) {
            return false;
        }

        /** @var \Riki\Subscription\Model\Emulator\Order $orderSimulator */
        $orderSimulator->getAppliedRuleIds();

        $data = $this->_couponHelper->getRulesUsedLabel(explode(',', $orderSimulator->getAppliedRuleIds()));

        return $data;
    }

    public function clearCouponCodeNotApplied($profileCache)
    {
        if (isset($profileCache)) {
            $profileCache->setData('coupon_code', null);
        }
        $this->profileCacheRepository->save($profileCache);
    }

    public function getModifiableOrder($profileId)
    {
        $response = ['profile_id' => $profileId];
        $shippingAddress = null;
        $selectedShippingAddress = null;
        $stockPointExist = false;
        $addressStockPoint = null;
        $disableButtonSP = false;
        $profileDeliveryType = null;
        $allowStockPointDeliveryTypes = [
            \Riki\DeliveryType\Model\Delitype::COOL,
            \Riki\DeliveryType\Model\Delitype::NORMAl,
            \Riki\DeliveryType\Model\Delitype::DM
        ];

        // Set registry
        $objProfile = $this->_helperProfile->load($profileId);
        $course = $objProfile->getCourseData();
        $profileCache = $this->getProfileEntity($profileId, true);

        $frequencyUnit = $profileCache->getFrequencyUnit();
        $frequencyInterval = $profileCache->getFrequencyInterval();

        if (!is_null($this->_registry->registry('subscription_profile'))) {
            $this->_registry->unregister('subscription_profile');
        }
        $this->_registry->register('subscription_profile', $profileCache);
        if (!is_null($this->_registry->registry('subscription_profile_obj'))) {
            $this->_registry->unregister('subscription_profile_obj');
        }
        $this->_registry->register('subscription_profile_obj', $objProfile);

        $courseId = $objProfile->hasData('course_id') ? $objProfile->getData('course_id') : 0;
        $frequencyId = $objProfile->getSubProfileFrequencyID();

        if (!is_null($this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID))) {
            $this->_registry->unregister(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID);
        }
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);
        if (!is_null($this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID))) {
            $this->_registry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);
        }
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);



        //
        $courseSettings = $this->getCourseSetting($profileCache->getData("course_id"));
        $isAllowChangeNextDelivery = $courseSettings['is_allow_change_next_delivery'];
        $isAllowChangeAddress = $courseSettings['is_allow_change_address'];
        $isAllowSpToChangeDl = $profileCache->getData("stock_point_delivery_type") == Profile::SUBCARRIER;

        /*Simulator order info*/
        $simulatorOrder = $this->getSimulatorOrderOfProfile($profileCache);

        if ($simulatorOrder == false) {
            $response['subtotal'] = $this->profileFactory->create()->load($profileId)->getTotalProductsPrice();
        } else {
            $response['subtotal'] = $simulatorOrder->getData('base_subtotal_incl_tax');
        }
        $response['delivery_date'] = $profileCache->getData('next_delivery_date');
        $response['deadline'] = date("Y-m-d", strtotime($profileCache->getData('next_order_date')) - 86400);

        $deliveryInformation = $this->getListProductByAddressAndByDeliveryType($simulatorOrder, $profileCache);
        $groupedDeliveryInformation = $this->groupDataByDeliveryType($deliveryInformation);
        $isShowStockPoint = $this->checkShowStockPoint($simulatorOrder, $profileCache, $groupedDeliveryInformation);
        foreach ($groupedDeliveryInformation as $addressId => $arrInfoWithDL) {
            foreach ($arrInfoWithDL as $deliveryType => $arrDetailDL) {
                $stockPointExist = $this->validateStockPointProduct->checkProfileExistStockPoint($profileCache);

                if ($stockPointExist) {
                    $isAllowChangeNextDelivery = false;
                }

                $arrProductByAddress = $arrDetailDL;

                /*Calculate Available end date*/
                $objMaxDate = $this->calculateAvailableEndDate($profileCache);
                $maxDate = $objMaxDate->format('Y-m-d');

                if ($isAllowChangeNextDelivery == 1) {
                    $isDisableDatePicker = false;
                } else {
                    $isDisableDatePicker = true;
                }

                $bufferDay = null;
                if (isset($course['exclude_buffer_days']) && $course['exclude_buffer_days']) {
                    $bufferDay = 0;
                }

                // Get Calendar Config
                $nextDeliveryDateMain = $this->getNextDeliveryDateOfMain($profileCache);
                $_checkCalendar = $this->calculateDeliveryDateHelper->getCalendar($addressId, $arrProductByAddress,
                    $deliveryType, $bufferDay, $nextDeliveryDateMain);
                /*Calculate Available start date*/
                $objMinDate = $this->calculateAvailableStartDate($_checkCalendar);

                if ($objMaxDate->getTimestamp() < $objMinDate->getTimestamp()) {
                    $calendarPeriodForEdit = $this->calculateDeliveryDateHelper->getEditProfileCalendarPeriod() ?: 0;
                    $objMaxDate = clone $objMinDate;
                    $objMaxDate->add(new \DateInterval(sprintf('P%sD', $calendarPeriodForEdit)));
                    $maxDate = $objMaxDate->format('Y-m-d');
                }
                $response['calendar_option'] = [
                    'unavailable_date' => $_checkCalendar,
                    'min_date' => $objMinDate->format('Y-m-d'),
                    'max_date' => $maxDate,
                    'is_disable' => $isDisableDatePicker,
                ];
                $shippingAddress = $this->getAddressDetail($addressId)['riki_nickname'];
                $selectedShippingAddress = $addressId;
                // timeslot
                $response['calendar_option']['delivery_time_list'] = $this->_deliveryDate->getListTimeSlot();
                $response['calendar_option']['delivery_time_selected'] = $arrProductByAddress['delivery_date']['time_slot'] ?? -1;

                // next of next
                $deliveryDate = $arrProductByAddress['delivery_date']['next_delivery_date']!=null ? $arrProductByAddress['delivery_date']['next_delivery_date'] : $profileCache->getData('next_delivery_date');
                $deliveryDate = strtotime($frequencyInterval." ".$frequencyUnit, strtotime($deliveryDate));
                $nextOfNextDeliveryDate = date('Y-m-d', $deliveryDate);
                $response['calendar_option']['next_of_next_delivery_date'] = $nextOfNextDeliveryDate;

                // Delivery type
                $profileDeliveryType = $deliveryType;
                break;
            }
        }
        foreach ($groupedDeliveryInformation as $addressId => $arrInfoWithDL) {
            foreach ($arrInfoWithDL as $deliveryType => $arrDetailDL) {
                $arrProduct = $arrDetailDL['product'];
                foreach ($arrProduct as $arrP) {
                    $productModel = $arrP['instance'];
                    $qty = $arrP['unit_case'] == 'CS' ? (int)$arrP['qty'] / (int)$arrP['unit_qty'] : (int)$arrP['qty'];
                    $response['product_list'][] = [
                        'id' => $productModel->getId(),
                        'name' => $arrP['name'],
                        'qty' => $qty,
                        'thumbnail' => $this->_imageBuilder->create($productModel, 'cart_page_product_thumbnail')->getImageUrl()
                    ];
                }
                $stockPointExist = $this->validateStockPointProduct->checkProfileExistStockPoint($profileCache);
                $disableButtonSP = $this->validateStockPointProduct->disableButtonSP($profileCache);
                if ($stockPointExist) {
                    $isAllowChangeAddress = false;
                    if ($isAllowSpToChangeDl && $isAllowChangeNextDelivery == true) {
                        $isAllowChangeNextDelivery = true;
                    } else {
                        $isAllowChangeNextDelivery = false;
                    }
                    $addressStockPoint = $this->validateStockPointProduct->getAddressStockPoint($profileCache);
                    $canShowStockPointAddress = $this->validateStockPointProduct->canShowStockPointAddress($profileCache);
                    if ($addressStockPoint == null && $canShowStockPointAddress ) {
                        $addressStockPoint = $this->stockPointDataHelper->getArrDataAddressStockPoint($profileId);
                        $addressStockPoint['id'] = $addressId;
                    }
                }
            }
        }
        $response['shipping_address'] = $shippingAddress;
        $addrStockPoint = [
            'address_id' => $addressStockPoint['id'],
            'address_name' => $addressStockPoint['lastName'] . $addressStockPoint['firstName'],
            'address_text' => $addressStockPoint['addressFull'],
            'address_telephone' => $addressStockPoint['telephone'],
        ];
        // delivery message
        $nextDeliveryDateCalculationOption = $courseSettings['next_delivery_date_calculation_option'];
        if($this->isDayOfWeekAndIntervalUnitMonthAndNotStockPoint($frequencyUnit, $nextDeliveryDateCalculationOption, $isShowStockPoint)){
            $response['calendar_option']['delivery_message'] =
                $this->getDeliveryMessage($profileCache->getData('next_delivery_date'),
                    date('w', strtotime($profileCache->getData('day_of_week'))),
                    $profileCache->getData('nth_weekday_of_month'));
        } else{
            $response['calendar_option']['delivery_message'] = null;
        }

        $this->saveVerificationDataToCache($profileId, $response, self::TYPE_DELIVERY_DATE);

        $typeShowBlock = $this->validateStockPointProduct->getTypeShowBlock();
        if($typeShowBlock == 1 && $disableButtonSP){
            $typeShowBlock = null;
        }
        $this->saveVerificationDataToCache($profileId, null, self::TYPE_SHIPPING_ADDRESS, [
            'selected_shipping_address' => "$selectedShippingAddress",
            'is_disable' => !$courseSettings['is_allow_change_address'],
            'stockpoint_option' => [
                'stockpoint_exist' => $stockPointExist,
                'display_remove_stockpoint_button' => $stockPointExist && !$disableButtonSP,
                'display_add_stockpoint_button' => ($isShowStockPoint && !$disableButtonSP
                    && in_array($profileDeliveryType, $allowStockPointDeliveryTypes)),
                'stockpoint_map_url' => ($isShowStockPoint && !$disableButtonSP
                    && in_array($profileDeliveryType, $allowStockPointDeliveryTypes)) ? $this->buildStockPointPostData->getUrlPostMap() : null,
                'stockpoint_address' => $addrStockPoint,
                'type_show_block' => $typeShowBlock
            ]
        ]);
        // payment method
        if($objProfile->getPaymentMethod()) {
            $response['payment_method']
                = __($this->paymentHelper->getPaymentMethodList()[$objProfile->getPaymentMethod()]);
        } else {
            $response['payment_method'] = null;
        }

        // frequency
        $response['frequency'] = __($objProfile->getFrequencyInterval()) . __($objProfile->getFrequencyUnit());

        // point used option
        $pointAllowUsed = $objProfile->getPointAllowUsed();
        if($pointAllowUsed == 0){
            $response['point_options'] = __('Not use point');
        } else if($pointAllowUsed == 1){
            $response['point_options'] = __('Automatically use all points');
        } else if($pointAllowUsed == 2){
            $response['point_options'] = __('Automatically redeem a specified maximum number of points');
        }
        return $response;
    }

    public function getModifiableOrderNoSimulate($profileList)
    {
        $productCartCollection = $this->productCartCollectionFactory->create();
        $profileTable = $productCartCollection->getConnection()->getTableName('subscription_profile');
        $frequencyTable = $productCartCollection->getConnection()->getTableName('subscription_frequency');
        $paymentFeeTable = $productCartCollection->getConnection()->getTableName('payment_fee');
        $subcourseTable = $productCartCollection->getConnection()->getTableName('subscription_course');
        $profileLinkTable = $productCartCollection->getConnection()->getTableName('subscription_profile_link');
        $productCartCollection->getSelect()->joinLeft(['profile' => $profileTable], 'main_table.profile_id = profile.profile_id')
        ->joinLeft(['frequency' => $frequencyTable],'profile.frequency_interval = frequency.frequency_interval and profile.frequency_unit = frequency.frequency_unit')
        ->joinLeft(['payment' => $paymentFeeTable], 'profile.payment_method = payment.payment_code', 'payment_name')
        ->joinLeft(['subcourse' => $subcourseTable], 'profile.course_id = subcourse.course_id', ['terms_of_use', 'subscription_type'])
        ->joinLeft(['profile_link' => $profileLinkTable], 'profile.profile_id = profile_link.profile_id', ['linked_profile_id']);
        $productCartCollection->addFieldToFilter('profile.profile_id', ['in' => $profileList]);
        $profileItemList = [];
        $productIdList = [];
        $shippingAddressIdList = [];
        $response = [];

        foreach($productCartCollection as $productCart){
            $profileId = $productCart->getData('profile_id');
            $courseSettings = $this->getCourseSetting($productCart->getData('course_id'));
            $response[$profileId]['delivery_date'] = $productCart->getData('next_delivery_date');
            $response[$profileId]['deadline'] = date("Y-m-d", strtotime($productCart->getData('next_order_date')) - 86400);
            $productId = $productCart->getData('product_id');
            $profileItemList[$profileId][$productId] = [
                'id' => $productId,
                'qty' => $productCart->getData('qty'),
                'shipping_address' => $productCart->getData('shipping_address_id'),
                'product_type' => $productCart->getData('product_type'),
                'parent_item_id' => $productCart->getData('parent_item_id')
            ];
            if(!in_array($productId, $productIdList)){
                $productIdList[] = $productId;
            }
            if(!in_array($productCart->getData('shipping_address_id'), $productIdList)){
                $shippingAddressIdList[] = $productCart->getData('shipping_address_id');
            }
            if($productCart->getData('payment_method')){
                if($productCart->getData('payment_method') == 'paygent'){
                    $response[$profileId]['payment_method'] = 'クレジットカード支払い（前回使用）';
                } else {
                    $response[$profileId]['payment_method'] = __($productCart->getData('payment_name'));
                }
                $response[$profileId]['payment_method_value'] = $productCart->getData('payment_method');
            } else {
                $response[$profileId]['payment_method'] = null;
                $response[$profileId]['payment_method_value'] = null;
            }

            $response[$profileId]['is_monthly_fee'] = $productCart['subscription_type'] == CourseType::TYPE_MONTHLY_FEE;
            $response[$profileId]['is_allow_change_next_delivery'] = !!$courseSettings['is_allow_change_next_delivery'];
            $response[$profileId]['is_allow_change_payment_method'] = !!$courseSettings['is_allow_change_payment_method'];
            $response[$profileId]['is_allow_change_address'] = !!$courseSettings['is_allow_change_address'];
            $response[$profileId]['is_allow_change_item'] = !!$courseSettings['is_allow_change_product'] && !!$courseSettings['is_allow_change_qty'];
            $response[$profileId]['is_stockpoint'] = !!$productCart->getData('stock_point_profile_bucket_id');
            if($response[$profileId]['is_stockpoint']){
                if($productCart->getData('stock_point_delivery_type') != Profile::SUBCARRIER){
                    $response[$profileId]['is_allow_change_next_delivery'] = false;
                }
            }
            $response[$profileId]['stockpoint_delivery_type'] = $productCart->getData('stock_point_delivery_type');
            $response[$profileId]['shipping_address'] = $productCart->getData('shipping_address_id');
            // frequency
            $response[$profileId]['frequency_label'] = __($productCart->getFrequencyInterval()) . __($productCart->getFrequencyUnit());
            $response[$profileId]['frequency_id'] = $productCart->getData('frequency_id');
            //term of use
            $termsOfUse =  $this->getTermsOfUseDownloadUrl($productCart->getData('terms_of_use'));
            if($termsOfUse) {
                $response[$profileId]['terms_of_use'] = $this->scopeConfig->getValue('web/secure/base_url') . $termsOfUse;
            } else {
                $response[$profileId]['terms_of_use'] = null;
            }
            // authorized fail
            if($productCart->getData('linked_profile_id') && !$productCart->getData('payment_method')){
                $response[$profileId]['authorized_fail_message'] = $this->getAuthorizedFailMessage($profileId);
            } else{
                $response[$profileId]['authorized_fail_message'] = null;
            }
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('entity_id', $productIdList, 'in')->create();
        $productItemList = $this->_productRepository->getList($searchCriteria)->getItems();

        foreach($profileList as $profileId){
            $total = 0;
            if(key_exists($profileId, $profileItemList)) {
                foreach ($profileItemList[$profileId] as $pid => $p) {
                    foreach ($productItemList as $pr) {
                        if ($pid == $pr->getId()) {
                            $productModel = $pr;
                            break;
                        }
                    }
                    $finalPrice = $productModel->getFinalPrice($p['qty']);
                    if ($p['product_type'] != 'bundle') {
                        $amount = $this->adjustmentCalculator->getAmount($finalPrice, $productModel)->getValue();
                    } else {
                        $amount = $this->_subHelperData->getBundleMaximumPrice($productModel);
                    }
                    $amount = $this->priceCurrency->format(
                        $amount,
                        false,
                        \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION
                    );
                    $amount = $this->localeFormat->getNumber($amount);
                    $total += $amount * $p['qty'];
                    if ($productModel->getData('unit_sap') == 1
                        || ($productModel->getData('case_display') == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY
                            && $productModel->getData('unit_qty') > 1)) {
                        $profileItemList[$profileId][$pid]['qty'] = $productModel->getData('unit_qty') > 1 ? (int)$p['qty'] / (int)$productModel->getData('unit_qty') : (int)$p['qty'];
                    }
                    $profileItemList[$profileId][$pid]['name'] = $productModel->getName();
                    $profileItemList[$profileId][$pid]['thumbnail'] = $this->_imageBuilder->create($productModel, 'cart_page_product_thumbnail')->getImageUrl();
                }
                $response[$profileId]['subtotal'] = $total;
                $response[$profileId]['product_list'] = $profileItemList[$profileId];
            } else {
                $response[$profileId]['subtotal'] = 0;
                $response[$profileId]['product_list'] = [];
            }
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('entity_id', $shippingAddressIdList, 'in')->create();
        $customerAddressList = $this->_customerAddressRepository->getList($searchCriteria)->getItems();
        foreach($response as $id => $profile){
            $shippingAddressItem = null;
            foreach($customerAddressList as $customerAddress){
                if(key_exists('shipping_address', $profile)) {
                    if ($profile['shipping_address'] == $customerAddress->getId()) {
                        $shippingAddressItem = $customerAddress;
                    }
                } else {
                    $shippingAddressItem = null;
                }
            }
            if ($shippingAddressItem && $rikiNicknameObj = $shippingAddressItem->getCustomAttribute('riki_nickname')) {
                $response[$id]['shipping_address'] = $rikiNicknameObj->getValue();
            } else {
                $response[$id]['shipping_address'] = '';
            }

            //reformat product list
            $finalProductList = [];
            foreach($profile['product_list'] as $pid => $p){
                $finalProductList[] = [
                    'id' => $pid,
                    'name' => $p['name'],
                    'qty' => $p['qty'],
                    'thumbnail' => $p['thumbnail']
                ];
            }
            $response[$id]['product_list'] = $finalProductList;
        }

        return $response;
    }

    public function getTermsOfUseDownloadUrl($termsOfUse) {
        if ($termsOfUse) {
            $mediaDirectory = $this->directoryList->getUrlPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

            return '/' . $mediaDirectory . '/' .
                \Riki\SubscriptionCourse\Controller\Adminhtml\Course\Save::UPLOAD_TARGET . '/' . $termsOfUse;
        }
        return null;
    }

    protected function getFrequencyList($profileId){
        $list = [];
        $course = $this->courseFactory->create()->load($this->profileApiRepository->get($profileId)->getCourseId());
        if ($course->getId()) {
            $frequencies = $course->getFrequencyEntities();
            foreach ($frequencies as $frequency) {
                if (isset($frequency['frequency_id'])) {
                    $list[] = [
                        'frequency_id' => (int)$frequency['frequency_id'],
                        'label' => __($frequency['frequency_interval']) . __($frequency['frequency_unit'])
                    ];
                }
            }
        }
        return $list;
    }

    public function validateStockPoint($objProfileCache){
        $message = null;
        $productCartCache = $this->profileSaveHelper->preparedProfileCartItemData($objProfileCache);
        if(!$productCartCache){
            $productCartCache = [];
        }
        if ($objProfileCache) {
            $rikiStockPoint = $objProfileCache->getData('riki_stock_point_id');
            $existStockPoint = $objProfileCache->getData('stock_point_profile_bucket_id');
            if ((int)$existStockPoint >0 || $rikiStockPoint) {
                $isValid = true;

                if (
                !(
                    $objProfileCache->getData('payment_method') == \Bluecom\Paygent\Model\Paygent::CODE ||
                    $objProfileCache->getData('payment_method') == \Bluecom\Paygent\Model\Paygent::CODE_NEW
                )
                ) {
                    $isValid = false;
                }
                /**
                 * Check all allow stock point
                 */
                $errorStockPoint = $this->profileSaveHelper->_validateAllProductAllowStockPoint($productCartCache, $objProfileCache);
                if (!empty($errorStockPoint)) {
                    $text1 = "Stock Point is not allowed for these products [%s].";
                    $text2 = "Please remove them from cart before you choose to deliver with Stock Point.";
                    return [
                        'status' => false,
                        'message' => sprintf(__($text1.$text2), implode(',', $errorStockPoint))
                    ];
                }

                /**
                 * All products are in stock on Hitachi
                 */
                if (!$this->profileSaveHelper->_validateProductInventoryForStockPoint($existStockPoint)) {
                    $isValid = false;
                }

                /**
                 * Check payment method = paygent
                 */
                if ($objProfileCache->getData('payment_method') != \Bluecom\Paygent\Model\Paygent::CODE) {
                    $isValid = false;
                }

                /**
                 * Check delivery type address
                 */
                $deliveryType = $this->profileSaveHelper->getProductDeliveryTypes();
                if (!$this->validateStockPointProduct->validateDeliveryTypeAddress($deliveryType)) {
                    $isValid = false;
                }

                if (!$isValid) {
                    return [
                        'status' => false,
                        'message' => __("Sorry, MACHI ECO flights can not be used with the items you purchase / payment method.")
                    ];
                }
            }
        }
        return [
            'status' => true,
            'message' => $message
        ];
    }

    public function validateCustomerAddress($profileCache, $arrAddress = null){
        $arrParams['payment_method'] = $profileCache->getData('payment_method');
        $arrParams['address'] = $arrAddress;
        if(!$this->profileSaveHelper->checkChangeAddressType($arrParams, $profileCache)){
            return [
                'status' => false,
                'message' => __('Please change payment method from COD to Paygent to update all changes.')
            ];
        }
        return [
            'status' => true,
            'message' => null
        ];
    }

    public function checkShowStockPoint($simulatorOrderObject, $profileModelData, $dlInformation)
    {
        $isShowStockPoint = false;

        /** start process logic stock point */
        $listDelivery = [];
        foreach ($dlInformation as $carts) {
            foreach ($carts as $deliveryType => $infoDelivery) {
                $listCartData = [];
                foreach ($infoDelivery["product"] as $product) {
                    if (isset($product['instance']) && !empty($product['instance'])) {
                        /**
                         * Does not validate for free gift
                         */
                        $isFreeGift = (isset($product['is_free_gift']) && $product['is_free_gift']) ? true : false;
                        if ($isFreeGift) {
                            continue;
                        }
                        $listCartData[] = [
                            'product' => $product['instance'],
                            'qty' => $product["qty"]
                        ];
                    } else {
                        return $isShowStockPoint;
                    }
                }
                $listDelivery[$deliveryType] = $listCartData;
            }
        }

        if (!empty($listDelivery) || count(array_unique($listDelivery)) == 1) {
            foreach ($listDelivery as $deliveryType => $listCartData) {
                if (!empty($listCartData)) {
                    /** check 4 is to show button stock point */
                    $isShowStockPoint = $this->validateStockPointProduct->isShowStockPoint(
                        $simulatorOrderObject,
                        $deliveryType,
                        $listCartData,
                        $profileModelData
                    );
                }
            }
        }

        return $isShowStockPoint;
    }

    public function getStockPointReqData($profileId, $shippingAddressId, $returnUrl)
    {
        /* If profileId is tmp, will return main profileId else return itself*/
        $returnProfile = $this->_helperProfile->getMainFromTmpProfile($profileId);

        $response = null;

        if ($profile = $this->profileCacheRepository->getProfileDataCache($profileId)) {
            $customerId = $profile->getCustomerId();
            $address = $this->_customerAddressRepository->getById($shippingAddressId);
            if ($profile && $customerId == $profile->getData('customer_id') && $address) {
                $fullAddress = [
                    '〒 ' . $address->getPostcode(),
                    $address->getRegion()->getRegion(),
                    trim(implode(" ", $address->getStreet()))
                ];
                $regionName = $address->getRegion()->getRegion();
                $rawDataValue = [
                    "postcode" => $address->getPostcode(),
                    "prefecture" => $regionName,
                    "address" => implode(" ", $fullAddress),
                    "telephone" => $address->getTelephone(),
                    "return_url" => $this->_urlBuilder->getUrl('subscriptions/profile/webAppStockPoint'),
                    "magento_data" => [
                        "profile_id" => $returnProfile,
                        "return_url" => $returnUrl
                    ]
                ];

                try {
                    $this->buildStockPointPostData->setPostDataRequest($rawDataValue);

                    /**
                     * Set nonce data to profile
                     */
                    $nonce = $this->buildStockPointPostData->getNonceData();
                    if (isset($profile)) {
                        $profile->setData('riki_stock_point_nonce', $nonce);
                        $this->processShippingAddressStockPoint($shippingAddressId, $profile);
                    }
                    $this->saveVerificationDataToCache($profileId, null, self::TYPE_NONCE, $nonce);
                    $response =  $this->buildStockPointPostData->getPostDataRequestGenerate();
                    $this->profileCacheRepository->save($profile);
                } catch (\Exception $e) {
                    $response = null;
                }
            }
        }

        return $response;
    }

    public function processShippingAddressStockPoint($shippingAddressId, $profile)
    {
        $productCart = $profile->getData('product_cart');
        if (!empty($productCart)) {
            $shippingAddressBeforeChange = null;
            foreach ($productCart as $key => $item) {
                $shippingAddressBeforeChange = $productCart[$key]['shipping_address_id'];
                $productCart[$key]['shipping_address_id'] = $shippingAddressId;
            }
            $profile->setData('product_cart', $productCart);
            $profile->setData(
                'riki_shipping_address_before_change',
                $shippingAddressBeforeChange
            );
        }
    }

    public function getNewAddressUrl(){
        return $this->_urlBuilder->getUrl('customer/address/new/');
    }

    public function getCustomerAddressByText($addressId)
    {
        // Get all Address of current customer

        $objAddress = $this->_customerAddressRepository->getById($addressId);
        $arrAddr = [
            '〒 ' . $objAddress->getPostcode(),
            $objAddress->getRegion()->getRegion(),
            trim(implode(" ", $objAddress->getStreet()))
        ];
        $arrReturn[$objAddress->getId()] = implode(" ", $arrAddr);

        return $arrReturn[$addressId];
    }

    /**
     * @param $simulatorOrder
     * @param $profileCache
     * @return mixed
     */
    public function getGroupedDeliveryInformation($simulatorOrder, $profileCache)
    {
        if(!isset($this->simpleCache['groupedDeliveryInformation'])) {
            $deliveryInformation = $this->getListProductByAddressAndByDeliveryType($simulatorOrder, $profileCache);
            $groupedDeliveryInformation = $this->groupDataByDeliveryType($deliveryInformation);

            $this->simpleCache['groupedDeliveryInformation'] = $groupedDeliveryInformation;
        }

        return $this->simpleCache['groupedDeliveryInformation'];
    }


    /**
     * @param $simulatorOrder
     * @param $profileCache
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @return array
     * @throws \Exception
     */
    public function getProfileCalendarOption($simulatorOrder, $profileCache, $course)
    {
        $calendarOption = [];

        $courseSettings = $course->getSettings();
        $isAllowChangeNextDelivery = $courseSettings['is_allow_change_next_delivery'];

        $groupedDeliveryInformation = $this->getGroupedDeliveryInformation($simulatorOrder, $profileCache);

        $frequencyUnit = $profileCache->getFrequencyUnit();
        $frequencyInterval = $profileCache->getFrequencyInterval();

        $isShowStockPoint = $this->checkShowStockPoint($simulatorOrder, $profileCache, $groupedDeliveryInformation);
        foreach ($groupedDeliveryInformation as $addressId => $arrInfoWithDL) {
            foreach ($arrInfoWithDL as $deliveryType => $arrDetailDL) {
                $stockPointExist = $this->validateStockPointProduct->checkProfileExistStockPoint($profileCache);

                if ($stockPointExist) {
                    $isAllowChangeNextDelivery = false;
                }

                $arrProductByAddress = $arrDetailDL;

                /*Calculate Available end date*/
                $objMaxDate = $this->calculateAvailableEndDate($profileCache);
                $maxDate = $objMaxDate->format('Y-m-d');

                if ($isAllowChangeNextDelivery == 1) {
                    $isDisableDatePicker = false;
                } else {
                    $isDisableDatePicker = $profileCache->getData("stock_point_delivery_type") != Profile::SUBCARRIER;
                }

                $bufferDay = null;
                if ($course->getData('exclude_buffer_days')) {
                    $bufferDay = 0;
                }

                // Get Calendar Config
                $nextDeliveryDateMain = $this->getNextDeliveryDateOfMain($profileCache);
                $_checkCalendar = $this->calculateDeliveryDateHelper->getCalendar($addressId, $arrProductByAddress,
                    $deliveryType, $bufferDay, $nextDeliveryDateMain);
                /*Calculate Available start date*/
                $objMinDate = $this->calculateAvailableStartDate($_checkCalendar);

                if ($objMaxDate->getTimestamp() < $objMinDate->getTimestamp()) {
                    $calendarPeriodForEdit = $this->calculateDeliveryDateHelper->getEditProfileCalendarPeriod() ?: 0;
                    $objMaxDate = clone $objMinDate;
                    $objMaxDate->add(new \DateInterval(sprintf('P%sD', $calendarPeriodForEdit)));
                    $maxDate = $objMaxDate->format('Y-m-d');
                }
                $calendarOption = [
                    'unavailable_date' => $_checkCalendar,
                    'min_date' => $objMinDate->format('Y-m-d'),
                    'max_date' => $maxDate,
                    'is_disable' => $isDisableDatePicker,
                ];


                // timeslot
                $calendarOption['delivery_time_list'] = $this->_deliveryDate->getListTimeSlot();
                $calendarOption['delivery_time_selected'] = $arrProductByAddress['delivery_date']['time_slot'] ?? "-1";

                $arrThreeDelivery = $this->_helperProfile->calculateNextDelivery($profileCache);
                $nextOfNextDeliveryDate = $arrThreeDelivery[1]['delivery_date'];
                $nextOfNextDeliveryDateAfter = $arrThreeDelivery[2]['delivery_date'];

                $calendarOption['next_of_next_delivery_date'] = date("d-m-Y", strtotime($nextOfNextDeliveryDate));
                $calendarOption['next_of_next_delivery_date_after'] = date("d-m-Y", strtotime($nextOfNextDeliveryDateAfter));

                // delivery message
                $nextDeliveryDateCalculationOption = $courseSettings['next_delivery_date_calculation_option'];
                if($this->isDayOfWeekAndIntervalUnitMonthAndNotStockPoint($frequencyUnit, $nextDeliveryDateCalculationOption, $isShowStockPoint)){
                    $calendarOption['delivery_message'] =
                        $this->getDeliveryMessage($profileCache->getData('next_delivery_date'),
                            date('w', strtotime($profileCache->getData('day_of_week'))),
                            $profileCache->getData('nth_weekday_of_month'));
                } else{
                    $calendarOption['delivery_message'] = null;
                }

                break;
            }
        }
        $this->saveVerificationDataToCache($profileCache->getProfileId(), ['calendar_option' => $calendarOption], self::TYPE_DELIVERY_DATE);

        return $calendarOption;
    }

    public function getCourseSetting($courseId)
    {
        $objCourse = $this->_subCourseHelper->loadCourse($courseId);

        if (empty($objCourse) || empty($objCourse->getId())) {
            return [];
        }

        return $objCourse->getSettings();
    }

    public function getSimulatorOrderOfProfile($profileCache)
    {
        if ($profileCache) {
            $simulatorOrder = $this->helperSimulator->createSimulatorOrderHasData($profileCache);
            if ($simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {

                /** update data for profile cache simulate  */
                $this->profileIndexerHelper->updateDataProfileCache($profileCache->getProfileId(), $simulatorOrder);
                return $simulatorOrder;
            }
        }
        return false;
    }

    public function getAddressDetail($addressId, $customerId = null)
    {
        try {
            $customerAddressObj = $this->_customerAddressRepository->getById($addressId);
        } catch (\Exception $e) {
            $customerAddressObj = null;
        }

        if ($customerAddressObj instanceof \Magento\Customer\Model\Data\Address) {
            $rikiNickname = '';
            if ($rikiNicknameObj = $customerAddressObj->getCustomAttribute('riki_nickname')) {
                $rikiNickname = $rikiNicknameObj->getValue();
            }

            $rikiFirstName = $customerAddressObj->getFirstname();
            $rikiLastname = $customerAddressObj->getLastname();

            if ($rikiTypeAddressObj = $customerAddressObj->getCustomAttribute('riki_type_address')) {
                $rikiTypeAddress = $rikiTypeAddressObj->getValue();
            } else {
                $rikiTypeAddress = '';
            }
        } else {
            $rikiNickname = $rikiLastname = $rikiFirstName = $rikiTypeAddress = '';
        }

        $arrReturn['id'] = $addressId;
        $arrReturn['riki_nickname'] = $rikiNickname;
        $arrReturn['riki_firstname'] = $rikiFirstName;
        $arrReturn['riki_lastname'] = $rikiLastname;
        $arrReturn['riki_type_address'] = $rikiTypeAddress;
        $arrReturn['telephone'] = $customerAddressObj ? $customerAddressObj->getTelephone() : '';
        $arrReturn['riki_address_text'] = $this->getCustomerAddressByText($addressId);
        $arrReturn['riki_edit_url'] = $this->getAddressEditUrl($addressId);
        if($this->isCNCorCIS($customerId)){
            if ($rikiTypeAddress != AddressType::SHIPPING) {
                $arrReturn['riki_edit_url'] = null;
            }
        }
        return $arrReturn;
    }

    public function isCNCorCIS($customerId){
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('entity_id', $customerId, 'eq')->create();
        $customerList = $this->customerRepository->getList($searchCriteria)->getItems();
        foreach($customerList as $customer) {
            if ($customer->getCustomAttribute('membership') instanceof \Magento\Framework\Api\AttributeValue) {
                $membershipStr = $customer->getCustomAttribute('membership')->getValue();
                if ($membershipStr != '') {
                    $memberships = explode(',', $membershipStr);
                    foreach ($memberships as $membership) {
                        if ($membership == Membership::CODE_5 || $membership == Membership::CODE_6) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function getAddressEditUrl($addressId){
        $typeId = $this->checkAddressType($addressId);
        if ($typeId == ProfileEditBlock::ADDRESS_TYPE_HOME_NO_COMPANY) {
            $editUrl = $this->_subCourseHelper->getStoreConfig(
                self::ADDRESS_LINK_EDIT_HOME_NO_COMPANY
            );
        } elseif ($typeId == ProfileEditBlock::ADDRESS_TYPE_HOME_HAVE_COMPANY) {
            $editUrl = $this->_subCourseHelper->getStoreConfig(
                self::ADDRESS_LINK_EDIT_HOME_HAVE_COMPANY
            );
        } elseif ($typeId == ProfileEditBlock::ADDRESS_TYPE_AMBASSADOR_COMPANY) {
            $editUrl = $this->_subCourseHelper->getStoreConfig(
                self::ADDRESS_LINK_EDIT_AMBASSADOR_COMPANY
            );
        } else {
            $editUrl = $this->_urlBuilder->getUrl('customer/address/edit', ['id' => $addressId]);
        }
        return $editUrl;
    }

    public function checkAddressType($shippingAddressId)
    {
        try {
            $customerAddress = $this->_customerAddressRepository->getById($shippingAddressId);
            if ($customerAddress->getCustomAttribute('riki_type_address')->getValue() == 'home'
                && $customerAddress->getCompany() == null
            ) {
                return ProfileEditBlock::ADDRESS_TYPE_HOME_NO_COMPANY;
            } elseif ($customerAddress->getCustomAttribute('riki_type_address')->getValue() == 'home'
                && $customerAddress->getCompany() != null
            ) {
                return ProfileEditBlock::ADDRESS_TYPE_HOME_HAVE_COMPANY;
            } elseif ($customerAddress->getCustomAttribute('riki_type_address')->getValue() == 'company') {
                return ProfileEditBlock::ADDRESS_TYPE_AMBASSADOR_COMPANY;
            } else {
                return ProfileEditBlock::ADDRESS_TYPE_ANOTHER;
            }
        } catch (\Exception $e) {
            return ProfileEditBlock::ADDRESS_TYPE_ANOTHER;
        }
    }

    public function getAllAddress($customerId)
    {
        $objCollection = $this->customerAddressCollectionFactory->create()->addAttributeToFilter("parent_id", $customerId);
        $objCollection->load();
        $arrReturn = [];
        foreach ($objCollection as $objAddress) {
            $arrReturn[] = $this->getAddressDetail($objAddress->getData('entity_id'), $customerId);
        }

        return $arrReturn;
    }

    public function isDayOfWeekAndIntervalUnitMonthAndNotStockPoint($unitFrequency, $nextDeliveryDateCalculationOption, $isStockPoint){
        if ($unitFrequency === 'month' && $nextDeliveryDateCalculationOption === 'day_of_week' && !$isStockPoint) {
            return true;
        }

        return false;
    }

    public function getDeliveryMessage($deliveryDate, $profileDayOfWeek, $profileNthWeekdayOfMonth){
        $deliveryMessage = '';
        if ($deliveryDate != '') {
            if (
                $profileNthWeekdayOfMonth != ''
                && $profileDayOfWeek != ''
            ) {
                $nthWeekdayOfMonth = $this->calculateNthWeekdayOfMonth($profileNthWeekdayOfMonth);
                $dayOfWeek = $this->getDayOfWeek($profileDayOfWeek);
            } else {
                $nthWeekdayOfMonth = $this->calculateNthWeekdayOfMonth($deliveryDate);
                $dayOfWeek = $this->getDayOfWeek($deliveryDate);
            }
            $deliveryMessage = '<span class="text-green">' . $nthWeekdayOfMonth . $dayOfWeek . '</span>' .
                '<span class="text-black">'. __('every') . '</span>';
        } else {
            $deliveryMessage .= __('');
        }
        return $deliveryMessage;
    }

    public function calculateNthWeekdayOfMonth($date_string){
        if (is_numeric($date_string)) {
            return $this->getNthWeekdayOfMonthTranslate()[$date_string];
        } else {
            $d = new \DateTime($date_string);
            $dayOfMonth = date("d", $d->getTimestamp());
            return $this->getNthWeekdayOfMonthTranslate()[ceil($dayOfMonth / 7.0)];
        }
    }

    public function getDayOfWeek($date_string){
        if (is_numeric($date_string)) {
            return $this->getDayOfWeekTranslate()[$date_string];
        } else {
            $d = new \DateTime($date_string);
            return $this->getDayOfWeekTranslate()[ceil(date("w", $d->getTimestamp()))];
        }
    }

    public function getNthWeekdayOfMonthTranslate()
    {
        $arrNthWeekdayOfMonth = [
            1 => __('1st'),
            2 => __('2nd'),
            3 => __('3rd'),
            4 => __('4th'),
            5 => __('Last')
        ];

        return $arrNthWeekdayOfMonth;
    }

    public function getDayOfWeekTranslate()
    {
        $arrDayOfWeek = [
            0 => __('Sunday'),
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
        ];

        return $arrDayOfWeek;
    }

    public function deliveryDateChange($profileId, $nextDeliveryDate, $deliveryTime){
        $profileModel = $this->profileFactory->create()->load($profileId, null, true);

        // save profile
        $productCarts = $profileModel->getProductCart($profileId);
        $profileModel->setData('next_delivery_date', $nextDeliveryDate);
        $productIds = [];
        $region = [];
        $arrAddress = [];
        foreach($productCarts as $productCart) {
            $productCart->setNextDeliveryDate($nextDeliveryDate);
            $productCart->setNextDeliverySlotID($deliveryTime);
            $productIds[] = $productCart->getProductId();
            if (!isset($region[$productCart->getShippingAddressId()])) {
                $address = $profileModel->getAddressData($productCart->getShippingAddressId());
                $region[$productCart->getShippingAddressId()] = $address['RegionID'];
                $arrAddress[] = $address;
            }
        }

        $course = $profileModel->getSubscriptionCourse();
        $profileModel->setData('course_data', $course->getData());

        if ($profileModel->getData('day_of_week') != null
            && $profileModel->getData('nth_weekday_of_month') != null) {
            $dayOfWeek = date('l', strtotime($nextDeliveryDate));
            $nthWeekdayOfMonth = $this->deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                $nextDeliveryDate
            );

            $profileModel->addData([
                'day_of_week' => $dayOfWeek,
                'nth_weekday_of_month' => $nthWeekdayOfMonth
            ]);
        }

        $this->profileRepository->saveProfileOriginal($profileModel, $arrAddress, $region, $productIds, false);

        // remove cache
        $this->profileCacheRepository->removeCache($profileId);
        $profileModel = $this->profileFactory->create()->load($profileId, null, true);
        return $profileModel->getData('next_order_date');
    }

    public function saveDataToCache($profileId, $data){
        $cacheId = self::CACHE_PREFIX . $profileId . '_' . $data['type'];
        $data = $this->serializer->serialize($data['content']);
        $this->_cache->save($data, $cacheId, [], 3600);
    }

    public function removeDataFromCache($profileId, $type){
        $cacheId = self::CACHE_PREFIX . $profileId . '_' . $type;
        $this->_cache->remove($cacheId);
    }

    public function getDataFromCache($profileId, $type){
        $cacheId = self::CACHE_PREFIX . $profileId . '_' . $type;
        $data = $this->_cache->load($cacheId);
        if(!$data){
            return null;
        }
        return $this->serializer->unserialize($data);
    }

    public function saveVerificationDataToCache($profileId, $response, $type, $additionalInfo = null){
        if($this->getDataFromCache($profileId, $type) !== null){
            $this->removeDataFromCache($profileId, $type);
        }
        $data = ['type' => $type, 'content' => null];
        if($type === self::TYPE_DELIVERY_DATE){
            $data['content'] = [
                'min_date' => $response['calendar_option']['min_date'],
                'max_date' => $response['calendar_option']['max_date'],
                'unavailable_date' => $response['calendar_option']['unavailable_date'],
                'delivery_time_list' => $this->getTimeslotIdList($response['calendar_option']['delivery_time_list']),
                'is_disable' => $response['calendar_option']['is_disable']
            ];
        } else {
            $data['content'] = $additionalInfo;
        }
        $this->saveDataToCache($profileId, $data);
    }

    public function getTimeslotIdList($timeslotList){
        $result = [];
        foreach($timeslotList as $timeslot){
            $result[] = $timeslot['value'];
        }
        return $result;
    }

    public function verifySetDeliveryDate($profileId, $deliveryDate, $deliveryTime, $stopRecursion = false){
        $verifyData = $this->getDataFromCache($profileId, self::TYPE_DELIVERY_DATE);
        if(!$verifyData && !$stopRecursion){
            $this->getModifiableOrder($profileId);
            return $this->verifySetDeliveryDate($profileId, $deliveryDate, $deliveryTime, true);
        }
        if( $deliveryDate > $verifyData['max_date'] || $deliveryDate < $verifyData['min_date'] ||
            in_array($deliveryDate, $verifyData['unavailable_date']) ||
            $verifyData['is_disable'] || !in_array($deliveryTime, $verifyData['delivery_time_list'])){
            return false;
        }
        return true;
    }

    public function checkStockPoint($profileId){
        $profileCache = $this->getProfileEntity($profileId);
        return $this->validateStockPointProduct->checkProfileExistStockPoint($profileCache);
    }

    public function getProfileEntity($profileId, $reset = false){
        if($reset){
            $this->removeProfileCache($profileId);
        }
        $profileCache = $this->profileCacheRepository->initProfile($profileId, false)->getProfileData()[$profileId];
        $profileCache = $this->processProfileStockPoint($profileCache, $profileId);
        return $profileCache;
    }

    public function processProfileStockPoint($profileCache, $profileId)
    {
        if (isset($profileCache)) {
            $currentProfile = $profileCache;
            $stockPointSession = $this->buildStockPointPostData->getDataNotifyConvert();
            $this->_logger->critical("NET-18 stockpointsession " . json_encode($stockPointSession));
            if (empty($stockPointSession)) {
                $stockPointSession = $currentProfile->getData('stock_point_data');
            } else {
                if (isset($currentProfile['is_delete_stock_point'])) {
                    unset($profileCache['is_delete_stock_point']);
                }
            }

            /**
             * Check delete stock point
             */
            $isDelete = false;
            if (isset($currentProfile['is_delete_stock_point']) && $currentProfile['is_delete_stock_point']) {
                $isDelete = true;
            }

            if (!$isDelete &&
                isset($stockPointSession['stock_point_id']) &&
                $stockPointSession['stock_point_id'] != null
            ) {
                $rikiStockPointId = $currentProfile->getData('riki_stock_point_id');
                if ($this->buildStockPointPostData->getRikiStockId()) {
                    $rikiStockPointId = $this->buildStockPointPostData->getRikiStockId();
                }

                $profileCache->setData('riki_stock_point_id', $rikiStockPointId);
                $profileCache = $this->buildStockPointPostData->setDataStockPointToProfile($profileCache, $profileId);
                if (isset($currentProfile['is_delete_stock_point'])) {
                    unset($profileCache['is_delete_stock_point']);
                }
            }
        }

        return $profileCache;
    }
    
    public function removeProfileCache($profileId){
        $this->profileCacheRepository->removeCache($profileId);
    }

    public function getCcLastUsedDate($customerId, $profileId)
    {
        $collection = $this->paygentHistory->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('profile_id', $profileId)
            ->addFieldToFilter('type', ['in' => ['profile_update', 'authorize']])
            ->setOrder('id', 'desc')
            ->setPageSize(1);
        if (!$collection->getSize()) {
            return false;
        }
        return $collection->getFirstItem()->getUsedDate();
    }

    public function loadOriginData($profileId)
    {
        if ($this->_helperProfile->getTmpProfile($profileId) !== false) {
            $profileId = $this->_helperProfile->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        return $this->_helperProfile->load($profileId);
    }

    public function getSubscriberBlock($customerId){
        $currentCustomer = $this->_customerFactory->create()->load($customerId);

        $groupId = $currentCustomer->getGroupId();
        $dateTimeNow = $this->_localeDate->date();

        // get profile oldest from customer
        $profileLast = $this->customerHaveSubscription($customerId);
        if($profileLast && $profileLast->getCreatedDate()){
            $profileCreatedDay = $profileLast->getCreatedDate();
            $createdDate = \DateTime::createFromFormat('Y-m-d', $profileCreatedDay);
        } else {
            $createdDate  =  null;
        }

        // Date config
        $xDay = (int)$this->scopeConfig->getValue(self::SUBCSCRIBER_X_DAY, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $yDay = (int)$this->scopeConfig->getValue(self::SUBCSCRIBER_Y_DAY, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        //get id block for  member type
        switch ($groupId){
            case 1:
                return 'mypage_topright_for_normal';
                break;
            case 2:
                if($createdDate && $xDay && $createdDate->diff($dateTimeNow)->days >= $xDay){
                    return 'mypage_topright_for_subscriber_xxx';
                } else {
                    return 'mypage_topright_for_subscriber';
                }
                break;
            case 3:
                if($createdDate &&  $yDay && $createdDate->diff($dateTimeNow)->days >= $yDay){
                    return 'mypage_topright_for_clubmember_yyy';
                } else {
                    return 'mypage_topright_for_clubmember';
                }
                break;
            default:
                return 'mypage_topright_for_normal';
        }
    }

    public function customerHaveSubscription($customerId)
    {
        if (!($customerId)) {
            return false;
        }

        $customerSub = $this->profileModel->getCustomerSubscriptionProfile($customerId);
        if ($customerSub->getSize() > 0) {
            $profileLast = $customerSub->getLastItem();
            return $profileLast;
        }

        return false;
    }

    public function getAuthorizedFailMessage($profileId)
    {
        $url = '';
        $str1 = '* Because you can not do deal with your credit card used last time, ';
        $str2 = "you can not change the order now.<br/>";
        $str3 = 'Sorry for your inconvenience but please update your card information from ';
        $allStr = $str1 . $str2 . $str3  . '<a href="%1">here</a>.';
        return __($allStr, $url);
    }

    /**
     * is show changing payment method link
     *
     * @param $profileId
     * @return bool
     */
    public function showChangePaymentMethodLink($profileId)
    {
        $profileModel =  $this->profileModel->load($profileId);
        if ($this->_helperProfile->checkProfileHaveTmp($profileId) && $profileModel->getPaymentMethod() === null) {
            return true;
        }
        return false;
    }
}
