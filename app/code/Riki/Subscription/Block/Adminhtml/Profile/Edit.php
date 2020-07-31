<?php
namespace Riki\Subscription\Block\Adminhtml\Profile;

use Bluecom\PaymentFee\Model\PaymentFeeFactory;
use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Model\Profile\Profile;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Helper\Data as SubscriptionHelperData;
use \Magento\Catalog\Model\Product\Type as ProductType;
use Riki\Questionnaire\Model\QuestionnaireAnswer;
use Riki\SubscriptionCourse\Model\Course\Source\QtyRestrictionOptions;

/**
 * Customer gift registry edit block
 * @property PaymentFeeFactory paymentFeeFactory
 */
class Edit extends \Magento\Backend\Block\Template
{
    const XML_PATH_TAX_ORDER_DISPLAY_CONFIG = 'tax/sales_display/shipping';

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $helperImage;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;

    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $deliveryDate;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface
     */
    protected $wrappingRepository;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $giftWrappingData;

    /**
     * @var \Magento\GiftMessage\Model\MessageFactory
     */
    protected $messageFactory;

    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface
     */
    protected $adjustmentCalculator;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $helperSimulator;

    /**
     * @var \Riki\ProductStockStatus\Helper\StockData
     */
    protected $stockData;

    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $deliveryHelper;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $courseHelper;

    /**
     * @var \Riki\Subscription\Helper\Data
     */
    protected $subHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subPageHelper;

    /**
     * @var \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    protected $calculateDeliveryDateHelper;

    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $mediaConfig;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Address\CollectionFactory
     */
    protected $customerAddressCollectionFactory;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    private $extensibleDataObjectConverter;

    /**
     * @var SubscriptionHelperData
     */
    protected $subHelperData;

    /**
     * @var \Magento\Customer\Model\AddressRegistry
     */
    protected $addressRegistry;

    /**
     * @var \Magento\Framework\Locale\FormatInterface $localeFormat
     */
    protected $localeFormat;

    /**
     * @var \Riki\Subscription\Model\Profile\FreeGift
     */
    protected $freeGiftManagement;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $timezoneHelper;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Model\Config\Source\Reason
     */
    protected $disengageReason;

    /**
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $paymentFeeHelper;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    protected $productCartFactory;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $mageCustomerRepository;

    /**
     * @var \Riki\BackOrder\Helper\Data
     */
    protected $backOrderHelper;

    /**
     * @var \Riki\Customer\Helper\Address
     */
    protected $customerAddressHelper;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Riki\Subscription\Helper\Profile\ProfileSessionHelper
     */
    protected $profileSessionHelper;

    /**
     * @var \Riki\PointOfSale\Model\Config\Source\PointOfSale
     */
    protected $pointOfSale;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteria;

    protected $isDisabledAll;
    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    protected $helperStockPoint;
    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;
    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;
    /**
     * @var \Riki\SubscriptionFrequency\Helper\Data
     */
    protected $frequencyHelper;

    protected $isShowStockPoint = null;
    /**
     * @var \Magento\Customer\Api\Data\RegionInterfaceFactory
     */
    protected $regionDataFactory;
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;
    /**
     * @var \Riki\Customer\Helper\Region
     */
    protected $regionHelper;

    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $promoHelper;

    /**
     * @var \Riki\Subscription\Helper\Indexer\Data
     */
    protected $profileIndexerHelper;

    /**
     * @var
     */
    protected $defaultAddressId;


    /**
     * @var \Riki\SubscriptionProfileDisengagement\Model\Reason
     */
    protected $reasonModel;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Question\CollectionFactory
     */
    protected $questionCollectionFactory;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory
     */

    protected $choiceCollectionFactory;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Answers\CollectionFactory
     */
    protected $answersCollectionFactory;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Reply\CollectionFactory
     */
    protected $replyCollectionFactory;
    /**
     * @var Profile
     */
    protected $profileModel;


    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * Edit constructor.
     * @param \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper
     * @param SubscriptionHelperData $subscriptionHelperData
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $customerAddressCollectionFactory
     * @param \Magento\Catalog\Helper\Image $helperImage
     * @param \Riki\Loyalty\Model\RewardManagement $rewardManagemnt
     * @param \Magento\Catalog\Model\Product\Media\Config $mediaConfig
     * @param \Riki\DeliveryType\Model\DeliveryDate $deliveryDate
     * @param PaymentFeeFactory $paymentFeeFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingData
     * @param \Magento\GiftMessage\Model\MessageFactory $messageFactory
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Riki\Subscription\Helper\Order\Simulator $helperSimulator
     * @param \Riki\ProductStockStatus\Helper\StockData $stockData
     * @param \Riki\DeliveryType\Helper\Data $deliveryHelper
     * @param \Riki\SubscriptionCourse\Helper\Data $courseHelper
     * @param SubscriptionHelperData $subHelper
     * @param \Riki\SubscriptionPage\Helper\Data $subPageHelper
     * @param \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDateHelper
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Magento\Customer\Model\AddressRegistry $addressRegistry
     * @param \Riki\Subscription\Model\Profile\FreeGift $freeGift
     * @param \Riki\SubscriptionProfileDisengagement\Model\Config\Source\Reason $disengageReason
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory
     * @param \Riki\Customer\Model\CustomerRepository $customerRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $mageCustomerRepository
     * @param \Bluecom\Paygent\Model\PaygentHistory $paygentHistory
     * @param \Riki\BackOrder\Helper\Data $backOrderHelper
     * @param \Riki\Customer\Helper\Address $customerAddressHelper
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Riki\Subscription\Helper\Profile\ProfileSessionHelper $profileSessionHelper
     * @param \Riki\PointOfSale\Model\Config\Source\PointOfSale $pointOfSale
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Subscription\Helper\StockPoint\Data $helperStockpoint
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory
     * @param \Riki\Customer\Helper\Region $regionHelper
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param \Riki\Promo\Helper\Data $promoHelper
     * @param \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper
     * @param \Riki\SubscriptionProfileDisengagement\Model\Reason $reasonModel
     * @param \Riki\Subscription\Model\Profile\Profile $profileModel
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param array $data
     */
    public function __construct(
        \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper,
        SubscriptionHelperData $subscriptionHelperData,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $customerAddressCollectionFactory,
        \Magento\Catalog\Helper\Image $helperImage,
        \Riki\Loyalty\Model\RewardManagement $rewardManagemnt,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
        PaymentFeeFactory $paymentFeeFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository,
        \Magento\GiftWrapping\Helper\Data $giftWrappingData,
        \Magento\GiftMessage\Model\MessageFactory $messageFactory,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Riki\Subscription\Helper\Order\Simulator $helperSimulator,
        \Riki\ProductStockStatus\Helper\StockData $stockData,
        \Riki\DeliveryType\Helper\Data $deliveryHelper,
        \Riki\SubscriptionCourse\Helper\Data $courseHelper,
        \Riki\Subscription\Helper\Data $subHelper,
        \Riki\SubscriptionPage\Helper\Data $subPageHelper,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDateHelper,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Customer\Model\AddressRegistry $addressRegistry,
        \Riki\Subscription\Model\Profile\FreeGift $freeGift,
        \Riki\SubscriptionProfileDisengagement\Model\Config\Source\Reason $disengageReason,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Riki\Customer\Model\CustomerRepository $customerRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $mageCustomerRepository,
        \Bluecom\Paygent\Model\PaygentHistory $paygentHistory,
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        \Riki\Customer\Helper\Address $customerAddressHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Riki\Subscription\Helper\Profile\ProfileSessionHelper $profileSessionHelper,
        \Riki\PointOfSale\Model\Config\Source\PointOfSale $pointOfSale,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Helper\StockPoint\Data $helperStockpoint,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory,
        \Riki\Customer\Helper\Region $regionHelper,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Riki\Promo\Helper\Data $promoHelper,
        \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper,
        \Riki\SubscriptionProfileDisengagement\Model\Reason $reasonModel,
        \Riki\Questionnaire\Model\ResourceModel\Question\CollectionFactory $questionCollectionFactory,
        \Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory $choiceCollectionFactory,
        \Riki\Questionnaire\Model\ResourceModel\Answers\CollectionFactory $answersCollectionFactory,
        \Riki\Questionnaire\Model\ResourceModel\Reply\CollectionFactory $replyCollectionFactory,
        Profile $profileModel,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        array $data = []
    ) {
        $this->regionHelper = $regionHelper;
        $this->regionDataFactory = $regionDataFactory;
        $this->regionFactory = $regionFactory;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->helperStockPoint = $helperStockpoint;
        $this->paymentFeeHelper = $paymentFeeHelper;
        $this->subHelperData = $subscriptionHelperData;
        $this->adjustmentCalculator = $subscriptionHelperData->getAdjustmentCalculator();
        $this->customerFactory = $customerFactory;
        $this->mediaConfig = $mediaConfig;
        $this->rewardManagement = $rewardManagemnt;
        $this->scopeConfig = $context->getScopeConfig();
        $this->helperImage = $helperImage;
        $this->helperProfile = $helperProfile;
        $this->customerAddressCollectionFactory = $customerAddressCollectionFactory;
        $this->deliveryDate = $deliveryDate;
        $this->paymentFeeFactory = $paymentFeeFactory;
        $this->productRepository = $productRepository;
        $this->wrappingRepository = $wrappingRepository;
        $this->giftWrappingData = $giftWrappingData;
        $this->messageFactory = $messageFactory;
        $this->taxCalculation = $taxCalculation;
        $this->helperSimulator = $helperSimulator;
        $this->registry = $registry;
        $this->stockData = $stockData;
        $this->deliveryHelper =  $deliveryHelper;
        $this->courseHelper = $courseHelper;
        $this->subHelper = $subHelper;
        $this->subPageHelper = $subPageHelper;
        $this->calculateDeliveryDateHelper = $calculateDeliveryDateHelper;
        $this->_request = $context->getRequest();
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->addressRegistry = $addressRegistry;
        $this->localeFormat = $localeFormat;
        $this->freeGiftManagement = $freeGift;
        $this->disengageReason = $disengageReason;
        $this->timezoneHelper  = $timezone;
        $this->productCartFactory = $productCartFactory;
        $this->customerRepository = $customerRepository;
        $this->mageCustomerRepository = $mageCustomerRepository;
        $this->paygentHistory = $paygentHistory;
        $this->backOrderHelper = $backOrderHelper;
        $this->customerAddressHelper = $customerAddressHelper;
        $this->addressRepository = $addressRepository;
        $this->profileSessionHelper = $profileSessionHelper;
        $this->pointOfSale = $pointOfSale;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->frequencyHelper = $frequencyHelper;
        $this->courseRepository = $courseRepository;
        $this->promoHelper = $promoHelper;
        $this->profileIndexerHelper = $profileIndexerHelper;
        $this->reasonModel = $reasonModel;
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->choiceCollectionFactory = $choiceCollectionFactory;
        $this->answersCollectionFactory = $answersCollectionFactory;
        $this->replyCollectionFactory = $replyCollectionFactory;
        $this->profileModel = $profileModel;
        $this->directoryList = $directoryList;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function canAddProduct()
    {
        return !$this->isDisengaged() && $this->getEntity()->getData('status');
    }

    /**
     * @param $product
     * @return string
     */
    public function getProductPrice($product)
    {
        if ($product->getTypeId() != 'bundle') {
            $finalPrice = $product->getFinalPrice(1) ?: 0;
            $amount = $this->adjustmentCalculator->getAmount($finalPrice, $product)->getValue();
            return $amount?$this->formatCurrency($amount):'-';
        } else {
            $price = $this->subHelperData->getBundleMaximumPrice($product);
            return $price ? $this->formatCurrency($price) : '-';
        }
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->registry->registry('subscription_profile');
    }

    /**
     * @return \Magento\Framework\App\State
     */
    public function getAppState()
    {
        return $this->_appState;
    }

    public function getCurrentCourse()
    {
        return $this->getEntity()->getCourseData();
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Subscription Edit'));
    }

    /**
     * @return string
     */
    public function getMassAddUrl()
    {
        return $this->getUrl('profile/profile/massadd', ['id'   =>  $this->getEntity()->getProfileId()]);
    }

    /**
     * get add coupon url
     *
     * @return string
     */
    public function getAddCouponUrl()
    {
        return $this->getUrl('profile/profile/couponAdd', ['id'   =>  $this->getEntity()->getProfileId()]);
    }

    /**
     * get delete coupon url
     *
     * @return string
     */
    public function getDeleteCouponUrl()
    {
        return $this->getUrl('profile/profile/couponDelete', ['id'   =>  $this->getEntity()->getProfileId()]);
    }

    /**
     * get change warehouse url
     *
     * @return string
     */
    public function getChangeWarehouseUrl()
    {
        return $this->getUrl('profile/profile/changeWarehouse', ['id'   =>  $this->getEntity()->getProfileId()]);
    }

    /**
     * Get edit URL with main profile ID
     * @return string
     */
    public function getEditUrl()
    {
        return $this->getUrl('profile/profile/edit', ['id'   =>  $this->getProfileId()]);
    }
    /**
     * Get list available payment method
     * @return mixed
     */
    public function getListPaymentMethod()
    {
        $obj = $this->registry->registry('subscription_profile_obj');

        return $obj->getListPaymentMethodAvailable();
    }

    /**
     * @param $price
     * @param null $websiteId
     * @return mixed
     */
    public function formatCurrency($price, $websiteId = null)
    {
        return $this->_storeManager->getWebsite($websiteId)->getBaseCurrency()->format($price);
    }

    /**
     * @param null $simulatorOrder
     * @return array
     * @throws LocalizedException
     */
    public function getListProductByAddressAndByDeliveryType($simulatorOrder = null)
    {
        if (!$simulatorOrder) {
            return [];
        }

        $objSessionProfile = $this->getEntity();

        $arrProductCart = [];

        foreach ($objSessionProfile->getData('product_cart') as $profileItem) {
            $arrProductCart[$profileItem['product_id']] = $profileItem;
        }

        $returnData = [];
        $this->checkSimulatorOrderData($simulatorOrder->getData(), $simulatorOrder->getAllVisibleItems(), $arrProductCart);
        /** @var \Riki\Subscription\Model\Emulator\Order\Item $orderItem */
        foreach ($simulatorOrder->getAllVisibleItems() as $orderItem) {
            if ($orderItem->getData('prize_id') || $orderItem->getData('is_riki_machine')) {
                $this->_logger->info('NED-8091 product is prize or riki machine');
                continue;
            }

            $product = $orderItem->getProduct();
            $productId = $product->getId();

            // Fix bug for case validate maximum qty restriction.
            // Add wrong qty of free gift to main product when is same product id.
            $isFreeGift = $this->promoHelper->isPromoOrderItem($orderItem);
            $objProductData = (isset($arrProductCart[$productId]) && !$isFreeGift) ? $arrProductCart[$productId] : null;

            $deliveryType = $this->getProductDeliveryType($product);

            $addressId = $objProductData ?
                $this->getShippingAddressIdFromProfileItem($objProductData)
                : $this->getDefaultAddressId();

            if (!isset($returnData[$addressId][$deliveryType])) {
                $returnRecord = [];

                $objAddress = $this->getAddressById($addressId);

                if (!$objAddress) {
                    $this->_logger->info('NED-8091 not exits object address');
                    return $returnData;
                }

                $returnRecord['name'] = !empty($objAddress->getCustomAttribute('riki_nickname'))
                    ? $objAddress->getCustomAttribute('riki_nickname')->getValue()
                    : '';

                $lastnameKana = $objAddress->getCustomAttribute('lastnamekana') ?
                    $objAddress->getCustomAttribute('lastnamekana')->getValue() : '';
                $firstnameKana = $objAddress->getCustomAttribute('firstnamekana') ?
                    $objAddress->getCustomAttribute('firstnamekana')->getValue() : '';

                $returnRecord['info'] = [
                    $objAddress->getLastname(),
                    $objAddress->getFirstname(),
                    $lastnameKana,
                    $firstnameKana,
                    $objAddress->getPostcode(),
                    $objAddress->getRegion(),
                    implode(' ', $objAddress->getStreet()),
                    $objAddress->getTelephone()
                ];

                $addressStockPoint = $this->getAddressStockPointDisplay();
                if (isset($addressStockPoint['region_id'])) {
                    $objAddressStockPoint = clone $objAddress;

                    foreach ($addressStockPoint as $name => $value) {
                        $objAddressStockPoint->setData($name, $value);
                    }

                    $region = $this->getRegionById($addressStockPoint['region_id']);
                    $objAddressStockPoint->setRegion($region);
                    $objAddressStockPoint->setCompany(null);
                    $objAddressStockPoint->setCity(null);

                    $returnRecord['address_html'] = $this->customerAddressHelper
                        ->formatCustomerAddressToString($objAddressStockPoint);
                } else {
                    $returnRecord['address_html'] = $this->customerAddressHelper
                        ->formatCustomerAddressToString($objAddress);
                }

                if ($stockPointData = $this->getStockPointPostData()) {
                    if ($stockPointData["comment_for_customer"]) {
                        $returnRecord['address_html'] = $returnRecord['address_html'] . "</br></br>" .
                            $stockPointData["comment_for_customer"];
                    }
                } elseif ($objSessionProfile->getData('stock_point_delivery_information')) {
                    $returnRecord['address_html'] = $returnRecord['address_html'] . "</br></br>" .
                        $objSessionProfile->getData('stock_point_delivery_information');
                }

                $returnRecord['delivery_date'] = [
                    'next_delivery_date' => $objProductData ?
                        $objProductData->getData('delivery_date') : $orderItem->getData('delivery_date'),
                    'time_slot' => $objProductData ?
                        $objProductData->getData('delivery_time_slot') : $orderItem->getData('delivery_time_slot'),
                ];

                $returnData[$addressId][$deliveryType] = $returnRecord;
            }
            /* convert product data */
            $flatProductData = $this->extensibleDataObjectConverter->toNestedArray(
                $product,
                [],
                \Magento\Catalog\Api\Data\ProductInterface::class
            );

            $flatProductData["stock_message"] = $this->getStockStatus($product);
            $flatProductData["thumbnail"] = $this->getProductImagesProfile($product);

            $returnData[$addressId][$deliveryType]['product'][] = [
                'name' => $orderItem->getName(),
                'price' => $product->getPrice(),
                'qty' => $orderItem->getQtyOrdered(),
                'unit_case' => $orderItem->getData('unit_case'),
                'unit_qty' => $orderItem->getData('unit_qty'),
                'gw_id' => $orderItem->getData('gw_id'),
                'gift_message_id' => $orderItem->getData('gift_message_id'),
                'product_data' => $flatProductData,
                'instance' => $product,
                'productcat_id' => $objProductData ? $objProductData->getData('cart_id') : null,
                'productcart_data' => $objProductData?
                    $objProductData->toArray():$orderItem->setQty($orderItem->getQtyOrdered())->getData(),
                'gw_data' => $isFreeGift? [] : $this->getAttributeArray($product->getData('gift_wrapping')),
                'has_gw_data' => $isFreeGift? false : $product->hasData('gift_wrapping'),
                'has_gift_message' => $product->hasData('gift_message_id'),
                'gift_message_data' => $this->getMessage($product->getData('gift_message_id')),
                'amount' => $orderItem->getPriceInclTax(),
                'is_free_gift' => $isFreeGift,
                'allow_seasonal_skip' => $product->getData('allow_seasonal_skip'),
                'seasonal_skip_optional' => $product->getData('seasonal_skip_optional'),
                'allow_skip_from' => $this->getDateFormat($product->getData('allow_skip_from')),
                'allow_skip_to' => $this->getDateFormat($product->getData('allow_skip_to')),
                'is_skip' => $objProductData ? (int)$objProductData->getData('is_skip_seasonal') : 0,
                'skip_from' => $objProductData ? $objProductData->getData('skip_from') : null,
                'skip_to' => $objProductData ? $objProductData->getData('skip_to') : null,
                'is_addition' => $objProductData ? $objProductData->getData('is_addition') : null
            ];
        }

        return $returnData;
    }

    /**
     * @param $simulatorOrder
     * @param $allItems
     * @param bool $objectSessionProfile
     */
    public function checkSimulatorOrderData($simulatorOrder, $allItems, $objectSessionProfile = false)
    {
        try
        {
            if ($simulatorOrder['profile_id'] == 2002398785)
            {
                $this->_logger->info('NED-8019 simulator order: '.json_encode($simulatorOrder));
                if ($objectSessionProfile)
                {
                    foreach ($objectSessionProfile as $sessionProfile)
                    {
                        $this->_logger->info('NED-8019 object session profile: '.json_encode($sessionProfile->getData()));
                    }
                }
                foreach ($allItems as $item)
                {
                    $this->_logger->info('NED-8019 item data: '.json_encode($item->getData()));
                }
            }
        } catch (\Exception $e)
        {
            return;
        }
    }

    /**
     * @param \Magento\Framework\DataObject $profileItem
     * @return mixed
     */
    private function getShippingAddressIdFromProfileItem(
        \Magento\Framework\DataObject $profileItem
    ) {
        $stockPointData = $this->getEntity()->getData("stock_point_data");

        if (is_array($stockPointData) && isset($stockPointData['magento_data']['address_id'])) {
            return $stockPointData['magento_data']['address_id'];
        }

        return $profileItem->getData(Profile::SHIPPING_ADDRESS_ID);
    }

    /**
     * @return int
     */
    public function getDefaultAddressId()
    {
        if (is_null($this->defaultAddressId)) {
            $this->defaultAddressId = $this->freeGiftManagement->getDefaultShippingAddressFromProfileItems(
                $this->getEntity()->getData('product_cart')
            );
        }

        return $this->defaultAddressId;
    }

    /**
     * get region object data
     * @param $regionId
     * @return RegionInterface
     * @throws LocalizedException
     */
    public function getRegionById($regionId)
    {
        /** @var RegionInterface $region */
        $regionModel = $this->regionFactory->create()->load($regionId);
        if (!empty($regionModel)) {
            /** @var RegionInterface $region */
            $region = $this->regionDataFactory->create();
            $region->setRegion($regionModel->getDefaultName())
                ->setRegionCode($regionModel->getCode())
                ->setRegionId($regionModel->getRegionId());
            return $region;
        }
        return false;
    }

    /**
     * @return $this|bool|null
     */
    public function getListProductOfCourse()
    {
        $subscriptionCourseResourceModel = $this->helperProfile->getSubscriptionCourseResourceModel();
        $products = $subscriptionCourseResourceModel->getAllProductByCourse(
            $this->getEntity()->getData("course_id"),
            $this->getEntity()->getData("store_id")
        );
        return $products;
    }

    /**
     * @return array
     */
    public function getAllAddress()
    {
        // Get all Address of current customer
        $customerId = $this->getEntity()->getData("customer_id");

        $objCollection = $this->customerAddressCollectionFactory->create()
            ->addAttributeToFilter("parent_id", $customerId);

        $arrReturn = [];
        /** @var \Magento\Customer\Model\Address $objAddress */
        foreach ($objCollection as $objAddress) {
            $addressDataModel = $this->addressRepository->getById($objAddress->getId());
            $arrAddr = [
                $objAddress->getStreetLine(1),
                $objAddress->getCity(),
                $objAddress->getPostcode(),
                $objAddress->getRegion(),
            ];

            /* get first name kana & lastname kana */
            if ($firstnamekana = $addressDataModel->getCustomAttribute('firstnamekana')) {
                $firstnamekana = $firstnamekana->getValue();
            } else {
                $firstnamekana = '';
            }

            /* get first name kana & lastname kana */
            if ($lastnamekana = $addressDataModel->getCustomAttribute('lastnamekana')) {
                $lastnamekana = $lastnamekana->getValue();
            } else {
                $lastnamekana = '';
            }
            $addressStockPoint = $this->getAddressStockPointDisplay();
            if (!empty($addressStockPoint)) {
                $arrAddr = [
                    $addressStockPoint['lastname'] . ' ' . $addressStockPoint['firstname'],
                    $addressStockPoint['street'][0],
                    $this->buildStockPointPostData->getRegionNameById($addressStockPoint['region_id'])
                ];
            }

            $arrReturn[] = [
                "name" => $this->helperProfile->getAddressNameByAddressId($objAddress->getId()),
                "address_data" => implode(", ", $arrAddr),
                "address_id" => $objAddress->getId(),
                "info" => [
                    $objAddress->getData('lastname'),
                    $objAddress->getData('firstname'),
                    $lastnamekana,
                    $firstnamekana,
                    $objAddress->getPostcode(),
                    $objAddress->getRegion(),
                    $objAddress->getCity(),
                    $objAddress->getStreetLine(1),
                    $objAddress->getTelephone()
                ],
                'address_html'  =>  $this->customerAddressHelper->formatCustomerAddressToString($addressDataModel)
            ];
        }
        return $arrReturn;
    }

    /**
     * get address of stock point
     * @return array
     */
    public function getAddressStockPointDisplay()
    {
        $addressData = [];
        if ($this->isAllowShowAddressStockPoint()) {
            /**
             * Change current shipping if selected stock point or is stock point
             */
            if ($this->getEntity()->getData("riki_stock_point_id") &&
                $this->getEntity()->getData("stock_point_data")["delivery_type"]
            ) {
                $stockPointData = $this->getEntity()->getData("stock_point_data");
                $regionId =$this->regionHelper->getRegionIdByName(trim($stockPointData["stock_point_prefecture"]));
                $addressData = [
                    'firstname' => $stockPointData["stock_point_firstname"],
                    'lastname' => $stockPointData["stock_point_lastname"],
                    'street' => [$stockPointData["stock_point_address"]],
                    'region_id' => $regionId,
                    'postcode' => $stockPointData["stock_point_postcode"],
                    'telephone' =>$stockPointData["stock_point_telephone"]
                ];
            } elseif ($this->getEntity()->getData("stock_point_profile_bucket_id") &&
                $this->getEntity()->getData("stock_point_delivery_type")
            ) {
                $stockPoint = $this->helperStockPoint->getStockPointByBucketId(
                    $this->getEntity()->getData("stock_point_profile_bucket_id")
                );
                if ($stockPoint) {
                    $addressData = [
                        'firstname' => $stockPoint->getFirstname(),
                        'lastname' =>  $stockPoint->getLastname(),
                        'street' => [$stockPoint->getStreet()],
                        'region_id' => $stockPoint->getRegionId(),
                        'postcode' => $stockPoint->getPostcode(),
                        'telephone' => $stockPoint->getTelephone()
                    ];
                }
            }
        }
        return $addressData;
    }
    /**
     * Check if delivery type = locker or pickup then enable
     */
    public function isAllowShowAddressStockPoint()
    {
        if (!$this->helperStockPoint->isEnable()) {
            return false;
        }
        if (isset($this->getEntity()->getData("stock_point_data")["delivery_type"]) &&
            (
                $this->getEntity()->getData("stock_point_data")["delivery_type"] == Profile::LOCKER ||
                $this->getEntity()->getData("stock_point_data")["delivery_type"] == Profile::PICKUP
            )
        ) {
            return true;
        } elseif ($this->getEntity()->getData("stock_point_delivery_type") == Profile::LOCKER ||
            $this->getEntity()->getData("stock_point_delivery_type") == Profile::PICKUP
        ) {
            return true;
        }
        return false;
    }
    /**
     * @param $addressKey
     * @return mixed
     */
    public function getCustomerAddressByText($addressKey)
    {
        // Get all Address of current customer
        $customerId = $this->getEntity()->getData("customer_id");

        $objCollection = $this->customerAddressCollectionFactory->create()
            ->addAttributeToFilter("parent_id", $customerId);

        $objCollection->load();

        $arrReturn = [];
        foreach ($objCollection as $objAddress) {
            $arrAddr = [
                $objAddress->getStreetLine(1),
                $objAddress->getCity(),
                $objAddress->getPostcode(),
                $objAddress->getRegion(),
                $objAddress->getRegionId(),
            ];
            $arrReturn[$objAddress->getData("entity_id")] = implode(", ", $arrAddr);
        }

        return $arrReturn[$addressKey];
    }

    /**
     * @return array|null
     */
    public function getAllTimeSlot()
    {
        $timeSlot = $this->deliveryDate->getListTimeSlot();
        return $timeSlot;
    }

    /**
     * @return \Riki\SubscriptionCourse\Api\Data\SubscriptionCourseInterface|null
     */
    public function getCurrentSubscriptionCourse()
    {
        try {
            return $this->courseRepository->get($this->getEntity()->getCourseId());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array
     */
    public function getCourseSetting()
    {
        if ($objCourse = $this->getCurrentSubscriptionCourse()) {
            return $objCourse->getSettings();
        }

        return [];
    }

    public function getTermsOfUse() {
        if ($objCourse = $this->getCurrentSubscriptionCourse()) {
            return $objCourse->getTermsOfUse();
        }
        return null;
    }

    public function getTermsOfUseDownloadUrl() {
        $termsOfUse = $this->getTermsOfUse();
        if ($termsOfUse) {
            $mediaDirectory = $this->directoryList->getUrlPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

            return '/' . $mediaDirectory . '/' .
                \Riki\SubscriptionCourse\Controller\Adminhtml\Course\Save::UPLOAD_TARGET . '/' . $termsOfUse;
        }

        return '#';
    }
    /**
     * @param $product
     * @return string
     */
    public function getProductImagesProfile($product)
    {
        $origImageHelper = $this->helperImage->init($product, 'product_listing_thumbnail_preview')
            ->keepFrame(true)->constrainOnly(true)->resize(160, 160);
        return $origImageHelper->getUrl();
    }

    /**
     * @return mixed
     */
    public function getTentativePoint()
    {
        $obj = $this->registry->registry('subscription_profile_obj');

        return $obj->getTentativePointEarned();
    }

    /**
     * @return mixed
     */
    public function getTentativePointMoney()
    {
        $price = number_format($this->getTentativePoint(), 3);

        return $this->formatCurrency($price);
    }

    /**
     * @return mixed
     */
    public function getPointUsedMoney()
    {
        $price = $this->getPointUsed();
        $priceFormat = number_format($price, 3);
        return $this->formatCurrency($priceFormat);
    }

    /**
     * @return int
     */
    public function getPriceProduct()
    {
        $objEntity = $this->getEntity();

        $arrProduct = $objEntity->getData("product_cart");
        $price = 0;
        foreach ($arrProduct as $productCartId => $arrData) {
            $productId = $arrData['product_id'];

            $objSubscriptionHelper = $this->subHelper;
            $objProduct = $objSubscriptionHelper->loadProductWithCache($productId);
            $qty = $arrData->getData("qty");

            $_p = $this->adjustmentCalculator->getAmount(
                    $objProduct->getFinalPrice($qty),
                    $objProduct
                )->getValue() * $qty;

            $price = $price + $_p;
        }

        return $price;
    }

    /**
     * @return float|int
     */
    public function getFinalShippingFee()
    {
        return $this->helperProfile->getShippingFeeView(
            $this->getEntity()->getData('product_cart'),
            $this->getEntity()->getData('store_id')
        );
    }

    /**
     * @return int
     */
    public function getPaymentFee()
    {
        $paymentMethod = $this->getEntity()->getData("payment_method");
        return $this->paymentFeeHelper->getPaymentCharge($paymentMethod);
    }

    /**
     * @param $cid
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer($cid)
    {
        $objCustomer = $this->customerFactory->create();
        $objCustomer->load($cid);

        return $objCustomer;
    }

    /**
     * Get use point amount setting of this customer
     *
     * @return float
     */
    public function getPointUsed()
    {
        $customerId = $this->getEntity()->getData('customer_id');
        $customer = $this->getCustomer($customerId);
        $customerCode = $customer->getData('consumer_db_id');
        $pointSetting = $this->rewardManagement->getRewardUserSetting($customerCode);
        return $pointSetting['use_point_amount'];
    }

    /**
     * @return string
     */
    public function getUrlDeleteProductCart()
    {
        if ($this->getAppState()->getAreaCode() === "frontend") {
            return  $this->getUrl('subscriptions/profile/delete');
        } else {
            return  $this->getUrl('profile/profile/delete');
        }
    }

    /**
     * @return string
     */
    public function getUrlAddProduct()
    {
        if ($this->getAppState()->getAreaCode() === "frontend") {
            return $this->getUrl('subscriptions/profile/add', ['_current'=>true]);
        } else {
            return $this->getUrl('profile/profile/add', ['_current'=>true]);
        }
    }

    /**
     * @return bool
     */
    public function isDisableAll()
    {
        if (isset($this->isDisabledAll) and $this->isDisabledAll !== null) {
            return $this->isDisabledAll;
        }
        /** @var \Riki\Subscription\Model\Profile\Profile $obj */
        $obj = $this->registry->registry('subscription_profile_obj');

        if ($obj->getData('create_order_flag') == 1) {
            $this->isDisabledAll = true;
            return $this->isDisabledAll;
        }
        if ($obj->getData('status') == 2) {
            $this->isDisabledAll = true;
            return $this->isDisabledAll;
        }
        if ($obj->isWaitingToDisengaged()) {
            $this->isDisabledAll = false;
            return $this->isDisabledAll;
        }
        if (!$this->helperProfile->checkLeadTimeIsActiveForProfile($obj->getData('profile_id'))) {
            $this->isDisabledAll = true;
            return $this->isDisabledAll;
        }
        $this->isDisabledAll = $obj->isInStage();
        /** disable button when disable all */
        $this->registry->register("disable_button_stock_point", $this->isDisabledAll);

        if ($this->stockPointIsSelected()) {
            $this->isDisabledAll = true;
            return $this->isDisabledAll;
        }
        return $this->isDisabledAll;
    }

    /**
     * @return \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    public function getHelperCalculateDateTime()
    {
        return $this->calculateDeliveryDateHelper;
    }

    /**
     * @return bool
     */
    public function checkBtnUpdateAllChangePressed()
    {
        $cacheWrapper = $this->getEntity();
        if ($cacheWrapper && $cacheWrapper->getData(Constant::CACHE_BTN_UPDATE_PRESSED) == '1') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function checkBtnCreateOrderPressed()
    {
        $cacheWrapper = $this->getEntity();
        if ($cacheWrapper && $cacheWrapper->getData(Constant::CACHE_BTN_CREATE_ORDER_PRESSED) == '1') {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getArrDeliveryType()
    {
        $deliHelper = $this->deliveryHelper;
        return $deliHelper->getArrDeliveryType();
    }

    /**
     * @param $customerId
     * @return array|null
     */
    public function getRewardUserRedeem($customerId)
    {
        $customer = $this->getCustomer($customerId);
        $customerCode = $customer->getData('consumer_db_id');
        if ($customerCode) {
            $customerSetting = $this->rewardManagement->getRewardUserSetting($customerCode);
            return $customerSetting;
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getUrlGenerateOrder()
    {
        return $this->getUrl('profile/order/create');
    }

    /**
     * @return bool
     */
    public function isSubscriptionHanpukai()
    {
        if ($course = $this->getCurrentSubscriptionCourse()) {
            return $course->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI;
        }

        return false;
    }

    /**
     * GetUnitDisplay
     *
     * @param $product
     * @return array
     */
    public function getUnitDisplay($product)
    {
        if ('bundle' == $product->getTypeId()) {
            return [];
        }

        if ($product->getCaseDisplay() == 1) {
            return ['ea' => __('EA')];
        } elseif ($product->getCaseDisplay() == 2) {
            return ['cs' => __('CS')];
        } elseif ($product->getCaseDisplay() == 3) {
            $profileId = $this->getEntity()->getData("profile_id");
            $objSubscriptionHelper = $this->helperProfile;
            $objUnitCase = $objSubscriptionHelper->getUnitCaseProductCartProfile($profileId, $product->getId());

            $unitCase = 'EA';
            if (isset($objUnitCase[0]['unit_case'])) {
                $unitCase = $objUnitCase[0]['unit_case'];
            }

            if ($unitCase == 'EA') {
                return ['ea' => __('EA')];
            } elseif ($unitCase == 'CS') {
                return ['cs' => __('CS')];
            } else {
                return ['ea' => __('EA'),'cs' => __('CS')];
            }
        } else {
            return ['ea' => __('EA')];
        }
    }

    /**
     * GetUnitQty
     *
     * @param $product
     * @return int
     */
    public function getUnitQty($product)
    {
        if ($product->getUnitQty()) {
            return $product->getUnitQty();
        } else {
            return 1;
        }
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductById($id)
    {
        return $this->productRepository->getById($id);
    }

    /**
     * @param $attributeString
     * @return mixed
     */
    public function getAttributeArray($attributeString)
    {
        if (!\Zend_Validate::is($attributeString, 'NotEmpty')) {
            return [];
        }

        $arrayAttr = explode(',', $attributeString);

        $searchCriteria = $this->searchCriteria->addFilter('wrapping_id', $arrayAttr, 'in')->create();

        $wrappingData = $this->wrappingRepository->getList($searchCriteria);

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

        return $returnedArray;
    }

    /**
     * @param $attributeString
     * @return string
     */
    public function getAttributeName($attributeString)
    {
        $nameAtt = '';

        $wrappingData = $this->getWrappingDataById($attributeString);

        if ($wrappingData) {
            $nameAtt = $wrappingData->getData('gift_name');
        }

        return $nameAtt;
    }

    /**
     * @return null|string
     */
    public function getGiftConfig()
    {
        $giftWrappingHelper = $this->giftWrappingData;
        return $giftWrappingHelper->isGiftWrappingAvailableForItems($this->_storeManager->getStore()->getId());
    }

    /**
     * @return mixed
     */
    public function getMediaBaseUrl()
    {
        $currentStore = $this->_storeManager->getStore();
        return $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }
    /**
     * @param $messageId
     * @return $this
     */
    public function getMessage($messageId)
    {
        $giftMessage = $this->messageFactory->create();
        return $giftMessage->load($messageId);
    }

    public function getMessageConfig()
    {
        return $this->scopeConfig->getValue(
            \Magento\GiftMessage\Helper\Message::XPATH_CONFIG_GIFT_MESSAGE_ALLOW_ITEMS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId()
        );
    }
    /**
     * @param $wrappingFee
     * @return mixed
     */
    public function calTax($wrappingFee)
    {
        $wrappingTax = $this->giftWrappingData->getWrappingTaxClass($this->_storeManager->getStore());
        $wrappingRate = $this->taxCalculation->getCalculatedRate($wrappingTax);
        if ($wrappingFee > 0) {
            $taxRate = $wrappingRate/100;
            $wrappingFee = $wrappingFee + ($taxRate*$wrappingFee);
        }
        return (int)$wrappingFee ;
    }

    /**
     * @param $profileId
     * @return bool|object
     */
    public function getSimulatorOrderOfProfile($profileId)
    {
        $cacheProfile = $this->getEntity();
        if ($cacheProfile and isset($cacheProfile)) {
            try {
                $simulatorOrder = $this->helperSimulator->createSimulatorOrderHasData(
                    $cacheProfile,
                    null,
                    true
                );
                if ($simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {
                    $this->checkSimulatorOrderData($simulatorOrder->getData(), $simulatorOrder->getAllVisibleItems());
                    /** update data for profile cache simulate  */
                    $this->profileIndexerHelper->updateDataProfileCache($profileId, $simulatorOrder);
                    return $simulatorOrder;
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }

        return false;
    }

    /**
     * Get profile statuses
     *
     * @return array
     */
    public function getStatuses()
    {
        return [1=> 'Active', 0=> 'Disengaged'];
    }

    /**
     * Get stock status
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return string
     */
    public function getStockStatus($product)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        if ($product->getTypeId() != ProductType::TYPE_BUNDLE) {
            $stock = $this->stockData->getStockStatusByEnv(
                $product,
                \Riki\ProductStockStatus\Helper\StockData::ENV_BO
            );

            $storeId = $this->getEntity()->getData("store_id");

            if ($stock == __('Out of stock')
                && $this->backOrderHelper->isConfigBackOrder($product->getId(), $storeId)
            ) {
                if ($product->getIsSalable()) {
                    $stock = __('In stock');
                }
            }
        } else {
            if ($product->getIsSalable() == true) {
                $stock = __('In stock');
            } else {
                $stock = __('Out of stock');
            }
        }
        return $stock;
    }

    /**
     * Get billing address customer
     *
     * @return \Magento\Customer\Model\Address|bool
     */
    public function getBillingAddressCustomer()
    {
        $profile = $this->getEntity();
        $productCats = $profile->getData('product_cart');
        $productData = reset($productCats);
        if ($productData) {
            $addressId = $productData->getData(Profile::BILLING_ADDRESS_ID);
            if ($addressId) {
                try {
                    $address = $this->addressRegistry->retrieve($addressId);

                    if ($address->getId()) {
                        return $address;
                    }
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
        } else {
            $productCartModel = $this->productCartFactory->create()->getCollection();
            $productCartModel->addFieldToFilter('profile_id', $profile->getProfileId());
            foreach ($productCartModel->getItems() as $productCart) {
                $addressId = $productCart->getData(Profile::BILLING_ADDRESS_ID);
                if ($addressId) {
                    try {
                        $address = $this->addressRegistry->retrieve($addressId);

                        if ($address->getId()) {
                            return $address;
                        }
                    } catch (\Exception $e) {
                        $this->_logger->critical($e);
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get billing address text
     *
     * @param \Magento\Customer\Model\Address $address
     *
     * @return string
     */
    public function getBillingAddressText($address)
    {
        $customer = $address->getCustomer();
        $data = [
            $customer->getEmail(),
            $address->getPostcode(),
            $address->getRegion(),
            $address->getCity(),
            $address->getStreetFull(),
            $address->getData('apartment'),
            $address->getTelephone()
        ];
        return implode('<br>', $data);
    }

    /**
     * @return bool
     */
    public function getRolesOfAdminUser()
    {
        if ($this->_authorization->isAllowed('Riki_Subscription::change_earn_point')) {
            return true;
        }
        return false;
    }

    /**
     * get frequency option
     *
     * @return array
     */
    public function getFrequencyOptions()
    {
        $options = $this->courseHelper->getFrequenciesByCourse(
            $this->getEntity()->getData("course_id")
        );

        $options[0] = __('Unspecified');

        return $options;
    }

    /**
     * @return mixed
     */
    public function getProfileId()
    {
        return $this->getRequest()->getParam('id');
    }
    /**
     * @return string
     */
    public function getSubscriptionConfig()
    {
        $nestedArray = [];
        /** @var \Riki\Subscription\Model\Data\ApiProfile $profileModelData */
        $profileModelData = $this->registry->registry('subscription_profile_data');
        $profileId = $this->getProfileId();

        $frequencyOptions = $this->getFrequencyOptions();

        /**
         * flag to check current frequency is exist in course or not
         *     do not need to validate for stock point
         */
        $frequencyExistInCourse = true;

        /**
         * flag to check and show frequency box,
         *  do not need to show frequency box if profile is stock point
         */
        $canShowFrequencyBox = $this->canShowFrequencyBox();

        /** data StockPoint */
        if ($this->getStockPointPostData()) {
            $profileCurrentFrequency = $this->frequencyHelper->formatFrequency(
                $this->getStockPointPostData()['frequency_interval'],
                $this->getStockPointPostData()['frequency_unit']
            );

            $frequencySelected = (int) $this->subHelper->getFrequencyIdByUnitAndInterval(
                $this->getStockPointPostData()['frequency_unit'],
                $this->getStockPointPostData()['frequency_interval']
            );
        } else {
            $profileCurrentFrequency = $this->frequencyHelper->formatFrequency(
                $this->getEntity()->getData('frequency_interval'),
                $this->getEntity()->getData('frequency_unit')
            );
            $frequencySelected = (int) $this->subHelper->getFrequencyIdByUnitAndInterval(
                $this->getEntity()->getData('frequency_unit'),
                $this->getEntity()->getData('frequency_interval')
            );
        }

        if ($canShowFrequencyBox) {
            if ($frequencySelected == 0 || !isset($frequencyOptions[$frequencySelected])) {
                $frequencyExistInCourse = false;
            }
        }

        /* Get point balance*/
        $customerId = $profileModelData->getCustomerId();
        $customer= $this->mageCustomerRepository->getById($customerId);
        $customerCode = $customer->getCustomAttribute('consumer_db_id');
        $totalPoint = 0;

        if ($customerCode && !empty($customerCode->getValue())) {
            $totalPoint = $this->rewardManagement->getPointBalance($customerCode->getValue());
        }

        if (\Zend_Validate::is($profileModelData->getProfileId(), 'NotEmpty')) {
            $nestedArray = $this->extensibleDataObjectConverter->toNestedArray(
                $profileModelData,
                [],
                \Riki\Subscription\Api\Data\ApiProfileInterface::class
            );

            $nestedArray['course_code'] = $this->courseHelper->getCourseCodeByCourseId(
                $profileModelData->getCourseId()
            );

            /*profile current frequency*/
            $nestedArray['current_frequency'] = $profileCurrentFrequency;

            $nestedArray["new_paygent"] = $this->getEntity()->getData('new_paygent');
            $nestedArray["paygent_save_prederred"] = $this->getEntity()->getData('paygent_save_prederred');
            $nestedArray["selected_frequency"] =  $frequencySelected;
            $nestedArray["earn_point_on_order"] =  $this->getEntity()->getData('earn_point_on_order');
            $nestedArray["coupon_code"] =  $this->getEntity()->getData('coupon_code');
            $nestedArray["specified_warehouse_id"] =  $this->getEntity()->getData('specified_warehouse_id');
            $nestedArray["payment_method"] =  $this->getEntity()->getData('payment_method');

            $obj = $this->registry->registry('subscription_profile_obj');

            $nestedArray["disengagement_date"] =  $this->getDateFormat($obj->getData('disengagement_date'));
            $nestedArray["disengagement_reason"] =  $obj->getData('disengagement_reason');
            $nestedArray["disengagement_user"] =  $obj->getData('disengagement_user');
            $nestedArray["status"] =  $obj->getData('status');

            $sessionProfile = $this->getEntity();
            $nestedArray["frequency_unit"] =  $sessionProfile->getData('frequency_unit');
            $nestedArray["frequency_interval"] =  $sessionProfile->getData('frequency_interval');
        }

        /** @var  \Magento\Customer\Model\Address $billingAddress */
        $billingAddress = $this->getBillingAddressCustomer();
        $hasBillingAddress =  !is_bool($billingAddress);
        $flatBillingAddress = [];

        if ($billingAddress && $billingAddress->getId()) {
            $flatBillingAddress = $this->extensibleDataObjectConverter->toNestedArray(
                $billingAddress->getDataModel(),
                [],
                \Magento\Customer\Api\Data\AddressInterface::class
            );

            $apartment = '';

            if (\Zend_Validate::is($billingAddress->getCustomAttribute('apartment'), 'NotEmpty')) {
                $apartment = $billingAddress->getCustomAttribute('apartment')->getValue();
            }

            $flatBillingAddress["email"] = $billingAddress->getCustomer()->getEmail();
            $flatBillingAddress["inline_address"] = $this->getBillingAddressText($billingAddress);
            $flatBillingAddress["street_full"] = $billingAddress->getStreetFull();
            $flatBillingAddress["apartment"] = $apartment;

            //load customer

            $ambComDivisionName = $customer->getCustomAttribute('amb_com_division_name');

            if ($ambComDivisionName) {
                $flatBillingAddress["amb_com_division_name"]   = $ambComDivisionName->getValue();
            }

            $flatBillingAddress["companyDepartmentName"]   = null;

            $flatBillingAddress["personInCharge"]          = null;

            $customerDetail = $this->getCustomerByConsumerDbId($billingAddress);
            if ($customerDetail !=null) {
                $flatBillingAddress["companyDepartmentName"]   = $customerDetail['companyDepartmentName'];
                $flatBillingAddress["personInCharge"]          = $customerDetail['personInCharge'];
            }
        }
        $profileHaveTmp = 0; // 0 is not tmp
        if ($this->helperProfile->getTmpProfile($profileId) !== false) {
            $profileHaveTmp = 1; // 1 is have tmp
            $nestedArray['main_profile_id'] = $profileId;
        }
        /* call simulator object */
        /** @var \Riki\Subscription\Model\Emulator\Order $simulatorOrderObject */
        $simulatorOrderObject = $this->getSimulatorOrderOfProfile($profileModelData->getProfileId());

        /*set profile session again */
        $this->profileSessionHelper->generateProfileDataBySimulateData(
            $profileModelData->getProfileId(),
            $simulatorOrderObject
        );

        $hasSimulateOrder = !is_bool($simulatorOrderObject);
        $simulateOrderFlatData = [];

        if ($hasSimulateOrder) {
            $simulateOrderFlatData = $this->extensibleDataObjectConverter->toNestedArray(
                $simulatorOrderObject,
                [],
                \Magento\Sales\Api\Data\OrderInterface::class
            );
            $simulateOrderFlatData["gw_amount"] = $simulatorOrderObject->getGwItemsPriceInclTax();
            $simulateOrderFlatData["fee"] = floatval($simulatorOrderObject->getFee());
            $simulateOrderFlatData["base_fee"] = $simulatorOrderObject->getBaseFee();
            $simulateOrderFlatData["used_point_amount"] = $simulatorOrderObject->getData('used_point_amount');
            $simulateOrderFlatData["bonus_point_amount"] = $simulatorOrderObject->getBonusPointAmount();
        }

        /* get dl information */
        $deliveryInformation = $this->getListProductByAddressAndByDeliveryType($simulatorOrderObject);
        $deliveryInformationGroupData = $this->groupDataAndAppendCalendarSetting($deliveryInformation);

        /*Get order status of each item in profile*/
        $productOutOfStock = $this->subHelperData->getAllProductOutOfStockInProfile(
            $profileModelData->getProfileId()
        );

        /*Get stock level of each item in profile*/
        $productStockLevel = $simulatorOrderObject? $this->getProductsStockQty($simulatorOrderObject) : [];

        /*Reward user setting*/
        $rewardUser = $this->getRewardUserRedeem($this->getEntity()->getCustomerId());
        $rewardUserSetting = isset($rewardUser['use_point_type'])?$rewardUser['use_point_type']:0;
        $rewardUserRedeem = isset($rewardUser['use_point_amount'])?$rewardUser['use_point_amount']:0;
        if ($this->getEntity()->getData(Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED)) {
            $profileHasChanged = $this->getEntity()->getData(Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED);
        } else {
            $profileHasChanged = false;
        }

        $appliedCoupon = $this->getCurrentAppliedCoupon($profileModelData->getProfileId());

        $isUsedPaygent = (int)$this->getCcLastUsedDate(
            $this->getEntity()->getCustomerId(),
            $profileModelData->getProfileId()
        );
        /** check show button stock point */
        $profileData = $this->registry->registry('subscription_profile');
        $this->checkShowStockPoint($simulatorOrderObject, $profileData, $deliveryInformationGroupData);

        $isInvalidLeadTimenOBothWh = !$this->helperProfile->checkLeadTimeIsActiveForProfile(
            $profileModelData->getProfileId()
        );
        $invalidLeadTimeMessage = $simulatorOrderObject? $simulatorOrderObject->getShippingAddress()->getRegion() : '';

        $disableButtonSP = $this->validateStockPointProduct->disableButtonSP($this->getEntity());
        $questionnaireData = $this->getQuestionnaireAnswers(
            $profileId,
            \Riki\Questionnaire\Model\Answers::QUESTIONNAIRE_ANSWER_TYPE_PROFILE
        );
        $reasonItems = $this->reasonModel->getDisengagementReasons(
            explode(',', $profileModelData->getDisengagementReason())
        );
        $reasons = [];
        if ($reasonItems) {
            foreach ($reasonItems as $reason) {
                $reasons[] = $reason->getTitle();
            }
        }
        $deliveryTime = $this->getDeliveryTimes($profileId);
        $waitOOSOrders = $this->getWaitingOOSOrders($profileId);
        $cancelOrder = $this->getTimesOfCancelOrder($profileId);

        $nestedArray['total_max_amount_threshold'] = $this->getTotalThresholdRestriction('amount','maximum');
        $nestedArray['total_min_amount_threshold'] = $this->getTotalThresholdRestriction('amount','minimum');
        $nestedArray['maximum_qty_restriction'] = $this->getTotalThresholdRestriction('qty');
        $nestedArray['terms_of_use'] = $this->getTermsOfUse();
        $nestedArray['terms_of_use_download_url'] = $this->getTermsOfUseDownloadUrl();

        $returnedData = [
            'profileData' =>  $nestedArray,
            'frequency_options' => $frequencyOptions,
            'frequencyExistInCourse' => $frequencyExistInCourse,
            'canShowFrequencyBox' => $canShowFrequencyBox,
            'course_setting' => $this->getCourseSetting(),
            'is_hanpukai' => $this->isSubscriptionHanpukai(),
            'course_data' => $this->getCurrentCourse(),
            'payment_method' => $this->getListPaymentMethod(),
            'is_used_paygent' => $isUsedPaygent,
            'billing_information' => $flatBillingAddress,
            'has_billing_address' => $hasBillingAddress,
            'allow_change_earn_point' => (bool) $this->getRolesOfAdminUser(),
            'price_format' =>  $this->localeFormat->getPriceFormat(null, 'JPY'),
            'order_data' => $simulateOrderFlatData,
            'has_order_data' => $hasSimulateOrder,
            'delivery_info' => $deliveryInformationGroupData,
            'product_out_off_stock' => $productOutOfStock,
            'product_stock_level' => $productStockLevel,
            'timeslot_data' => $this->getAllTimeSlot(),
            'addresses_data' => $this->getAllAddress(),
            'paygent_img' => $this->getViewFileUrl('images/credit_card_method.png'),
            'delete_product_cart_url' => $this->getUrlDeleteProductCart(),
            'simulate_profile_data_url' => $this->getSimulateProfileUrl(),
            'reward_user_redeem' => $rewardUserRedeem,
            'reward_user_setting' => $rewardUserSetting,
            'save_url' => $this->getUrl('*/*/save'),
            'generate_order_url' => $this->getUrlGenerateOrder(),
            'is_disabled_all' => $this->isDisableAll(),
            'free_gifts' => $this->subHelperData->getFreeGifts($simulatorOrderObject, true),
            'have_tmp' => $profileHaveTmp,
            'gift_available' => $this->getGiftConfig(),
            'message_available' => $this->getMessageConfig(),
            'balance' => $totalPoint,
            'subscription_profile_has_changed' => $profileHasChanged,
            'is_invalid_lead_time_on_both_wh' => $isInvalidLeadTimenOBothWh,
            'invalid_lead_time_message' => __('The product not available to ship to %1', $invalidLeadTimeMessage),
            'url_change_address_delivery' => $this->getUrl('profile/profile/changeShippingAddress'),
            'appliedCoupon' => $appliedCoupon,
            'coupon_code' => implode(',', $appliedCoupon),
            'addCouponUrl' => $this->getAddCouponUrl(),
            'changeWarehouseUrl' => $this->getChangeWarehouseUrl(),
            'deleteCouponUrl' => $this->getDeleteCouponUrl(),
            'warehouseOptions' => $this->getWarehouseOptions(),
            'allow_stock_point' => $this->allowShowButtonStockPoint(),
            'stock_point_public_key' => $this->helperStockPoint->getPublicKey(),
            'stock_point_url_post' =>  $this->helperStockPoint->getUrlPost(),
            'stock_point_is_selected' => $this->stockPointIsSelected(),
            'stock_point_data_post' => $this->getStockPointPostData() ?
                json_encode($this->getStockPointPostData()) : false,
            'is_stock_point_profile' => $this->isStockPointProfile(),
            'is_stock_point_profile_model' => $this->isStockPointProfileFromModel(),
            'disable_two_button_stock_point' => $disableButtonSP,
            'remove_stock_point_url' =>$this->getRemoveStockPointUrl(),
            'disable_button_stock_point' => $this->registry->registry("disable_button_stock_point"),
            'editUrl' => $this->getEditUrl(),
            'stock_point_address_data' => $this->getStockPointAddressData(),
            'return_url' => $this->getReturnUrl(),
            'disengagement_reasons' => $reasons,
            'questionnaire_data' => $questionnaireData,
            'delivery_times_of_subscription' => $deliveryTime,
            'waiting_oos_delivery' => $waitOOSOrders,
            'times_of_cancel_order' => $cancelOrder
        ];
        return json_encode($returnedData);
    }
    /**
     * @param $profileId
     * @return int
     */
    public function getTimesOfCancelOrder($profileId)
    {
        $timeOfOrder = $this->profileModel->getAdditionalInfoOfSubscription($profileId);
        if (isset($timeOfOrder['time_of_canceled']))
        {
            return (int)$timeOfOrder['time_of_canceled'];
        }
        return 0;
    }

    /**
     * @param int $profileId
     * @return int
     */
    public function getDeliveryTimes($profileId)
    {
        $timeOfOrder = $this->profileModel->getAdditionalInfoOfSubscription($profileId);
        if (isset($timeOfOrder['delivery_time']))
        {
            return (int)$timeOfOrder['delivery_time'];
        }
        return 0;
    }

    /**
     * @param int $profileId
     * @return array
     */
    public function getWaitingOOSOrders($profileId)
    {
        $timeOfOrder = $this->profileModel->getAdditionalInfoOfSubscription($profileId);
        $result = [];
        if (isset($timeOfOrder['waiting_for_oos']) && !empty($timeOfOrder['waiting_for_oos']))
        {
            foreach ($timeOfOrder['waiting_for_oos'] as $key => $order) {
                $result []=
                    [
                        'id' => $order,
                        'url' => $this->getUrl('sales/order/view', ['order_id' => $key])
                    ];
            }
        }
        return $result;
    }

    public function getTotalThresholdRestriction($restrictionType, $amountType = null) {
        $currentCourse = $this->getCurrentCourse();
        if ($currentCourse['course_id']) {
            switch ($restrictionType) {
                case 'amount':
                    $condition = $currentCourse['oar_condition_serialized'];
                    $serializeData = json_decode($condition, true);
                    switch ($amountType) {
                        case 'minimum':
                            $result['option'] = $serializeData['minimum']['option'];
                            if ($result['option'] == \Riki\Subscription\Helper\Order::EACH_ORDER) {
                                $result['amount'] = isset($serializeData[$amountType]['amounts']) ? $serializeData[$amountType]['amounts'] : [];

                                return $result;
                            }
                            $result['amount'] = isset($serializeData[$amountType]['amount']) ? $serializeData[$amountType]['amount'] : [];

                            return $result;
                            break;
                        case 'maximum';
                            return isset($serializeData[$amountType]['amount']) ? $serializeData[$amountType]['amount'] : [];
                            break;
                    }
                    break;
                case 'qty':
                    $condition = $currentCourse['maximum_qty_restriction'];
                    $serializeData = json_decode($condition, true);
                    $result['option'] = $serializeData['maximum']['option'];
                    if ($result['option'] == QtyRestrictionOptions::OPTION_VALUE_CUSTOM_ORDER) {
                        $result['qty'] = isset($serializeData['maximum']['qtys']) ? $serializeData['maximum']['qtys'] : [];

                        return $result;
                    }
                    $result['qty'] = isset($serializeData['maximum']['qty']) ? $serializeData['maximum']['qty'] : [];

                    return $result;
                    break;
            }
        }

        return [];
    }

    /**
     * @param \Riki\Subscription\Model\Emulator\Order $order
     * @return array
     */
    protected function getProductsStockQty(\Riki\Subscription\Model\Emulator\Order $order)
    {
        $result = [];

        /** @var \Riki\Subscription\Model\Emulator\Cart\Item $orderItem */
        foreach ($order->getAllVisibleItems() as $orderItem) {
            if ($orderItem->getHasChildren() || $orderItem->getParentItemId()) {
                continue;
            }

            $stockSetting = $this->stockData->getProductStockSettingsByStoreId(
                $orderItem->getProductId(),
                $order->getStoreId()
            );

            $result[$orderItem->getProductId()] = intval($stockSetting->getData('available_quantity'));
        }

        return $result;
    }

    /**
     * check show button stock point
     * @param $simulatorOrderObject
     * @param $profileModelData
     * @param $dlInformation
     * @return bool|null
     */
    public function checkShowStockPoint($simulatorOrderObject, $profileModelData, $dlInformation)
    {
        if ($this->isShowStockPoint != null) {
            return $this->isShowStockPoint;
        }
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
                        $this->isShowStockPoint = false;
                        return $this->isShowStockPoint;
                    }
                }
                $listDelivery[$deliveryType] = $listCartData;
            }
        }

        if (count($listDelivery) == 1) {
            foreach ($listDelivery as $deliveryType => $listCartData) {
                if (!empty($listCartData)) {
                    /** check 4 conditions to show button stock point */
                    $this->isShowStockPoint = $this->validateStockPointProduct->isShowStockPoint(
                        $simulatorOrderObject,
                        $deliveryType,
                        $listCartData,
                        $profileModelData
                    );
                    if (!$this->isShowStockPoint) {
                        return false;
                    }
                }
            }

            return $this->isShowStockPoint;
        }
        $this->isShowStockPoint = false;
        return $this->isShowStockPoint;
    }

    /**
     * get stock point data
     *
     * @return bool
     */
    public function getStockPointPostData()
    {
        if ($this->getEntity()->getData("stock_point_data")) {
            return $this->getEntity()->getData("stock_point_data");
        }
        return false;
    }

    /**
     * get url process stock point data
     *
     * @return string
     */
    public function getStockPointAddressData()
    {
        return $this->getUrl('profile/profile/stockPointAddressData');
    }

    /**
     * get return url
     * @return string
     */
    public function getReturnUrl()
    {
        /** get profile id of main */
        $profileId = $this->registry->registry('subscription_profile_data')->getProfileId();
        /* If profileId is tmp, will return main profileId else return itself*/
        $returnProfileId = $this->helperProfile->getMainFromTmpProfile($profileId);

        return $this->getUrl(
            'profile/profile/edit',
            [
                'id' =>  $returnProfileId,
                'form_key' => $this->formKey->getFormKey()
            ]
        );
    }
    public function getStockPointUrlPost()
    {
        return $this->helperStockPoint->getUrlPost();
    }
    /** link remove stock point */
    public function getRemoveStockPointUrl()
    {
        return $this->getUrl('profile/profile/removestockpoint', ['id'   =>  $this->getEntity()->getProfileId()]);
    }
    /**
     * check subscription profile is stock point
     *
     */
    public function stockPointIsSelected()
    {
        /** ex: false */
        if (!empty($this->getStockPointPostData())) {
            return true;
        }
        return false;
    }

    public function allowShowButtonStockPoint()
    {
        return $this->isShowStockPoint;
    }
    /**
     * @param $simulatorOrder
     * @param $storeId
     * @return mixed
     */
    public function getShippingFeeAfterSimulator($simulatorOrder, $storeId)
    {
        $taxDisplay = $this->scopeConfig->getValue(
            self::XML_PATH_TAX_ORDER_DISPLAY_CONFIG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($taxDisplay == 1) {//exclude tax
            return $simulatorOrder->getShippingAmount();
        } else {
            return $simulatorOrder->getShippingInclTax();
        }
    }

    /**
     * @return array
     */
    public function getDisengageReasons()
    {
        return $this->disengageReason->toArray();
    }

    /**
     * @return float|int
     */
    public function getSalesValueCount()
    {
        if ($course = $this->getCurrentSubscriptionCourse()) {
            return (float)$course->getSalesValueCount() - (float)$this->getEntity()->getSalesValueCount();
        }

        return 0;
    }

    /**
     * Get sales count value message when disengage profile
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSalesValueCountMessage()
    {
        $penaltyFee = $this->formatCurrency($this->getPenaltyFee());
        $salesValueCount = $this->getSalesValueCount();
        if ($salesValueCount > 0) {
            return __(
                'The customer did not reach the Sales value count (must purchase %1 more amount), please apply the penalty fee %2',
                $this->formatCurrency($salesValueCount),
                $penaltyFee
            );
        }
    }

    /**
     * Get sales qty count message when disengage profile
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSalesQtyCountMessage()
    {
        $penaltyFee = $this->formatCurrency($this->getPenaltyFee());
        $salesQtyCount = $this->getSalesQtyCount();
        if ($salesQtyCount > 0) {
            return __('The customer did not reach the Sales qty count (must purchase %1 more qty), please apply the penalty fee %2', $salesQtyCount, $penaltyFee);
        }
    }
    /**
     * @return float|int
     */
    public function getSalesQtyCount()
    {
        if ($course = $this->getCurrentSubscriptionCourse()) {
            return (float)$course->getSalesCount() - (float)$this->getEntity()->getSalesCount();
        }

        return 0;
    }

    /**
     * @return int
     */
    public function getPenaltyFee()
    {
        $courseData = $this->getEntity()->getCourseData();

        return isset($courseData['penalty_fee'])? $courseData['penalty_fee'] : 0;
    }

    /**
     * check profile has disengaged?
     *
     * @return bool
     */
    public function isDisengaged()
    {
        $entity = $this->getEntity();
        return $entity->getDisengagementDate()
            && $entity->getDisengagementReason()
            && $entity->getDisengagementUser()
            && $entity->getStatus();
    }

    /**
     * get Date after format
     *
     * @param $date
     * @return string
     */
    public function getDateFormat($date)
    {
        return $this->timezoneHelper->date($date)->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
    }

    /**
     * Get customer by consumer db id
     *
     * @param $billingAddress
     *
     * @return mixed
     */
    public function getCustomerByConsumerDbId($billingAddress)
    {
        $result["companyDepartmentName"]   = null;
        $result["personInCharge"]          = null;
        $consumerDbId = $billingAddress->getCustomer()->getConsumerDbId();
        if ($consumerDbId !=null) {
            $data = $this->customerRepository ->prepareAllInfoCustomer($consumerDbId);

            //Company department name
            if (isset($data['customer_api']) && isset($data['customer_api']['KEY_POST_NAME'])) {
                $result["companyDepartmentName"]   = $data['customer_api']['KEY_POST_NAME'];
            }

            //person in charge
            if (isset($data['amb_api']) && isset($data['amb_api']['CHARGE_PERSON'])) {
                $result["personInCharge"]   = $data['amb_api']['CHARGE_PERSON'];
            }
        }
        return $result;
    }

    /**
     * Get list additional product of course
     *
     * @return $this|bool|null
     */
    public function getAdditionalProductByCourse()
    {
        $subscriptionCourseResourceModel = $this->helperProfile->getSubscriptionCourseResourceModel();
        $courseId = $this->getEntity()->getData("course_id");
        $additionalCategories = $subscriptionCourseResourceModel->getAdditionalCategoryIds($courseId);
        if (!empty($additionalCategories)) {
            return true;
        }
    }

    /**
     * GetCcLastUsedDate
     *
     * @param $customerId
     * @param $profileId
     * @return bool
     */
    public function getCcLastUsedDate($customerId, $profileId)
    {
        $profileModelData = $this->registry->registry('subscription_profile_data');

        if (!$profileModelData) {
            return false;
        }
        $collection = $this->paygentHistory->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('profile_id', $profileId)
            ->addFieldToFilter('type', ['in' => ['profile_update', 'authorize']])
            ->setOrder('id', 'desc')
            ->setPageSize(1);
        if (!$collection->getSize() && !$profileModelData->getTradingId()) {
            return false;
        }
        return true;
    }
    public function getSimulateProfileUrl()
    {
        return  $this->getUrl('profile/profile/simulate');
    }

    /**
     * Add extra button
     *
     * @return $this
     */
    public function _prepareLayout()
    {
        $courseSettings = $this->getCourseSetting();

        $isAllowChangeProduct = false;

        if ((isset($courseSettings['is_allow_change_product']))) {
            $isAllowChangeProduct = $courseSettings['is_allow_change_product'];
        }

        $isDisabledAll = $this->isDisableAll();
        $isSubscriptionHanpukai = $this->isSubscriptionHanpukai();
        $isBtnUpdateAllChangesPressed = $this->checkBtnUpdateAllChangePressed();

        /*check subscription course of profile have addition categories*/
        $hasAdditionalProduct = $this->getAdditionalProductByCourse();

        $profileModelData = $this->registry->registry('subscription_profile_obj');
        $wasDisengaged    = false;
        if ($profileModelData &&
            $profileModelData->getData('disengagement_date') &&
            $profileModelData->getData('disengagement_reason') &&
            $profileModelData->getData('disengagement_user')
        ) {
            $wasDisengaged = true;
        }

        if ($isAllowChangeProduct && !$isDisabledAll && !$wasDisengaged
            && !$isBtnUpdateAllChangesPressed && !$isSubscriptionHanpukai
        ) {
            $this->getToolbar()->addChild(
                'saveButton',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Add Product To Subscription'),
                    'class' => 'action primary save button-add-subscription',
                    'style'  =>"font-size:1.4rem;font-weight:bold;"
                ]
            );
        }

        //Add Additional Product
        if ($isAllowChangeProduct && !$isDisabledAll && !$wasDisengaged
            && !$isBtnUpdateAllChangesPressed && !$isSubscriptionHanpukai && $hasAdditionalProduct
        ) {
            $this->getToolbar()->addChild(
                'add-dditionnal-product',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Add Additional Product'),
                    'class' => 'action primary save button-add-additional-product',
                    'style'  =>"font-size:1.4rem;font-weight:bold;"
                ]
            );
        }

        return parent::_prepareLayout();
    }

    /**
     * Add custom data
     * @return string
     */
    public function _toHtml()
    {
        $script = "<script type='text/javascript'>
            require([
                'jquery',
                'Magento_Ui/js/modal/modal',
                'mage/translate'
            ], function ($,modal,translate) {
                'use strict';
                
                $('.button-add-subscription').click(function(){
                    var hiddenElement = $('#add-products');
                    var options = {
                        type: 'popup',
                        responsive: true,
                        innerScroll: true,
                        modalClass: 'add-product-popup',
                        title: translate('Add product'),
                        buttons: [{
                        text: translate('Add'),
                        click: function() {                            
                            profileProductAdd.productGridAddSelected();
                            this.closeModal();
                        }
                    }]
                    };
                    var popup = modal(options,hiddenElement );
                    hiddenElement.modal('openModal');
                })
                                
                $('.button-add-additional-product').click(function(){
                    var hiddenElement = $('#add-additional-products');
                    var options = {
                        type: 'popup',
                        responsive: true,
                        innerScroll: true,
                        modalClass: 'add-additional-product-popup',
                        title: translate('Add additional product'),
                        buttons: [{
                        text: translate('Add'),
                        click: function() {                            
                            profileAdditionalProductAdd.productGridAddSelected();
                            this.closeModal();
                        }
                    }]
                    };
                    var popup = modal(options,hiddenElement );
                    hiddenElement.modal('openModal');
                })                
            });
            </script>";

        return parent::_toHtml() . $script;
    }

    /**
     * get warehouse option
     *
     * @return array
     */
    public function getWarehouseOptions()
    {
        $warehouseOptions = $this->pointOfSale->toArray();
        array_unshift($warehouseOptions, __('Unspecified'));
        return $warehouseOptions;
    }

    /**
     * @param $id
     * @return bool|\Magento\GiftWrapping\Api\Data\WrappingInterface
     */
    public function getWrappingDataById($id)
    {
        $searchCriteria = $this->searchCriteria->addFilter('wrapping_id', $id)->create();

        $wrappingData = $this->wrappingRepository->getList($searchCriteria);

        if ($wrappingData->getTotalCount()) {
            foreach ($wrappingData->getItems() as $item) {
                return $item;
            }
        }

        return false;
    }

    /**
     * Get current applied coupon of profile
     *
     * @param $profileId
     * @return array
     */
    private function getCurrentAppliedCoupon($profileId)
    {
        $rs = [];

        $profileData = $this->profileSessionHelper->getProfileDataById($profileId);

        if (!empty($profileData) && !empty($profileData['appliedCoupon'])) {
            $rs = $profileData['appliedCoupon'];
        }

        return $rs;
    }

    /**
     * @param $objSessionProfile
     * @param $simulatorOrder
     * @return array
     */
    private function getProductCartData($objSessionProfile, $simulatorOrder)
    {
        $rs=  $objSessionProfile->getData("product_cart");
        $freeGifts = $this->subHelperData->getFreeGifts($simulatorOrder);

        if (!empty($freeGifts)) {
            $rs = $this->freeGiftManagement->addFreeGiftsToCartProfile($rs, $freeGifts);
        }

        return $rs;
    }

    /**
     * @param $product
     * @return bool
     */
    private function getProductDeliveryType($product)
    {
        $deliveryType = $product->getCustomAttribute("delivery_type");

        if ($deliveryType) {
            return $deliveryType->getValue();
        }

        return false;
    }

    /**
     * Get address by id
     *
     * @param $id
     * @return bool|\Magento\Customer\Api\Data\AddressInterface
     */
    public function getAddressById($id)
    {
        try {
            return $this->addressRepository->getById($id);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $profileId
     * @return array|null
     */
    public function getNextDeliveryDateMain($profileId)
    {
        if ($this->helperProfile->getTmpProfile($profileId) !== false) {
            return $this->helperProfile->getNextDeliveryDateProfile($profileId);
        }
        return null;
    }
    /**
     * Group data and append calendar setting
     *
     * @param $arrReturn
     * @return array
     */
    private function groupDataAndAppendCalendarSetting($arrReturn)
    {
        // Group it
        foreach ($arrReturn as $addressId => $arrOfDeliveryType) {
            $arrDeliveryType = array_keys($arrOfDeliveryType);

            foreach ($arrOfDeliveryType as $deliveryType => $arrInfo) {
                $deliveryTypeEdited = $this->deliveryHelper->getDeliveryTypeNameInAllowGroup(
                    $deliveryType,
                    $arrDeliveryType
                );

                if ($deliveryTypeEdited == $deliveryType) {
                    continue;
                }

                if (! isset($arrReturn[$addressId][$deliveryTypeEdited])) {
                    $arrReturn[$addressId][$deliveryTypeEdited] = $arrInfo;
                } else {
                    $arrReturn[$addressId][$deliveryTypeEdited]['product'] = array_merge(
                        $arrReturn[$addressId][$deliveryTypeEdited]['product'],
                        $arrReturn[$addressId][$deliveryType]['product']
                    );

                    unset($arrReturn[$addressId][$deliveryType]); // Remove after group.
                }
            }
        }
        /** if profile have temp then
         * Available start date = MAX(Current system date (the day of placing order), Next delivery date of Main profile) + ...
         **/
        $nextDeliveryDate = $this->getNextDeliveryDateMain($this->getProfileId());
        /* append calendar setting */
        foreach ($arrReturn as $addressId => $arrOfDeliveryType) {
            foreach ($arrOfDeliveryType as $deliveryType => $arrInfo) {
                $bufferDay = null;
                $excludeBufferDays = $this->getCurrentSubscriptionCourse()->getData('exclude_buffer_days');
                if ($excludeBufferDays) {
                    $bufferDay = 0;
                }

                $restrictDate = $this->getHelperCalculateDateTime()->getCalendar(
                    $addressId,
                    $arrInfo,
                    $deliveryType,
                    $bufferDay,
                    $nextDeliveryDate
                );

                $calendarPeriod = $this->getHelperCalculateDateTime()->getCalendarPeriod();
                $arrReturn[$addressId][$deliveryType]["restrict_date"] = $restrictDate;
                $arrReturn[$addressId][$deliveryType]['is_exist_back_order_not_allow_choose_dd'] = 0;
                $arrReturn[$addressId][$deliveryType]["calendar_period"] = $calendarPeriod;
            }
        }

        return $arrReturn;
    }

    /**
     * check profile is stock point or not
     *
     * @return bool
     */
    public function isStockPointProfile()
    {
        $profileSession = $this->registry->registry('subscription_profile');
        return $this->validateStockPointProduct->checkProfileExistStockPoint($profileSession);
    }

    /**
     * check to show frequency box for profile edit page,
     *      do not need to show frequency box if profile is stock point
     *
     * @return bool
     */
    protected function canShowFrequencyBox()
    {
        $profileSession = $this->registry->registry('subscription_profile');

        /**
         * Check stock point for profile session
         */
        if ($profileSession->hasData('stock_point_data') &&
            is_array($profileSession->getData('stock_point_data'))
        ) {
            return false;
        }
        /**
         * Check profile exist
         */
        if ($profileSession->getData('stock_point_profile_bucket_id')) {
            return false;
        }

        return true;
    }

    /**
     * get bucket id from model
     * @return bool
     */
    public function isStockPointProfileFromModel()
    {
        $objModel = $this->registry->registry('subscription_profile_obj');
        $bucketId =  $objModel->getData("stock_point_profile_bucket_id");
        if ($bucketId) {
            return true;
        }
        return false;
    }

    /**
     * @param $entityId
     * @param int $type
     * @return array
     */
    public function getQuestionnaireAnswers($entityId, $type = QuestionnaireAnswer::FIELD_ENTITY_TYPE_ORDER)
    {
        $questionnaireAnswer = [];
        $answerCollection = $this->answersCollectionFactory->create();
        $answerCollection->addFieldToFilter('main_table.entity_id', $entityId);
        $answerCollection->addFieldToFilter('main_table.entity_type', $type);

        if ($answerCollection->getItems()) {
            $questionnaireIds = [];
            $answerIds = [];
            foreach ($answerCollection->getItems() as $answerItem) {
                $questionnaireIds[$answerItem->getData('enquete_id')] = $answerItem->getData('enquete_id');
                $answerIds[$answerItem->getData('enquete_id')] = $answerItem->getData('answer_id');
            }
            if ($questionnaireIds) {
                $answeredQuestionItems = $this->getQuestionsByQuestionnaireIds($questionnaireIds);
                if ($answeredQuestionItems) {
                    foreach ($answeredQuestionItems as $questionItem) {
                        $answerChoices = '';
                        if (array_key_exists($questionItem->getData('enquete_id'), $answerIds)) {
                            $answerChoices = $this->getAnswerReply(
                                $answerIds[$questionItem->getData('enquete_id')],
                                $questionItem->getQuestionId()
                            );
                        }
                        $questionnaireAnswer[] = [
                            'questionTitle' => $questionItem->getTitle(),
                            'answerChoices' => $answerChoices
                        ];
                    }
                }
            }
        }
        return $questionnaireAnswer;
    }

    /**
     * @param $questionnaireIds
     * @return \Magento\Framework\DataObject[]
     */
    public function getQuestionsByQuestionnaireIds($questionnaireIds)
    {
        $questionCollection = $this->questionCollectionFactory->create();
        $questionCollection->addFieldToFilter('enquete_id', ['in'=> $questionnaireIds]);
        $questionCollection->setOrder('sort_order', \Magento\Framework\Api\SortOrder::SORT_ASC);
        return $questionCollection->getItems();
    }

    /**
     * @param $answerId
     * @param $questionId
     * @return array
     */
    protected function getAnswerReply($answerId, $questionId)
    {
        $answerChoiceIds = [];
        $answerChoiceData = [];
        $replyCollection = $this->replyCollectionFactory->create();
        $replyCollection->addFieldToFilter('answer_id', $answerId);
        $replyCollection->addFieldToFilter('question_id', $questionId);
        if ($replyCollection->getItems()) {
            foreach ($replyCollection->getItems() as $item) {
                $answerChoiceIds[] = $item->getChoiceId();
                //text field answer
                if ($item->getData('content')) {
                    $answerChoiceData[] = $item->getData('content');
                }
            }
        }
        if ($answerChoiceIds) {
            $choiceCollection = $this->choiceCollectionFactory->create();
            $choiceCollection->addFieldToFilter('choice_id', ['in' => $answerChoiceIds]);
            $choiceCollection->setOrder('sort_order', 'ASC');
            if ($choiceCollection->getItems()) {
                foreach ($choiceCollection->getItems() as $choiceItem) {
                    $answerChoiceData[] = $choiceItem->getLabel();
                }
            }
        }
        return $answerChoiceData;
    }
}
