<?php

namespace Riki\Subscription\Block\Frontend\Profile;

use Riki\Subscription\Model\Constant;
use Magento\Framework\DataObject;
use Riki\TimeSlots\Model\TimeSlots;
use Riki\DeliveryType\Model\Delitype;

class ConfirmEditHanpukai extends \Magento\Framework\View\Element\Template
{
    /**
     * @var array
     */
    protected $nthWeekdayOfMonth = [
        1 => '1st',
        2 => '2nd',
        3 => '3rd',
        4 => '4th',
        5 => 'Last'
    ];

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_helperProfile;

    /* @var \Riki\Subscription\Model\Profile\ProfileFactory */
    protected $_profileFactory;

    /* @var \Riki\Subscription\Model\ProductCart\ProductCart */
    protected $_productCart;

    /* @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $_productRepository;

    /* @var \Riki\TimeSlots\Model\TimeSlots */
    protected $_timeSlot;

    /* @var \Riki\SubscriptionCourse\Model\Course */
    protected $_courseModel;

    /* @var \Riki\Subscription\Helper\Order\Simulator */
    protected $_simulator;

    /* @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $_customerAddressRepository;


    /* @var \Magento\Catalog\Helper\Image */
    protected $_helperImage;
    /**
     * @var \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    protected $calculateDeliveryDateHelper;
    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $deliveryDate;

    protected $maxDate;

    protected $courseId;
    /**
     * @var \Riki\SalesRule\Helper\CouponHelper
     */
    protected $_couponHelper;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $locale;

    public function __construct(
        \Magento\Catalog\Helper\Image $helperImage,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\SubscriptionCourse\Model\Course $course,
        TimeSlots $timeSlots,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Riki\Subscription\Model\ProductCart\ProductCart $productCart,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDate,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
        \Riki\SalesRule\Helper\CouponHelper $couponHelper,
        \Magento\Framework\Locale\Resolver $locale,
        array $data = []
    ){
        $this->_helperImage = $helperImage;
        $this->_customerAddressRepository = $addressRepositoryInterface;
        $this->_simulator = $simulator;
        $this->_courseModel = $course;
        $this->_timeSlot = $timeSlots;
        $this->_productRepository = $productRepositoryInterface;
        $this->_productCart = $productCart;
        $this->_profileFactory = $profileFactory;
        $this->_helperProfile = $profileData;
        $this->_registry = $registry;
        $this->calculateDeliveryDateHelper = $calculateDeliveryDate;
        $this->deliveryDate = $deliveryDate;
        $this->_couponHelper = $couponHelper;
        $this->locale = $locale;
        parent::__construct($context, $data);
    }

    public function _prepareLayout()
    {
        $pageTitle = __('Payment method edit confirm');
        $this->pageConfig->getTitle()->set(__($pageTitle));
        return parent::_prepareLayout();
    }

    /**
     * Get data registry from controller
     *
     * @param $key
     * @return mixed
     */
    public function getDataRegistry($key)
    {
        return $this->_registry->registry($key);
    }

    /**
     * Get data config
     *
     * @param $path
     *
     * @return mixed
     */
    public function getSystemConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->_scopeConfig->getValue($path, $storeScope);
        return $config;
    }
    /**
     * Get shipping address
     *
     * @return $this
     */
    public function loadProfileModel()
    {
        $profileId = $this->getDataRegistry(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PROFILE_ID);
        if ($this->_helperProfile->getTmpProfile($profileId) !== false) {
            $profileId = $this->_helperProfile->getTmpProfile($profileId)->getData('linked_profile_id');
        }

        return $profileModel = $this->_profileFactory->create()->load($profileId);
    }

    /**
     * Make product cart data and new product add
     *
     * @param $profileId
     * @param $arrNewProductData
     *
     * @return array|void
     */
    public function makeProductCartData($profileId)
    {
        if ($this->_helperProfile->getTmpProfile($profileId) !== false) {
            $profileId = $this->_helperProfile->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        $productCartCollection = $this->_productCart->getCollection();
        $productCartCollection->addFieldToFilter('profile_id', $profileId);

        $data = [];
        foreach ($productCartCollection->getItems() as $item) {
            try {
                $productModel = $this->_productRepository->getById($item->getData('product_id'));
                if ($productModel && $productModel->getStatus() == 1) {
                    $obj = new DataObject();
                    $obj->setData($item->getData());
                    $data[$obj->getData("cart_id")] = $obj;
                }
            } catch (\Exception $e) {
                $this->_logger->error('Product ID #' . $item->getData('product_id') . ' was delete');
            }
        }

        return $data;
    }

    /**
     * Get Customer Address Text
     *
     * @param $addressId
     *
     * @return mixed
     */
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
     * Get shipping address
     */
    public function getProductCartStandardValue()
    {
        $arrResult['shipping_address_id'] = '';
        $arrResult['delivery_time_slot'] = '';
        $arrProduct = $this->getListProduct($this->getDataRegistry(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PROFILE_ID));
        foreach ($arrProduct as $productId => $arrData) {
            if (array_key_exists('profile', $arrData)) {
                $productCartModel = $arrData['profile'];
                if (($productCartModel instanceof \Riki\Subscription\Model\ProductCart\ProductCart)
                    && $productCartModel->getData('shipping_address_id')
                ) {
                    /**
                     * Just need find one shipping address id because subscription is same shipping address
                     * Maybe not ok for case subscription hampukai
                     */
                    $arrResult['shipping_address_id'] = $productCartModel->getData('shipping_address_id');
                    $arrResult['delivery_time_slot'] = $productCartModel->getData('delivery_time_slot');
                    return $arrResult;
                }
            }
        }
        return $arrResult;
    }

    /**
     * Get list product
     *
     * @return array
     */
    public function getListProduct($profileId)
    {
        if ($this->_helperProfile->getTmpProfile($profileId) !== false) {
            $profileId = $this->_helperProfile->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        $arrProducts = $this->_helperProfile->getArrProductCart($profileId);
        return $arrProducts;
    }

    /**
     * Get slot object
     *
     * @param $slotId
     *
     * @return $this|null
     */
    public function getSlotObject($slotId)
    {
        $slotModel = $this->_timeSlot->load($slotId);
        if ($slotModel && $slotModel->getId()) {
            return $slotModel;
        }
        return null;
    }

    /**
     * Get list product by shipping address id and delivery type
     *
     * @param $profileId
     *
     * @return array
     */
    public function getListProductByAddressIdAndDeliveryType($profileId)
    {
        $arrResult = array();
        $listProductInCart = $this->getListProduct($profileId);
        foreach ($listProductInCart as $productId => $arrProductInfo)
        {
            $deliveryType = $arrProductInfo['details']->getData('delivery_type');
            $shippingAddressId = $arrProductInfo['profile']->getData('shipping_address_id');
            if ($deliveryType == Delitype::COLD || $deliveryType == Delitype::NORMAl || $deliveryType == Delitype::DM) {
                $deliveryType = Delitype::COOL_NORMAL_DM;
            }
            $deliveryDate = $arrProductInfo['profile']->getData('delivery_date');
            $timeSlot = $arrProductInfo['profile']->getData('time_slot');

            $arrResult[$shippingAddressId][$deliveryType]['product'][] = $arrProductInfo;
            $arrResult[$shippingAddressId][$deliveryType]['delivery_date']['next_delivery_date'] = $deliveryDate;
            $arrResult[$shippingAddressId][$deliveryType]['delivery_date']['time_slot'] = $timeSlot;
        }

        return $arrResult;
    }
    /**
     * @param \Riki\Subscription\Model\Emulator\Order $simulatorOrder
     * @return array
     * @throws \Exception
     */
    public function getListProductByAddressAndByDeliveryType($simulatorOrder = null)
    {
        $changeShippingAddressId = '';
        $objectManager = $this->_objectManager;
        $deliveryTypeHelper = $objectManager->get("Riki\DeliveryType\Helper\Data");
        $objSessionProfile = $this->getEntity();
        if ($objSessionProfile->getData('new_shipping_address_id')) {
            $changeShippingAddressId = $objSessionProfile->getData('new_shipping_address_id');
        }
        $arrProductCat = $objSessionProfile->getData("product_cart");
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
                $objCustomer = $this->_customerFactory->create()->load($objSessionProfile->getData("customer_id"));
                $objAddress = $objCustomer->getAddressById($addressId);
                if (is_null($objAddress->getId())) {
                    if ($objCustomer->getDefaultShippingAddress() instanceof \Magento\Customer\Model\Address) {
                        $addressId = $objCustomer->getDefaultShippingAddress()->getId();
                    } else {
                        $arrObjectAddress = $objCustomer->getAddresses();

                        if (count($arrObjectAddress) > 0) {
                            $addressId = array_keys($arrObjectAddress)[0];
                        } else {
                            throw new LocalizedException(__("User do not have any address to delivery!"));
                        }
                    }
                }
                // Check this address is exists or not. If it is not exists. Choose default for customer


                $productId = $objProductData->getData('product_id');
                $productFactory = $objectManager->create('\Magento\Catalog\Model\ProductFactory');
                $product = $productFactory->create();
                $product->load($productId);
                $deliveryType = $product->getData("delivery_type");


                if (!isset($arrReturn[$addressId][$deliveryType])) {
                    $objAddress = $objectManager->create('\Magento\Customer\Model\Address')->load($addressId);
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
                        $courseModel = $this->_subCourseModel->load($objSessionProfile->getData('course_id'));
                        if ($courseModel->getData('allow_change_next_delivery_date') == 0) {
                            $arrReturn[$addressId][$deliveryType]['delivery_date'] = [
                                'next_delivery_date' => $objSessionProfile->getData('next_delivery_date'),
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
                $iBaseTierPrice = 0;
                if ($objProductData->getData('product_type') != 'bundle') {
                    if (!empty($product->getTierPrice())) {
                        $iBaseTierPrice = $this->getRenderPrice($product, 1);
                    }
                    $product->setFinalPrice(null);
                    $amount = $this->getRenderPrice($product, $objProductData->getData('qty'));
                } else {
                    $amount = $this->_subHelperData->getBundleMaximumPrice($product);
                }
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
                    'base_tier_price' => $iBaseTierPrice,
                    'is_free_gift' => $objProductData->getData('is_free_gift'),
                    'is_spot' => $objProductData->getData('is_spot'),
                    'is_addition' => $objProductData->getData('is_addition')
                ];
            }
        }

        // Group it
        foreach ($arrReturn as $addressId => $arrOfDeliveryType) {
            $arrDeliveryType = array_keys($arrOfDeliveryType);

            foreach ($arrOfDeliveryType as $deliveryType => $arrInfo) {
                $deliveryTypeEdited = $deliveryTypeHelper->getDeliveryTypeNameInAllowGroup($deliveryType, $arrDeliveryType);

                if ($deliveryTypeEdited == $deliveryType) continue;

                if (!isset($arrReturn[$addressId][$deliveryTypeEdited])) {
                    $arrReturn[$addressId][$deliveryTypeEdited] = $arrInfo;
                } else {
                    $arrReturn[$addressId][$deliveryTypeEdited]['product'] = array_merge($arrReturn[$addressId][$deliveryTypeEdited]['product'], $arrReturn[$addressId][$deliveryType]['product']);

                    unset($arrReturn[$addressId][$deliveryType]); // Remove after group.
                }
            }
        }

        return $arrReturn;
    }

    /**
     * Get profile id
     *
     * @return mixed
     */

    public function getProfileId()
    {
        return $this->getDataRegistry(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PROFILE_ID);
    }


    /**
     * Need profile model data and product cart data
     *
     */
    public function makeObjectDataForSimulate($profileId)
    {
        $profileModel = $this->loadProfileModel();
        $productCartData = $this->makeProductCartData($profileId);
        $obj = new DataObject();
        $obj->setData($profileModel->getData());
        if ($this->getDataRegistry(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PAYMENT_METHOD)) {
            $paymentMethod = $this->getDataRegistry(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PAYMENT_METHOD);
            if ($paymentMethod == 'new_paygent') {
                $paymentMethod = 'paygent';
            }
            $obj->setData('payment_method',$paymentMethod);

        }

        $obj->setData('course_data', $this->getCourseData($profileModel->getData('course_id')));
        $obj->setData("product_cart", $productCartData);

        $couponCode = $this->getCouponCode();
        $obj->setData('coupon_code',$couponCode);


        return $obj;
    }

    /**
     * Get course data
     *
     * @return mixed
     * @throws \Exception
     */
    public function getCourseData($courseId)
    {
        return $this->_courseModel->load($courseId);
    }

    /**
     * Get shipping fee include tax
     *
     * @param $shippingFee
     *
     * @return float
     */
    public function getShippingFeeIncludeTax($shippingFee)
    {
        return $this->_helperProfile->getShippingInclueTax($shippingFee, $this->_storeManager->getStore()->getId());
    }


    /**
     * Simulator with object data
     *
     * @param $objectData
     * @return array|bool
     */
    public function simulator($objectData)
    {
        return $this->_simulator->createSimulatorOrderHasData($objectData);
    }

    /**
     * Get address detail by address id
     *
     * @param $addressId
     *
     * @return mixed
     * @throws \Exception
     */
    public function getAddressDetail($addressId)
    {
        $customerShippingAddress = $this->_customerAddressRepository->getById($addressId);

        if ($customerShippingAddress instanceof \Magento\Customer\Model\Data\Address) {
            if ($rikiNicknameObj = $customerShippingAddress->getCustomAttribute('riki_nickname')) {
                $rikiNickname = $rikiNicknameObj->getValue();
            } else {
                $rikiNickname = '';
            }

            if ($rikiFirstnameKanaObj = $customerShippingAddress->getCustomAttribute('firstnamekana')) {
                $rikiFirstnameKana = $rikiFirstnameKanaObj->getValue();
            } else {
                $rikiFirstnameKana = '';
            }

            if ($rikiLastnameKanaObj = $customerShippingAddress->getCustomAttribute('lastnamekana')) {
                $rikiLastnameKana = $rikiLastnameKanaObj->getValue();
            } else {
                $rikiLastnameKana = '';
            }

            if ($rikiTypeAddressObj = $customerShippingAddress->getCustomAttribute('riki_type_address')) {
                $rikiTypeAddress = $rikiTypeAddressObj->getValue();
            } else {
                $rikiTypeAddress = '';
            }

        } else {
            $rikiNickname
                = $customerShippingAddress ? $customerShippingAddress->getData('riki_nickname') : '';
            $rikiFirstnameKana
                = $customerShippingAddress ? $customerShippingAddress->getData('firstnamekana') : '';
            $rikiLastnameKana
                = $customerShippingAddress ? $customerShippingAddress->getData('lastnamekana') : '';
            $rikiTypeAddress
                = $customerShippingAddress ? $customerShippingAddress->getData('riki_type_address') : '';
        }
        $arrReturn['lastname'] = $customerShippingAddress->getLastname();
        $arrReturn['firstname'] = $customerShippingAddress->getFirstname();
        $arrReturn['riki_nickname'] = $rikiNickname;
        $arrReturn['riki_firstnamekana'] = $rikiFirstnameKana;
        $arrReturn['riki_lastnamekana'] = $rikiLastnameKana;
        $arrReturn['riki_type_address'] = $rikiTypeAddress;
        $arrReturn['telephone'] = $customerShippingAddress ? $customerShippingAddress->getTelephone() : '';
        return $arrReturn;
    }

    /**
     * Get image url
     *
     * @param $product
     *
     * @return string
     */
    public function getImageUrl($product)
    {
        /* @var $product \Magento\Catalog\Model\Product */
        return $this->_helperImage->init($product, 'cart_page_product_thumbnail')
            ->keepFrame(false)
            ->constrainOnly(true)
            ->resize(160, 160);
    }

    /**
     * Format price
     *
     * @param $price
     * @param null $websiteId
     * @return mixed
     */
    public function formatCurrency($price)
    {
        return $this->_storeManager->getWebsite($this->_storeManager->getWebsite()->getId())
            ->getBaseCurrency()->format($price);
    }

    /**
     * Get Day
     *
     * @param $stringDate
     *
     * @return string
     */
    public function getDay($stringDate)
    {
        $timestamp = strtotime($stringDate);
        $day = date('D', $timestamp);
        return $day;
    }

    /**
     * @param $paymentCode
     * @return mixed
     */
    public function getPaymentTitle($paymentCode)
    {
        $configPath = 'payment/'.$paymentCode.'/title';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->_scopeConfig->getValue($configPath, $storeScope);
    }
    public function getHelperCalculateDateTime() {
        return $this->calculateDeliveryDateHelper;
    }
    public function getAllTimeSlot()
    {
        $timeSlot = $this->deliveryDate->getListTimeSlot();
        return $timeSlot;
    }
    /**
     * Get last delivery of Profile
     *
     * @return int|false
     */
    public function getLastDeliveryDate()
    {
        $profileId = $this->getRequest()->getParam('id');
        $profileModel = $this->loadProfileModel();
        $orderTimes = $profileModel->getData('order_times');

        if ($profileModel->getData('type') == 'tmp') {
            $orderTimes -= 1;
        }

        $lastDeliveryDate = $this->_helperProfile->getLastOrderDeliveryDateOfProfile($profileId, $orderTimes);
        if (is_null($lastDeliveryDate)) {
            if ($profileModel->getData('type') == 'tmp') {
                $lastDeliveryDate = $profileModel->getData('next_delivery_date');
            } else {
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
        } else if ($profileModel->getData('type') == 'tmp') {
            $lastDeliveryDate = date(
                'Y-m-d',
                strtotime(
                    $profileModel->getData('frequency_interval')
                    . " "
                    . $profileModel->getData('frequency_unit'),
                    strtotime($lastDeliveryDate)
                )
            );
        }

        return $lastDeliveryDate;
    }

    /**
     * get Available start date for calendar edit profile
     *
     * @param $checkCalendar
     * @return \DateTime
     */
    public function calculateAvailableStartDate($checkCalendar)
    {
        $startDate = time();
        foreach ($checkCalendar as $date) {
            if (strtotime($date) > $startDate) {
                $startDate = strtotime($date);
            }
        }
        if (!$this->courseId) {
            $profileModel = $this->loadProfileModel();
            $this->courseId = $profileModel->getData('course_id');
        }
        $courseId = $this->courseId;

        $minDate = $this->_localeDate->scopeDate(null, date('Y-m-d', $startDate + 86400));

        $courseSettings = $this->getCourseData($courseId);
        $hanpukaiAllowChangeDeliveryDate = $courseSettings['hanpukai_delivery_date_allowed'];
        $hanpukaiDeliveryDateFrom = $courseSettings['hanpukai_delivery_date_from'];
        if ($hanpukaiAllowChangeDeliveryDate && strtotime($hanpukaiDeliveryDateFrom) > $startDate) {
            $minDate = $this->_localeDate->scopeDate(null, date('Y-m-d', strtotime($hanpukaiDeliveryDateFrom)));
        }

        return $minDate;
    }

    /**
     * Get profile id
     *
     * @return mixed
     */

    public function getCouponCode()
    {
        $couponCode = $this->getDataRegistry(Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_COUPON_CODE);
        if(is_array($couponCode)&&count($couponCode)>0)
        {
            $couponCode = implode(',',$couponCode);
        }
        return $couponCode;
    }

    /**
     * Get list rule coupon
     *
     * @param $orderSimulator
     * @param $couponCodeInput
     * @return array|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListRulIdsAppliedHanpukai($orderSimulator,$couponCodeInput)
    {
        $ruleId = $orderSimulator->getAppliedRuleIds();
        $listCouponCode = $couponCodeInput;

        //add message when auto remove coupon code
        $this->_couponHelper->addMessageRemoveCouponCode($orderSimulator,$couponCodeInput);

        $dataCoupon = $this->_couponHelper->checkCouponRealIdsWhenProcessSimulator($ruleId,$listCouponCode);
        if(is_array($dataCoupon)&& count($dataCoupon)){
            return $dataCoupon;
        }
        return false;
    }

    /**
     * Show coupon code on input hidden
     *
     * @param $orderSimulator
     * @return null|string
     */
    public function showCouponCode($orderSimulator)
    {
        if(!$orderSimulator)
        {
            return null;
        }

        $coupon = $this->_couponHelper->getRealAppliedCoupon($orderSimulator);
        if(!is_array($coupon))
        {
            $coupon = [];
        }

        return implode(',',$coupon);
    }

    /**
     * Check show delivery message
     *
     * @return boolean
     */
    public function isShowDeliveryMessage()
    {
        $profileModel = $this->loadProfileModel();

        if ($this->_helperProfile->isDayOfWeekAndUnitMonthAndNotStockPoint($profileModel)) {
            return true;
        }

        return false;
    }

    /**
     * Get locale code
     *
     * @return string
     */
    public function getLocaleCode()
    {
        return $this->locale->getLocale();
    }

    /**
     * Get value of nth weekday of month
     *
     * @param int $index
     * @return string
     */
    public function getValueOfNthWeekdayOfMonth($index)
    {
        return $this->nthWeekdayOfMonth[$index];
    }

    /**
     * Get url update next delivery date in message
     *
     * @return string
     */
    public function getUrlUpdateNextDeliveryDate()
    {
        return $this->getUrl('subscriptions/profile/ajaxUpdateNextDeliveryDateMessage');
    }
}
