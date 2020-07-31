<?php

namespace Riki\Subscription\Model\Profile\WebApi;

use Bluecom\Paygent\Model\Paygent;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\LocalizedException;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Riki\Subscription\Api\WebApi\EditPage\WebAppEditProfileInterface;
use Riki\Subscription\Helper\Order;
use Riki\Subscription\Model\Profile\Profile;
use Riki\Subscription\Model\Validator;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

class WebAppEditProfile implements WebAppEditProfileInterface
{
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    private $profileFactory;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    private $courseFactory;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    private $orderSimulatorHelper;

    /**
     * @var \Riki\Subscription\Helper\Indexer\Data
     */
    private $profileIndexerHelper;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $appEmulation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Riki\Subscription\Helper\WebApi\DeliveryDateHelper
     */
    private $deliveryDateHelper;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    private $imageBuilder;

    /**
     * @var \Riki\Catalog\Helper\Data
     */
    private $catalogHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Riki\AdvancedInventory\Helper\Inventory
     */
    private $helperInventory;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course[]
     */
    private $courseData = [];

    /**
     * @var CaseDisplay
     */
    private $caseDisplay;

    /**
     * @var ProfileRepository
     */
    private $webApiProfileRepository;

    /**
     * @var Order
     */
    private $subscriptionHelperOrder;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    private $productCartFactory;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    private $subscriptionCourseHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Riki\Subscription\Model\Simulator\CouponSimulator
     */
    private $couponSimulator;

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var Order
     */
    private $orderHelper;

    /**
     * @var \Riki\Promo\Helper\Data
     */
    private $promoHelper;

    /**
     * @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface
     */
    private $giftWrappingRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    private $giftWrappingData;

    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    private $taxCalculation;

    /**
     * @var \Riki\BackOrder\Helper\Data
     */
    private $backOrderHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    private $subscriptionValidator;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    private $quoteAddressFactory;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    private $couponFactory;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    private $ruleFactory;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    private $customerAddressRepository;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course\Source\Payment
     */
    private $sourcePayment;

    /**
     * @var \Riki\Subscription\Helper\Profile\Controller\Save
     */
    private $profileSaveHelper;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    private $validateStockPointProduct;

    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    private $stockPointHelper;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    private $profileHelperData;

    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    private $subCourseResourceModel;

    /**
     * @var \Riki\SalesRule\Helper\CouponHelper
     */
    private $couponHelper;

    /**
     * @var \Riki\Subscription\Model\Emulator\Order
     */
    private $simulateOrder = null;

    /**
     * @var array $originProductCartData
     */
    private $originProductCartData = [];

    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Helper\Order\Simulator $orderSimulatorHelper,
        \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Subscription\Helper\WebApi\DeliveryDateHelper $deliveryDateHelper,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\AdvancedInventory\Helper\Inventory $helperInventory,
        CaseDisplay $caseDisplay,
        \Riki\Subscription\Api\WebApi\ProfileRepositoryInterface $webApiProfileRepository,
        Order $subscriptionHelperOrder,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Riki\SubscriptionCourse\Helper\Data $subscriptionCourseHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Subscription\Model\Simulator\CouponSimulator $couponSimulator,
        \Magento\Framework\Registry $coreRegistry,
        \Riki\Subscription\Helper\Order $orderHelper,
        \Riki\Promo\Helper\Data $promoHelper,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $giftWrappingRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\GiftWrapping\Helper\Data $giftWrappingData,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Riki\SubscriptionCourse\Model\Course\Source\Payment $sourcePayment,
        \Riki\Subscription\Helper\Profile\Controller\Save $profileSaveHelper,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper,
        \Riki\Subscription\Helper\Profile\Data $profileHelperData,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $subCourseResourceModel,
        \Riki\SalesRule\Helper\CouponHelper $couponHelper
    ) {
        $this->courseFactory = $courseFactory;
        $this->orderSimulatorHelper = $orderSimulatorHelper;
        $this->profileIndexerHelper = $profileIndexerHelper;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->deliveryDateHelper = $deliveryDateHelper;
        $this->imageBuilder = $imageBuilder;
        $this->catalogHelper = $catalogHelper;
        $this->profileFactory = $profileFactory;
        $this->productRepository = $productRepository;
        $this->helperInventory = $helperInventory;
        $this->caseDisplay = $caseDisplay;
        $this->webApiProfileRepository = $webApiProfileRepository;
        $this->subscriptionHelperOrder = $subscriptionHelperOrder;
        $this->productCartFactory = $productCartFactory;
        $this->categoryFactory = $categoryFactory;
        $this->subscriptionCourseHelper = $subscriptionCourseHelper;
        $this->timezone = $timezone;
        $this->couponSimulator = $couponSimulator;
        $this->coreRegistry = $coreRegistry;
        $this->orderHelper = $orderHelper;
        $this->promoHelper = $promoHelper;
        $this->giftWrappingRepository = $giftWrappingRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->giftWrappingData = $giftWrappingData;
        $this->taxCalculation = $taxCalculation;
        $this->backOrderHelper = $backOrderHelper;
        $this->scopeConfig = $scopeConfig;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->ruleFactory = $ruleFactory;
        $this->couponFactory = $couponFactory;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->quoteFactory = $quoteFactory;
        $this->sourcePayment = $sourcePayment;
        $this->profileSaveHelper = $profileSaveHelper;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->stockPointHelper = $stockPointHelper;
        $this->profileHelperData = $profileHelperData;
        $this->subCourseResourceModel = $subCourseResourceModel;
        $this->couponHelper = $couponHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($profileId, $customerId)
    {
        $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(),
            \Magento\Framework\App\Area::AREA_FRONTEND, true);

        $result['response'] = [];

        /** @var Profile $profile */
        $profile = $this->profileFactory->create()->load($profileId);
        $profile->getProductCart();

        if ($profile->getCustomerId() != $customerId) {
            $result['response']['message'][] = [
                "type" => "error",
                "text" => __("Profile is not belong to customer")
            ];
            return $result;
        }


        $course = $this->getCourseById($profile->getCourseId());
        // API doesn't use Hanpukai
        if ($course->getSubscriptionType() == CourseType::TYPE_HANPUKAI) {
            $result['response']['message'][] = [
                "type" => "error",
                "text" => __("Cannot edit hanpukai profile")
            ];
            return $result;
        }

        $frequencyId = $course->checkFrequencyEntitiesExitOnDb($profile->getFrequencyUnit(),
            $profile->getFrequencyInterval());

        $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
        $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $course->getId());

        try {
            $simulatorOrderInfo = $this->getSimulatorOrderInformation($profile);
        } catch (\Exception $e) {
            $result['response']['message'][] = [
                "type" => "error",
                "text" => $e->getMessage()
            ];

            return $result;
        }

        $result['response'] = [
            'message' => [],
            'redirect' => '',
            'content' => [
                'profile_data' => $simulatorOrderInfo
            ]
        ];

        $this->appEmulation->stopEnvironmentEmulation();

        return $result;
    }

    /**
     * @param Profile $profile
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @return array
     */
    public function getCourseNotice($profile, $course)
    {
        $notice = [];

        if ($course->getMustSelectSku()) {
            $mustSelectCartData = $course->getMustSelectSku();
            $mustSelectCarts = explode(':', $mustSelectCartData);

            $mustSelectCartId = $mustSelectCarts[0];
            $mustSelectProductQty = $mustSelectCarts[1];

            $category = $this->categoryFactory->create()->load($mustSelectCartId);

            $notice[] = __('Please select %1 of %2', $mustSelectProductQty, $category->getName());
        }

        if ($course->getMinimumOrderQty()) {
            $notice[] = __('You need to add at least %1 products', (int)$course->getMinimumOrderQty());
        }

        if (!$course->getData('allow_change_product')) {
            $notice[] = __('This subscription course do not allow change product');
        }

        $maximumQty = $this->getMaximumQty($course, $profile->getOrderTimes() + 1);

        if ($maximumQty > 0) {
            $notice[] = __('Maximum qty for each product is %1', $maximumQty);
        }

        //Validate Amount restriction
        list ($min, $max, $option) =  $this->orderHelper->getMinMaxOption($course, $profile);
        if ($option >= 0 && !empty($min)) {
            if (!empty($min) ) {
                $notice[] = __('Minimum order amount is %1 yen', $min);
            }
        }

        return $notice;
    }

    /**
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @param int $nextOrderTimes
     * @return int
     */
    private function getMaximumQty($course, $nextOrderTimes)
    {
        if ($maximumRestrictionData = $course->getData('maximum_qty_restriction')) {
            $restrictionOptionType = $course->getData('maximum_qty_restriction_option');

            switch ($restrictionOptionType) {
                case Validator::ONLY_APPLY_FOR_THE_SECOND_ORDER:
                    if ($nextOrderTimes != 2) {
                        return 0;
                    }
                    break;
                case Validator::CUSTOM_AMOUNT_FOR_EACH_ORDER_TIME:
                    break;
                default:
                    return 0;
            }

            try {
                $options = json_decode($maximumRestrictionData, true);
                if (isset($options['maximum']) && is_array($options['maximum'])) {
                    if (isset($options['maximum']['qty'])) {
                        return (int)$options['maximum']['qty'];
                    }

                    if (isset($options['maximum']['qtys'])) {
                        foreach ($options['maximum']['qtys'] as $threshold) {
                            if (isset($threshold['from_order_time']) &&
                                isset($threshold['to_order_time']) &&
                                isset($threshold['qty'])
                            ) {
                                if ($threshold['from_order_time'] <= $nextOrderTimes &&
                                    $threshold['to_order_time'] >= $nextOrderTimes
                                ) {
                                    return (int)$threshold['qty'];
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                return 0;
            }
        }

        return 0;
    }

    /**
     * get course by id
     *
     * @param $courseId
     * @return \Riki\SubscriptionCourse\Model\Course
     */
    private function getCourseById($courseId)
    {
        if (!isset($this->courseData[$courseId])) {
            $this->courseData[$courseId] = $this->courseFactory->create()->load($courseId);
        }

        return $this->courseData[$courseId];
    }

    /**
     * @param Profile $profile
     * @return array
     */
    private function getSimulatorOrderInformation($profile)
    {
        $course = $this->getCourseById($profile->getCourseId());

        /** @var \Riki\Subscription\Model\Emulator\Order $simulatorOrder */
        $simulatorOrder = $this->getSimulatorOrderOfProfile($profile);

        if ($simulatorOrder == null) {
            return $this->prepareResponseDataForEmptySimulatorOrder($profile, $course);
        }

        $courseSettings = $course->getSettings();

        $paymentMethod = $simulatorOrder->getPayment()->getMethod();

        if ($profile->getPaymentMethod() == null) {
            $paymentMethod = null;
            $paymentMethodLabel = null;
        } elseif ($simulatorOrder->getPayment()->getMethod() == Paygent::CODE) {
            $paymentMethodLabel  = 'クレジット支払い（前回使用）';
        } else {
            $paymentMethodLabel = $simulatorOrder->getPayment()->getData('additional_information')['method_title'];
        }

        $termsOfUse =  $this->deliveryDateHelper->getTermsOfUseDownloadUrl($course->getData('terms_of_use'));
        if($termsOfUse) {
            $termsOfUse = $this->scopeConfig->getValue('web/secure/base_url') . $termsOfUse;
        }

        $simulatorOrderInformation = [
            'profile_id' => $profile->getProfileId(),
            'course_name' => $profile->getCourseData()['course_name'],
            'course_code' => $profile->getCourseData()['course_code'],
            'course_id' => $profile->getCourseData()['course_id'],
            'payment_method' => [
                'code' => $paymentMethod,
                'title' => $paymentMethodLabel
            ],
            'frequency' => sprintf(
                __("%s " . $profile->getFrequencyUnit()), $profile->getFrequencyInterval()
            ),
            'frequency_id' => $profile->getSubProfileFrequencyID(),
            'shipping_address_name' => $simulatorOrder->getShippingAddress()->getRikiNickname(),
            'calendar_option' => $this->deliveryDateHelper->getProfileCalendarOption($simulatorOrder, $profile, $course),
            'next_delivery_date' => $profile->getNextDeliveryDate(),
            'next_order_date' => $profile->getNextOrderDate(),
            'deadline' => date('Y-m-d', strtotime("-1 day", strtotime($profile->getNextOrderDate()))),
            'coupon_code' => $profile->getCouponCode() ?? '',
            'course_notice' => $this->getCourseNotice($profile, $course),
            'is_allow_change_next_delivery' => !!$courseSettings['is_allow_change_next_delivery'],
            'is_allow_change_payment_method' => !!$courseSettings['is_allow_change_payment_method'],
            'is_allow_change_address' => !!$courseSettings['is_allow_change_address'],
            'is_allow_change_item' => !!$courseSettings['is_allow_change_product'],
            'is_allow_change_qty' => !!$courseSettings['is_allow_change_qty'],
            'is_stockpoint' => !!$profile->getData('stock_point_profile_bucket_id'),
            'is_monthly_fee' =>  $course->getSubscriptionType() == CourseType::TYPE_MONTHLY_FEE,
            'terms_of_use' => $termsOfUse,
            'authorized_fail_message' => $this->getAuthorizeFailedMessage($profile),

            // Simulation order total
            'subtotal' => $simulatorOrder->getSubtotalInclTax(),
            'grand_total' => $simulatorOrder->getGrandTotal(),
            'shipping_fee' => $simulatorOrder->getShippingInclTax(),
            'payment_fee' => (int)$simulatorOrder->getFee(),
            'gift_wrapping_fee' => (int)$simulatorOrder->getData('gw_items_base_price_incl_tax'),
            'discount_amount' => $simulatorOrder->getData('discount_amount'),
            'point_used' => (int)$simulatorOrder->getData('used_point_amount'),
            'point_earned' => $simulatorOrder->getData('bonus_point_amount'),
            'list_rule_applied' => $this->deliveryDateHelper->getListRuleIdsApplied($simulatorOrder)
        ];

        if($simulatorOrderInformation['is_stockpoint']){
            if($profile->getData('stock_point_delivery_type') != Profile::SUBCARRIER){
                $simulatorOrderInformation['is_allow_change_next_delivery'] = false;
            }
        }

        $simulatorOrderInformation['product_list'] = $this->getProfileProductList($profile,$simulatorOrder);

        $totalQty = 0;
        if (count($simulatorOrderInformation['product_list']) > 0) {
            foreach ($simulatorOrderInformation['product_list'] as $productList) {
                $totalQty += $productList['unit_case'] == CaseDisplay::PROFILE_UNIT_CASE ? $productList['qty']/$productList['unit_qty'] : $productList['qty'];
            }
        }

        $simulatorOrderInformation['total_qty'] = $totalQty;

        return $simulatorOrderInformation;
    }

    /**
     * @param Profile $profile
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @return array
     * @throws LocalizedException
     */
    public function prepareResponseDataForEmptySimulatorOrder($profile, $course)
    {
        $courseSettings = $course->getSettings();

        $paymentMethod = $profile->getPaymentMethod();
        if ($paymentMethod == null) {
            $paymentMethodLabel = null;
        } elseif ($profile->getPaymentMethod() == Paygent::CODE) {
            $paymentMethodLabel  = 'クレジット支払い（前回使用）';
        } else {
            $paymentMethodLabel = $this->sourcePayment->getOptionName($paymentMethod);
        }

        $termsOfUse =  $this->deliveryDateHelper->getTermsOfUseDownloadUrl($course->getData('terms_of_use'));
        if($termsOfUse) {
            $termsOfUse = $this->scopeConfig->getValue('web/secure/base_url') . $termsOfUse;
        }

        $simulatorOrderInformation = [
            'profile_id' => $profile->getProfileId(),
            'course_name' => $profile->getCourseData()['course_name'],
            'course_code' => $profile->getCourseData()['course_code'],
            'course_id' => $profile->getCourseData()['course_id'],
            'payment_method' => [
                'code' => $paymentMethod,
                'title' => $paymentMethodLabel
            ],
            'frequency' => sprintf(
                __("%s " . $profile->getFrequencyUnit()), $profile->getFrequencyInterval()
            ),
            'frequency_id' => $profile->getSubProfileFrequencyID(),
            'shipping_address_name' => '',
            'calendar_option' => '',
            'next_delivery_date' => '',
            'next_order_date' => '',
            'deadline' => '',
            'coupon_code' => $profile->getCouponCode() ?? '',
            'course_notice' => $this->getCourseNotice($profile, $course),
            'is_allow_change_next_delivery' => !!$courseSettings['is_allow_change_next_delivery'],
            'is_allow_change_payment_method' => !!$courseSettings['is_allow_change_payment_method'],
            'is_allow_change_address' => !!$courseSettings['is_allow_change_address'],
            'is_allow_change_item' => !!$courseSettings['is_allow_change_product'],
            'is_allow_change_qty' => !!$courseSettings['is_allow_change_qty'],
            'is_stockpoint' => !!$profile->getData('stock_point_profile_bucket_id'),
            'terms_of_use' => $termsOfUse,
            'is_monthly_fee' => $course->getSubscriptionType() == CourseType::TYPE_MONTHLY_FEE,
            'authorized_fail_message' => $this->getAuthorizeFailedMessage($profile),

            // Simulation order total
            'subtotal' => 0,
            'grand_total' => 0,
            'shipping_fee' => 0,
            'payment_fee' => 0,
            'gift_wrapping_fee' => 0,
            'discount_amount' => 0,
            'point_used' => 0,
            'point_earned' => 0,
            'list_rule_applied' => false,

            'product_list' => null,
            'total_qty' => 0
        ];

        if($simulatorOrderInformation['is_stockpoint']){
            if($profile->getData('stock_point_delivery_type') != Profile::SUBCARRIER){
                $simulatorOrderInformation['is_allow_change_next_delivery'] = false;
            }
        }

        return $simulatorOrderInformation;
    }


    /**
     * @param Profile $profile
     * @param $simulatorOrder
     * @return array
     */
    public function getProfileProductList($profile, $simulatorOrder)
    {
        $groupedDeliveryInformation = $this->deliveryDateHelper->getGroupedDeliveryInformation($simulatorOrder,
            $profile);
        $arrProductCart = [];
        $arrProductCartFree = [];
        foreach ($groupedDeliveryInformation as $addressId => $arrInfoWithDL) {
            foreach ($arrInfoWithDL as $deliveryType => $arrDetailDL) {
                foreach ($arrDetailDL['product'] as $productCart) {
                    $product = $productCart['instance'];
                    if($productCart['is_free_gift']){
                        $arrProductCartFree[$product->getId()] = $productCart;
                    } else {
                        $arrProductCart[$product->getId()] = $productCart;
                    }
                }
            }
        }

        $items = [];
        /** @var \Riki\Subscription\Model\Emulator\Order\Item $orderItem */
        foreach ($simulatorOrder->getAllVisibleItems() as $orderItem) {
            if ($orderItem->getData('prize_id') || $orderItem->getData('is_riki_machine')) {
                continue;
            }

            $productModel = $orderItem->getProduct();

            $isFreeGift = $this->promoHelper->isPromoOrderItem($orderItem);
            $productCart = (isset($arrProductCart[$productModel->getId()]) && !$isFreeGift) ? $arrProductCart[$productModel->getId()] : null;
            if(!$productCart){
                (isset($arrProductCartFree[$productModel->getId()]) && $isFreeGift) ? $arrProductCartFree[$productModel->getId()] : null;
            }
            $stockMessageInfo = $this->getStockStatus($productModel);

            if ($productCart['unit_case'] == CaseDisplay::PROFILE_UNIT_CASE) {
                $unitQty = $productModel->getUnitQty() ? $productModel->getUnitQty() : 1;
            } else {
                $unitQty = 1;
            }
            $amount = $orderItem->getPriceInclTax() * $unitQty;

            $pid = $isFreeGift ? $productModel->getId() . '10'  : $productModel->getId() . '00';

            $items[$pid] = [
                'product_id' => $productModel->getId(),
                'name' => $orderItem->getName(),
                'qty' => $orderItem->getQtyOrdered(),
                'amount' => $amount,
                'unit_case' => $orderItem->getData('unit_case'),
                'unit_qty' => $orderItem->getData('unit_qty'),
                'product_cart_id' => $productCart['productcat_id'],
                'is_addition' => (int)$productCart['is_addition'],
                'is_free_gift' => (int)$isFreeGift,
                'is_spot' => (int)$productCart['is_spot'],
                'stock_status' => $stockMessageInfo['stock_status'],
                'stock_message' => $stockMessageInfo['stock_message'],
                'gw_id' => $orderItem->getData('gw_id'),
                'gw_data' => ($isFreeGift || !$productModel->getData('gift_wrapping'))? null : $this->getGiftWrappingAttributeArray($productModel->getData('gift_wrapping')),
                'thumbnail' => $this->imageBuilder->create($productModel,
                    'cart_page_product_thumbnail')->getImageUrl(),
                'label_list' => $this->getProductLabel($productModel)
            ];
        }

        return $items;
    }

    /**
     * @param $attributeString
     * @return mixed
     */
    public function getGiftWrappingAttributeArray($attributeString)
    {
        $arrayAttr = explode(',', $attributeString);
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('wrapping_id', $arrayAttr, 'in')->create();
        $wrappingData = $this->giftWrappingRepository->getList($searchCriteria);

        if (!$wrappingData->getTotalCount()) {
            return [];
        }

        $returnedArray = [];
        $returnedArray['totalRecords'] = $wrappingData->getTotalCount();

        $returnedArray['items'] = [];

        foreach ($wrappingData->getItems() as $item) {
            $itemToArray = $item->toArray();
            $itemToArray['price_incl_tax'] = $this->calTax($itemToArray["base_price"]);
            $returnedArray['items'][] = $itemToArray;
        }

        array_unshift($returnedArray['items'], [
            "wrapping_id" => -1,
            "gift_name" => __('None'),
            "price_incl_tax" => 0
        ]);

        return $returnedArray;
    }

    /**
     * @param $wrappingFee
     * @return mixed
     */
    public function calTax($wrappingFee)
    {
        $wrappingTax = $this->giftWrappingData->getWrappingTaxClass($this->storeManager->getStore());
        $wrappingRate = $this->taxCalculation->getCalculatedRate($wrappingTax);
        if ($wrappingFee > 0) {
            $taxRate = $wrappingRate/100;
            $wrappingFee = $wrappingFee + ($taxRate*$wrappingFee);
        }
        return (int)$wrappingFee ;
    }

    /**
     * Get stock status
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    public function getStockStatus($product)
    {
        $stockMessageInfo = ['stock_status' => 1, 'stock_message' => ''];

        $stockMessage = $this->subCourseResourceModel->getStockStatusMessage($product);

        if (array_key_exists('class', $stockMessage)
            && array_key_exists('message', $stockMessage)
        ) {
            $stockMessageInfo['stock_message'] = __('Stock:') .' '. $stockMessage['message'];
        }

        $isInStock = $product->getIsSalable();
        if ($isInStock == false) {
            $stockMessageInfo['stock_message'] = __('Stock:') .' '. $this->subCourseResourceModel->getOutStockMessageByProduct($product);
        }
        $stockMessageInfo['stock_status'] = $isInStock ? 1 : 0;

        return $stockMessageInfo;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     */
    private function getProductLabel($product)
    {
        $label = [];

        //Delivery Type
        $label[] = [
            'type' => $product->getDeliveryType()
        ];

        //Free label
        if ($product->getIsFreeShipping()) {
            $label[] = [
                'type' => 'free_shipping'
            ];
        }

        if ($this->catalogHelper->hasGiftWrapping($product)) {
            if ($this->catalogHelper->hasFreeGiftWrapping($product)) {
                $label[] = [
                    'type' => 'wrapping_free'
                ];

            } else {
                $label[] = [
                    'type' => 'wrapping_available'
                ];
            }
        }

        return $label;
    }

    private function getSimulatorOrderOfProfile($profile)
    {
        if ($this->simulateOrder == null) {
            if ($profile) {
                $simulatorOrder = $this->orderSimulatorHelper->createSimulatorOrderHasData($profile);
                if ($simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {

                    /* update data for profile cache simulate  */
                    $this->profileIndexerHelper->updateDataProfileCache($profile->getProfileId(), $simulatorOrder);
                    return $simulatorOrder;
                }
            }
        }

        return $this->simulateOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function applyProfileChange($profile, $customerId)
    {
        $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(),
            \Magento\Framework\App\Area::AREA_FRONTEND, true);

        if (!$this->profileHelperData->checkLeadTimeIsActiveForProfile($profile->getId())) {
            return $result['response'] = [
                'content' => [
                    'message' => [
                        'type' => 'error',
                        'text' => __('The product not available to ship to %1')
                    ]
                ]
            ];
        }

        /** @var Profile $profileOrigin */
        $profileOrigin = $this->profileFactory->create()->load($profile->getId());
        $course = $profileOrigin->getSubscriptionCourse();

        list($profileAddressId, $profileDeliveryDate, $profileDeliveryDateTimeSlot, $discountRate) = $this->getOriginAddressDeliveryInformation($profileOrigin);

        // Get product ids of profile origin.
        $productIdsOrigin = $this->getProductIdsFromProfileOrigin($profileOrigin, $profile->getId());

        $productCartData = [];
        $totalQty = 0;
        $productIds = [];

        try {
            /** @var \Riki\Subscription\Api\Data\Profile\ProductInterface $productCart */
            foreach ($profile->getProfileProductCart() as $productCart) {
                $productId = $productCart->getProductId();
                $qty = $productCart->getQty();

                // Check qty of product
                $this->validateSingleProduct($productId, $qty, $productIdsOrigin);
                $this->validateProductChanging($productId, $productIdsOrigin, $course);

                $cartProduct = $this->getProductCartData(
                    $productCart,
                    $productId,
                    $profileOrigin->getData('profile_id'),
                    $qty,
                    $profileAddressId,
                    $profileDeliveryDate,
                    $profileDeliveryDateTimeSlot,
                    $profileAddressId,
                    $discountRate
                );

                if ($cartProduct != false) {
                    $this->validateProductQtyChanging($profile->getId(), $productId, $cartProduct['qty'], $course);
                    $productCartKey = $productCart->getCartId()?? 'new_' . $productCart->getProductId();
                    $productCartData[$productCartKey] = $cartProduct;
                    $totalQty += $qty;
                    $productIds[$productId] = $qty;
                }
            }
        } catch (\Exception $e) {
            return $result['response'] = [
                'content' => [
                    'message' => [
                        'type' => 'error',
                        'text' => __($e->getMessage())
                    ]
                ]
            ];
        }

        //Profile Spot item ids
        $spotIds = $this->productCartFactory->create()->getSpotItemIds($profile->getId());

        try {
            $this->validateCartProduct($course, $totalQty, $productIds, $profileOrigin,
                $productIdsOrigin, $spotIds, $productCartData);
        } catch (\Exception $e) {
            return $result['response'] = [
                'content' => [
                    'message' => [
                        'type' => 'error',
                        'text' => $e->getMessage()
                    ]
                ]
            ];
        }

        $profileOrigin->setData('product_cart', $productCartData);

        if ($this->validateStockPointProduct->canCleanDataSpCarrier()) {
            $this->validateStockPointProduct->cleanDataStockPointSubCarrier($profileOrigin);
        }

        $changedData = [];
        $changedData['coupon_code'] = $profile->getCouponCode();

        $simulatorOrder = $this->orderSimulatorHelper->createMageOrderForAPI($profileOrigin->getId(), $productCartData,
            $profileOrigin->getCustomer(), $changedData);

        if ($simulatorOrder) {
            $validateResult = $this->subscriptionHelperOrder
                ->validateAmountRestriction(
                    $simulatorOrder,
                    $course,
                    $profileOrigin
                );
            if (!$validateResult['status']) {
                return $result['response'] = [
                    'content' => [
                        'message' => [
                            'type' => 'error',
                            'text' => $validateResult['message']
                        ]
                    ]
                ];
            }

            if ($profile->getCouponCode()) {
                if (empty($simulatorOrder->getCouponCode())) {
                    $isValid = false;
                } else {
                    $arrCouponCode = explode(',', $simulatorOrder->getCouponCode());
                    $isValid = in_array($profile->getCouponCode(), $arrCouponCode);
                }

                if (!$isValid) {
                    return $result['response'] = [
                        'content' => [
                            'message' => [
                                'type' =>'error',
                                'text' => __('Coupon code is not valid')
                            ]
                        ]
                    ];
                }
            }
        } else {
            return $result['response'] = [
                'content' => [
                    'message' => [
                        'type' => 'error',
                        'text' => __('Cannot simulate order information')
                    ]
                ]
            ];
        }

        // column type cannot use default timestamp_init
        $currentTime = $this->timezone->date(null, null, false)
            ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);

        try {
            $this->webApiProfileRepository->processProfileProductCart($profileOrigin->getId(),
                $profileOrigin->getProductCart(), false, $profileOrigin, $profileDeliveryDate, $currentTime, $spotIds);
            $profileOrigin->setCouponCode($profile->getCouponCode())->save();

            $hasBucketId = $profileOrigin->getData("delete_profile_has_bucket_id");
            if (!empty($hasBucketId)) {
                /** call api when remove stock point  */
                $resultApi = $this->stockPointHelper->removeFromBucket($profileOrigin->getProfileId());
                if (isset($resultApi['success']) && !$resultApi['success']) {
                    throw new LocalizedException(
                        __("There are something wrong in the system. Please re-try again.")
                    );
                }
            }

        } catch (LocalizedException $e) {
            return $result['response'] = [
                'content' => [
                    'message' => [
                        'type' => 'error',
                        'text' => $e->getMessage()
                    ]
                ]
            ];
        }

        return $result['response'] = [
            'message' => [
                'type' => 'success',
                'text' => __('Update profile successfully!')
            ],
            'content' => [
                'profile_data' => $this->getProfileDataResponse($profileOrigin, $simulatorOrder)
            ]
        ];
    }

    public function getProfileDataResponse($profile, $simulateOrder)
    {
        $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(),
            \Magento\Framework\App\Area::AREA_FRONTEND, true);

        $course = $this->getCourseById($profile->getCourseId());
        $courseSettings = $course->getSettings();

        $paymentMethod = $simulateOrder->getPayment()->getMethod();
        if ($profile->getPaymentMethod() == null) {
            $paymentMethod = null;
            $paymentMethodLabel = null;
        } elseif ($simulateOrder->getPayment()->getMethod() == Paygent::CODE) {
            $paymentMethodLabel  = 'クレジット支払い（前回使用）';
        } else {
            $paymentMethodLabel = $simulateOrder->getPayment()->getData('additional_information')['method_title'];
        }

        $termsOfUse =  $this->deliveryDateHelper->getTermsOfUseDownloadUrl($course->getData('terms_of_use'));
        if($termsOfUse) {
            $termsOfUse = $this->scopeConfig->getValue('web/secure/base_url') . $termsOfUse;
        }

        $simulatorOrderInformation = [
            'profile_id' => $profile->getProfileId(),
            'course_name' => $profile->getCourseData()['course_name'],
            'course_code' => $profile->getCourseData()['course_code'],
            'course_id' => $profile->getCourseData()['course_id'],
            'payment_method' => [
                'code' => $paymentMethod,
                'title' => $paymentMethodLabel
            ],
            'frequency' => sprintf(
                __("%s " . $profile->getFrequencyUnit()), $profile->getFrequencyInterval()
            ),
            'frequency_id' => $profile->getSubProfileFrequencyID(),
            'shipping_address_name' => $simulateOrder->getShippingAddress()->getRikiNickname(),
            'calendar_option' => $this->deliveryDateHelper->getProfileCalendarOption($simulateOrder, $profile, $course),
            'next_delivery_date' => $profile->getNextDeliveryDate(),
            'next_order_date' => $profile->getNextOrderDate(),
            'deadline' => date('Y-m-d', strtotime("-1 day", strtotime($profile->getNextOrderDate()))),
            'coupon_code' => $profile->getCouponCode() ?? '',
            'course_notice' => $this->getCourseNotice($profile, $course),
            'is_allow_change_next_delivery' => !!$courseSettings['is_allow_change_next_delivery'],
            'is_allow_change_payment_method' => !!$courseSettings['is_allow_change_payment_method'],
            'is_allow_change_address' => !!$courseSettings['is_allow_change_address'],
            'is_allow_change_item' => !!$courseSettings['is_allow_change_product'],
            'is_allow_change_qty' => !!$courseSettings['is_allow_change_qty'],
            'is_stockpoint' => !!$profile->getData('stock_point_profile_bucket_id'),
            'terms_of_use' => $termsOfUse,
            'is_monthly_fee' => $course->getSubscriptionType() == CourseType::TYPE_MONTHLY_FEE,
            'authorized_fail_message' => $this->getAuthorizeFailedMessage($profile),

            // Simulation order total
            'subtotal' => $simulateOrder->getSubtotalInclTax(),
            'grand_total' => $simulateOrder->getGrandTotal(),
            'shipping_fee' => $simulateOrder->getShippingInclTax(),
            'payment_fee' => (int)$simulateOrder->getFee(),
            'gift_wrapping_fee' => (int)$simulateOrder->getData('gw_items_base_price_incl_tax'),
            'discount_amount' => $simulateOrder->getData('discount_amount'),
            'point_used' => (int)$simulateOrder->getData('used_point_amount'),
            'point_earned' => $simulateOrder->getData('bonus_point_amount'),
            'list_rule_applied' => $this->deliveryDateHelper->getListRuleIdsApplied($simulateOrder)
        ];

        if($simulatorOrderInformation['is_stockpoint']){
            if($profile->getData('stock_point_delivery_type') != Profile::SUBCARRIER){
                $simulatorOrderInformation['is_allow_change_next_delivery'] = false;
            }
        }
        $simulatorOrderInformation['product_list'] = $this->getProfileProductList($profile, $simulateOrder);
        $totalQty = 0;
        if (count($simulatorOrderInformation['product_list']) > 0) {
            foreach ($simulatorOrderInformation['product_list'] as $productList) {
                $totalQty += $productList['unit_case'] == CaseDisplay::PROFILE_UNIT_CASE ? $productList['qty']/$productList['unit_qty'] : $productList['qty'];
            }
        }

        $simulatorOrderInformation['total_qty'] = $totalQty;

        $this->appEmulation->stopEnvironmentEmulation();

        return $simulatorOrderInformation;
    }

    public function getAuthorizeFailedMessage($profile)
    {
        $message = null;

        if ($this->profileHelperData->checkProfileHaveTmp($profile->getId()) && $profile->getPaymentMethod() === null) {
            $message = $this->deliveryDateHelper->getAuthorizedFailMessage($profile->getId());
        }

        return $message;
    }

    /**
     * @param \Riki\Subscription\Model\Data\Profile\Product $productCart
     * @param int $productId
     * @param int $mainProfileId
     * @param int $qty
     * @param int $addressId
     * @param $deliveryDate
     * @param $timeSlot
     * @param $shippingAddressId
     * @param float $discountRate
     * @return \Riki\Subscription\Model\Data\Profile\Product|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProductCartData(
        $productCart,
        $productId,
        $mainProfileId,
        $qty,
        $addressId,
        $deliveryDate,
        $timeSlot,
        $shippingAddressId,
        $discountRate
    ) {
        if ($productId) {
            $productModel = $this->productRepository->getById($productId);
            if ($productModel->getId()) {
                $productIds[] = $productId;

                $unitQty = $productModel->getData('unit_qty');
                $unitDisplay = $productModel->getData('case_display');

                $billingId = $productModel->getBillingAddressId() != 0
                    ? $productModel->getBillingAddressId()
                    : $addressId;

                $productCart->addData([
                    'profile_id' => $mainProfileId,
                    'qty' => $this->caseDisplay->getQtyPieceCaseForSaving($unitDisplay, $unitQty, $qty),
                    'product_type' => $productModel->getTypeId(),
                    'product_id' => $productId,
                    'shipping_address_id' => $shippingAddressId,
                    'billing_address_id' => $billingId,
                    'delivery_date' => $deliveryDate,
                    'delivery_time_slot' => $timeSlot,
                    'unit_case' => $this->caseDisplay->getCaseDisplayKey($unitDisplay),
                    'unit_qty' => $this->caseDisplay->validateQtyPieceCase($unitDisplay, $unitQty),
                    'stock_point_discount_rate' => $discountRate
                ]);

                return $productCart;
            }
        }

        return false;
    }

    /**
     * get product ids from profile origin.
     *
     * @param Profile $profileOrigin
     * @return array
     */
    public function getProductIdsFromProfileOrigin($profileOrigin)
    {
        $productIds = [];

        $productCarts = $profileOrigin->getProductCart();
        foreach ($productCarts as $productCart) {
            if (isset($productCart['product_id']) && $productCart['product_id']) {
                $productIds[] = $productCart['product_id'];
                $profileId = $productCart->getData('profile_id');
                $productId = $productCart->getData('product_id');
                $productQty = $productCart->getData('qty');
                $this->originProductCartData[$profileId][$productId]['qty'] = $productQty;
            }
        }

        return $productIds;
    }

    /**
     * @param Profile $profile
     * @return int|mixed
     */
    protected function getOriginAddressDeliveryInformation($profile)
    {
        /** @var \Riki\Subscription\Model\ProductCart\ProductCart $product */
        foreach ($profile->getProductCart() as $product) {
            $addressId = $product->getCurrentSelectedShippingAddress();
            $deliveryDate = $product->getNextDeliveryDate();
            $timeSlot = $product->getDeliveryTimeSlot();
            $discountRate = $product->getData('stock_point_discount_rate');
            return [$addressId, $deliveryDate, $timeSlot, $discountRate];
        }

        return 0;
    }

    protected function validateSingleProduct($id, $qty, $productIdsOrigin)
    {
        try {
            $product = $this->productRepository->getById($id);
            $stock = $product->getExtensionAttributes()->getStockItem();

            $min = (int)$stock->getData('min_sale_qty') ? $stock->getData('min_sale_qty') : 1;
            $max = (int)$stock->getData('max_sale_qty') ? $stock->getData('max_sale_qty') : $this->getMaxSaleQty();

            // Validate min & max sale qty
            if ($qty < $min || $qty > $max) {
                // group invalid products to show 1 mess only
                throw new \Exception(__("Product %1 quantity is invalid.", $product->getName()));
            } else {
                // Only validate stock of product not in profile origin.
                if (!in_array($id, $productIdsOrigin)) {
                    // Validate bundle product
                    if ($product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
                        $bundleStock = $this->helperInventory->checkWarehouseBundle($product, $qty, 0);
                        if (!$bundleStock) {
                            throw new \Exception(__("Product %1 is not saleable", $id));
                        }
                    } else {
                        // Validate simple product
                        if (($stock->getData('qty') < $qty || !$stock->getData('is_in_stock'))
                            && $stock->getData('backorders') == 0
                        ) {
                            throw new \Exception(__("Product %1 is not enough stock", $id));
                        }
                    }
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get max sale qty
     *
     * @return int|mixed
     */
    public function getMaxSaleQty()
    {
        $maxSaleQty = $this->scopeConfig->getValue(
            'cataloginventory/item_options/max_sale_qty',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (empty($maxSaleQty)) {
            $maxSaleQty = ProfileRepository::MAX_SALE_QTY;
        }

        return $maxSaleQty;
    }

    /**
     * Validate change product by subscription course setting
     * @param $productIdInput
     * @param $productIdsOrigin
     * @param $course
     */
    private function validateProductChanging($productIdInput, $productIdsOrigin, $course)
    {
        if (!in_array($productIdInput, $productIdsOrigin)) {
            if (!$course->getData('allow_change_product')) {
                throw new \Exception('This subscription course do not allow change product');
            }
        }
    }

    /**
     * Validate change qty by subscription course setting
     * @param $profileId
     * @param $inputProductId
     * @param $qtyPieceCase
     * @param $course
     */
    private function validateProductQtyChanging($profileId, $inputProductId, $qtyPieceCase, $course)
    {
        if (isset($this->originProductCartData[$profileId][$inputProductId]['qty'])) {
            if ($this->originProductCartData[$profileId][$inputProductId]['qty'] != $qtyPieceCase) {
                if (!$course->getData('allow_change_qty')) {
                    throw new \Exception('This subscription course do not allow change quantity');
                }
            }
        }
    }

    /**
     * Validate Cart Product
     * - Must select Category SKU
     * - Minimum Order Qty
     * - Only SPOT exist
     *
     * @param \Riki\SubscriptionCourse\Model\Course $course
     * @param int $totalQty
     * @param array $productIds
     * @param $profile
     * @param $productIdsOrigin
     * @param $spotIds
     * @param $productCartData
     * @return array
     * @throws \Exception
     */
    protected function validateCartProduct($course, $totalQty, $productIds, $profile, $productIdsOrigin, $spotIds, $productCartData)
    {
        $mustSelectCartData = $course->getMustSelectSku();
        if ($mustSelectCartData) {
            $mustSelectCarts = explode(':', $mustSelectCartData);
            if (count($mustSelectCarts) == 2) {
                $mustSelectCartId = $mustSelectCarts[0];
                $mustSelectProductQty = $mustSelectCarts[1];
                $cate = $this->categoryFactory->create()->load($mustSelectCartId);
                if (!$this->subscriptionCourseHelper->isValidMustHaveQtyInCategory($productIds, $mustSelectCartData)) {
                    throw new \Exception( __('You need to purchase %1 items of %2', $mustSelectProductQty,
                        $cate->getName()));
                }
            }
        }

        if ($course->getMinimumOrderQty() && $totalQty < $course->getMinimumOrderQty()) {
            throw new \Exception(
                __('You need to add at least %1 products', (int)$course->getMinimumOrderQty())
            );
        }

        $productIds = array_keys($productIds);
        $notSpot = array_diff($productIds, $spotIds);
        if (!$notSpot) {
            throw new \Exception(__('Profile has only SPOT item. You must add at least one subscription item'));
        }

        if (!$course->getData('allow_change_product')) {
            $deletedProductIds = array_diff($productIdsOrigin, $productIds);
            if (!empty($deletedProductIds)) {
                throw new \Exception(__('This subscription course do not allow change product'));
            }
        }

        $resultValidate= $this->subscriptionValidator->setProfileId($profile->getId())
            ->setProductCarts($productCartData)
            ->validateMaximumQtyRestriction();

        if ($resultValidate['error']) {
            $message = $this->subscriptionValidator->getMessageMaximumError(
                $resultValidate['product_errors'],
                $resultValidate['maxQty']
            );

            throw new \Exception($message);
        }


        // Validate Stock point
        $resultValidate = $this->checkStockPoint($profile, $productCartData);
        if ($resultValidate['error']) {
            throw new \Exception($resultValidate['message']);
        }
    }

    /**
     *
     * @param $profile
     * @param array $productCartData
     * @return array
     */
    public function checkStockPoint($profile, $productCartData)
    {
        $resultValidate = ['error' => false, 'message' => ''];

        $rikiStockPoint = $profile->getData('riki_stock_point_id');
        $existStockPoint = $profile->getData('stock_point_profile_bucket_id');

        if ((int)$existStockPoint > 0 || $rikiStockPoint) {
            $isValid = true;

            /**
             * Check all allow stock point
             */
            $errorStockPoint = $this->profileSaveHelper->_validateAllProductAllowStockPoint($productCartData, $profile);
            if (!empty($errorStockPoint)) {
                $text1 = "Stock Point is not allowed for these products [%s].";
                $text2 = "Please remove them from cart before you choose to deliver with Stock Point.";
                return [
                    'error' => true,
                    'message' => sprintf(__($text1 . $text2), implode(',', $errorStockPoint))
                ];
            }

            /**
             * All products are in stock on Hitachi
             */
            if (!$this->profileSaveHelper->_validateProductInventoryForStockPoint($existStockPoint)) {
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
                    'error' => true,
                    'message' => __("Sorry, MACHI ECO flights can not be used with the items you purchase / payment method.")
                ];
            }
        }

        return $resultValidate;
    }

    /**
     * {@inheritdoc}
     */
    public function applyCouponCode($profileId, $couponCode)
    {
        $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(),
            \Magento\Framework\App\Area::AREA_FRONTEND, true);

        if ($couponCode && strpos($couponCode, ',')) {
            $result['response'] = [
                'content' => [
                    'message' => [
                        'type' =>'error',
                        'text' => __('Coupon code is not valid')
                    ]
                ]
            ];

            return $result;
        }

        /** @var \Riki\Subscription\Model\Profile\Profile $profileOrigin */
        $profileOrigin = $this->profileFactory->create()->load($profileId);
        $arrProductCart = $profileOrigin->getProductCart();

        $defaultShippingAddressId = null;
        foreach ($arrProductCart as $productCart) {
            $defaultShippingAddressId = $productCart->getShippingAddressId();
            break;
        }

        $shippingAddressModel = $this->customerAddressRepository->getById($defaultShippingAddressId);
        /** @var \Magento\Quote\Model\Quote\Address $shippingAddress */
        $shippingAddress = $this->quoteAddressFactory->create();
        $shippingAddress = $shippingAddress->importCustomerAddressData($shippingAddressModel);

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();
        $shippingAddress->setQuote($quote);

        $coupon = $this->couponFactory->create()->loadByCode($couponCode);
        $ruleId = $coupon->getRuleId();

        /** @var \Magento\SalesRule\Model\Rule $rule */
        $rule = $this->ruleFactory->create()->load($ruleId);

        $quote->setCouponCode($couponCode);

        if ($rule->getId() && $this->couponHelper->canProcessRuleForAddressAndCustomer($rule, $shippingAddress)) {
            $result['response'] = [
                'content' => [
                    'message' => [
                        'type' => 'success',
                        'text' =>__("The coupon code has been accepted.")
                    ]
                ]
            ];
        } else {
            $result['response'] = [
                'content' => [
                    'message' => [
                        'type' =>'error',
                        'text' => __('Coupon code is not valid')
                    ]
                ]
            ];
        }

        $this->appEmulation->stopEnvironmentEmulation();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function removeCouponCode($profileId, $couponCode)
    {
        $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(),
            \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $arrData = $this->couponSimulator->couponApplied($profileId, $couponCode, 'delete');

        $this->appEmulation->stopEnvironmentEmulation();

        return $result['response'] = [
            'content' => [
                'content' => [
                    'message' => [
                        'type' => $arrData['is_validate'] ? 'success' : 'error',
                        'text' => $arrData['message']
                    ]
                ]
            ]
        ];
    }
}