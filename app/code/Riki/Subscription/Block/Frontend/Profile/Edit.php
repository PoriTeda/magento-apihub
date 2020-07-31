<?php

namespace Riki\Subscription\Block\Frontend\Profile;

use Bluecom\Paygent\Model\Paygent;
use Bluecom\PaymentFee\Model\PaymentFeeFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftRegistry\Block\Customer\Edit\AbstractEdit;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\Payment\Model\Method\Free;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Riki\Customer\Model\Address\AddressType;
use Riki\Subscription\Model\Profile\Profile;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Helper\Order\Data as SubscriptionHelperOrderData;
use Riki\SubscriptionCourse\Model\ResourceModel\Course as SubscriptionCourseResourceModel;
use Riki\SubscriptionCourse\Plugin\Controller\Cart\Add;
use Riki\TimeSlots\Model\TimeSlots;
use Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership as Membership;
use Riki\Subscription\Model\ProductCart\ProductCart;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;
use Riki\PaymentBip\Model\InvoicedBasedPayment;

/**
 * Customer giftregistry edit block
 * @property PaymentFeeFactory paymentFeeFactory
 */
class Edit extends AbstractEdit
{
    const ADDRESS_TYPE_HOME_NO_COMPANY = 1;
    const ADDRESS_TYPE_HOME_HAVE_COMPANY = 2;
    const ADDRESS_TYPE_AMBASSADOR_COMPANY = 3;
    const ADDRESS_TYPE_ANOTHER = 4;
    const ADDRESS_LINK_EDIT_HOME_NO_COMPANY
        = 'subscriptionprofiledit/subscription_profile_edit_customer_address/subscription_profile_edit_address_home_no_company_name';
    const ADDRESS_LINK_EDIT_HOME_HAVE_COMPANY
        = 'subscriptionprofiledit/subscription_profile_edit_customer_address/subscription_profile_edit_address_home_have_company_name';
    const ADDRESS_LINK_EDIT_AMBASSADOR_COMPANY
        = 'subscriptionprofiledit/subscription_profile_edit_customer_address/subscription_profile_edit_address_ambassador_company';

    const XML_PATH_TAX_ORDER_DISPLAY_CONFIG = 'tax/sales_display/shipping';

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

    protected $_helperProfile;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $helperImage;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $_rewardManagement;

    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $_deliveryDate;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     */
    protected $wrappingCollectionFactory;

    /**
     * Gift wrapping data
     *
     * @var \Magento\GiftWrapping\Helper\Data|null
     */
    protected $giftWrappingData;

    /**
     * @var \Magento\GiftMessage\Model\MessageFactory
     */
    protected $messageFactory;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $_helperWrapping;

    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $_taxCalculation;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $_isAdmin = false;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $helperSimulator;

    /* @var \Riki\Subscription\Helper\Data */
    protected $_subHelperData;

    /* @var \Riki\SubscriptionCourse\Model\ResourceModel\Course */
    protected $_subCourseResourceModel;

    /* @var \Riki\SubscriptionCourse\Model\Course */
    protected $_subCourseModel;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /* @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $_customerAddressRepository;

    /* @var \Riki\TimeSlots\Model\TimeSlots */
    protected $_timeSlot;
    /**
     * @var \Riki\Subscription\Model\Profile\FreeGift
     */
    protected $_freeGiftManagement;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $_imageBuilder;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_timezoneHelper;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $_subCourseHelper;

    /* @var \Bluecom\Paygent\Model\PaygentHistory */
    protected $paygentHistory;

    /* @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $customerRepository;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    protected $catalogRuleHelper;

    protected $maxDate;

    protected $isConfirmRequest = false;
    /**
     * @var \Riki\SalesRule\Helper\CouponHelper
     */
    protected $_couponHelper;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    /**
     * @var \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    protected $calculateDeliveryDate;

    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $addressCustomer;

    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subscriptionHelper;

    protected $paymentFeeHelper;
    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $deliveryHelper;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $locale;

    /**
     * @var \Riki\Subscription\Helper\Indexer\Data
     */
    protected $profileIndexerHelper;
    /**
     * @var \Riki\Subscription\Model\ProfileCacheRepository
     */
    protected $profileCacheRepository;

    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data $helperStockPoint
     */
    protected $helperStockPoint;

    /**
     * Edit constructor.
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Riki\SubscriptionCourse\Model\Course $subCourseModel
     * @param \Magento\Checkout\Helper\Data $checkoutDataHelper
     * @param TimeSlots $timeSlots
     * @param AddressRepositoryInterface $addressRepositoryInterface
     * @param OrderAddress $orderAddress
     * @param SubscriptionCourseResourceModel $subCourseResourceModel
     * @param SubscriptionHelperData $subHelperData
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\GiftRegistry\Model\Attribute\Config $attributeConfig
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $customerAddressCollectionFactory
     * @param \Magento\Catalog\Helper\Image $helperImage
     * @param \Riki\Loyalty\Model\RewardManagement $rewardManagement
     * @param \Magento\Catalog\Model\Product\Media\Config $_mediaConfig
     * @param \Riki\DeliveryType\Model\DeliveryDate $deliveryDate
     * @param PaymentFeeFactory $paymentFeeFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingData
     * @param \Magento\GiftMessage\Model\MessageFactory $messageFactory
     * @param \Magento\GiftWrapping\Helper\Data $helperWrapping
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
     * @param \Riki\Subscription\Helper\Order\Simulator $helperSimulator
     * @param \Riki\ProductStockStatus\Helper\StockData $stockData
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Riki\Subscription\Model\Profile\FreeGift $freeGift
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Bluecom\Paygent\Model\PaygentHistory $paygentHistory
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepositoryInterface
     * @param \Magento\Store\Api\StoreResolverInterface $storeResolverInterface
     * @param \Magento\Store\Api\GroupRepositoryInterface $groupRepositoryInterface
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockStateRepository
     * @param \Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface $stockRegistryProviderInterface
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Magento\Swatches\Block\Product\Renderer\Configurable $renderConfigurable
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Block\Product\View $productView
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Riki\CatalogRule\Helper\Data $catalogRuleHelper
     * @param \Riki\SubscriptionCourse\Helper\Data $subCourseHelper
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\SalesRule\Helper\CouponHelper $couponHelper
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\DeliveryType\Helper\Data $deliveryHelper
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Magento\Framework\Locale\Resolver $locale
     * @param \Riki\Subscription\Helper\Indexer\Data  $profileIndexerHelper
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Riki\SubscriptionCourse\Model\Course $subCourseModel,
        TimeSlots $timeSlots,
        AddressRepositoryInterface $addressRepositoryInterface,
        SubscriptionCourseResourceModel $subCourseResourceModel,
        \Riki\Subscription\Helper\Data $subHelperData,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\GiftRegistry\Model\Attribute\Config $attributeConfig,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $customerAddressCollectionFactory,
        \Magento\Catalog\Helper\Image $helperImage,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Magento\Catalog\Model\Product\Media\Config $_mediaConfig,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        \Magento\GiftWrapping\Helper\Data $giftWrappingData,
        \Magento\GiftMessage\Model\MessageFactory $messageFactory,
        \Magento\GiftWrapping\Helper\Data $helperWrapping,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Riki\Subscription\Helper\Order\Simulator $helperSimulator,
        \Riki\ProductStockStatus\Helper\StockData $stockData,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Riki\Subscription\Model\Profile\FreeGift $freeGift,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Bluecom\Paygent\Model\PaygentHistory $paygentHistory,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepositoryInterface,
        \Magento\Store\Api\StoreResolverInterface $storeResolverInterface,
        \Magento\Store\Api\GroupRepositoryInterface $groupRepositoryInterface,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\CatalogInventory\Api\StockStateInterface $stockStateRepository,
        \Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface $stockRegistryProviderInterface,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Magento\Swatches\Block\Product\Renderer\Configurable $renderConfigurable,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Block\Product\View $productView,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Riki\CatalogRule\Helper\Data $catalogRuleHelper,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelper,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\SalesRule\Helper\CouponHelper $couponHelper,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDate,
        \Riki\Subscription\Helper\StockPoint\Data $helperStockPoint,
        \Magento\Customer\Model\Address $addressCustomer,
        \Riki\SubscriptionPage\Helper\Data $subscriptionHelper,
        \Bluecom\PaymentFee\Helper\Data $paymentFeeHelper,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\DeliveryType\Helper\Data $deliveryHelper,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Magento\Framework\Locale\Resolver $locale,
        \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository,
        array $data = []
    ) {
        $this->customerRepository = $customerRepositoryInterface;
        $this->_subCourseModel = $subCourseModel;
        $this->paygentHistory = $paygentHistory;
        $this->_timeSlot = $timeSlots;
        $this->_customerAddressRepository = $addressRepositoryInterface;
        $this->_subCourseResourceModel = $subCourseResourceModel;
        $this->_subHelperData = $subHelperData;
        $this->_isAdmin = ($context->getAppState()->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE);
        $this->_customerFactory = $customerFactory;
        $this->_mediaConfig = $_mediaConfig;
        $this->_rewardManagement = $rewardManagement;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->helperImage = $helperImage;
        $this->_helperProfile = $helperProfile;
        $this->customerAddressCollectionFactory = $customerAddressCollectionFactory;
        $this->_deliveryDate = $deliveryDate;
        $this->_productRepository = $productRepository;
        $this->wrappingCollectionFactory = $wrappingCollectionFactory;
        $this->giftWrappingData = $giftWrappingData;
        $this->messageFactory = $messageFactory;
        $this->_helperWrapping = $helperWrapping;
        $this->_taxCalculation = $taxCalculation;
        $this->helperSimulator = $helperSimulator;
        $this->_stockData = $stockData;
        $this->_authorization = $authorization;
        $this->_freeGiftManagement = $freeGift;
        $this->_imageBuilder = $imageBuilder;
        $this->_timezoneHelper = $timezone;

        $this->storeRepositoryInterface = $storeRepositoryInterface;
        $this->storeResolverInterface = $storeResolverInterface;
        $this->groupRepositoryInterface = $groupRepositoryInterface;
        $this->_categoryFactory = $categoryFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->stockStateRepository = $stockStateRepository;
        $this->stockRegistryProviderInterface = $stockRegistryProviderInterface;
        $this->stockRegistry = $stockRegistryInterface;
        $this->renderConfigurable = $renderConfigurable;
        $this->_coreRegistry = $coreRegistry;
        $this->blockProductView = $productView;
        $this->_localeFormat = $localeFormat;
        $this->helperStockPoint = $helperStockPoint;

        $this->catalogRuleHelper = $catalogRuleHelper;
        $this->_subCourseHelper = $subCourseHelper;
        $this->profileFactory = $profileFactory;
        $this->_couponHelper = $couponHelper;
        $this->calculateDeliveryDate = $calculateDeliveryDate;
        $this->addressCustomer = $addressCustomer;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->paymentFeeHelper = $paymentFeeHelper;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->deliveryHelper =  $deliveryHelper;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->locale = $locale;
        $this->profileIndexerHelper = $profileIndexerHelper;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $registry,
            $customerSession,
            $attributeConfig,
            $data
        );

        $this->setData('area', 'frontend');
    }

    public function getEntity()
    {
        return $this->_registry->registry('subscription_profile');
    }

    public function getAppState()
    {
        return $this->_appState;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Subscription Edit Title'));
    }

    public function getIsAdmin()
    {
        return $this->_isAdmin;
    }

    /**
     * Scope Selector 'registry/registrant'
     *
     * @var string
     */
    protected $_prefix = 'subscription';

    /**
     * Return array of attributes groupped by group
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getGroupedRegistryAttributes()
    {
        return $this->getGroupedAttributes();
    }

    /**
     * @return bool
     */
    public function getProfileCache()
    {
        return $this->getEntity();
    }

    public function loadOriginData()
    {
        $profileId = $this->_request->getParam('id');
        if ($this->_helperProfile->getTmpProfile($profileId) !== false) {
            $profileId = $this->_helperProfile->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        return $this->_helperProfile->load($profileId);
    }

    /**
     * Return privacy field selector (input type = select)
     *
     * @return string
     */
    public function getIsPublicHtml()
    {
        $options[''] = __('Please Select');
        $options += ['0' => __('Private'), '1' => __('Public')];
        $value = $this->getEntity()->getIsPublic();
        return $this->getSelectHtml($options, 'is_public', 'is_public', $value, 'required-entry');
    }

    /**
     * Return status field selector (input type = select)
     *
     * @return string
     */
    public function getStatusHtml()
    {
        $options = ['0' => __('Private'), '1' => __('Public')];
        $value = $this->getEntity()->getIsActive();
        return $this->getSelectHtml($options, 'is_active', 'is_active', $value, 'required-entry');
    }

    public function getTimeDelivery()
    {
        $options = ['0' => __('指定なし'), '1' => __('Time')];
        $value = $this->getEntity()->getIsActive();
        return $this->getSelectHtml($options, 'is_active', 'is_active', $value, 'required-entry');
    }

    public function getListFrequency($isAllow)
    {

        $arrAllFrequencyHasConcat = $this->_subCourseHelper->getFrequenciesByCourse($this->getEntity()->getData("course_id"));

        $frequency_unit = $this->getEntity()->getData("frequency_unit");
        $frequency_interval = $this->getEntity()->getData("frequency_interval");

        $value = $this->_subHelperData->getFrequencyIdByUnitAndInterval($frequency_unit, $frequency_interval);

        $strAttr = '';
        if (!$isAllow) {
            $strAttr = "disabled";
        }

        return $this->getSelectHtml2($arrAllFrequencyHasConcat, 'frequency_id', 'frequency_id', $value, 'required-entry', $strAttr);
    }

    public function getTextFrequencySelected()
    {
        $arrAllFrequencyHasConcat = $this->_subCourseHelper->getFrequenciesByCourse($this->getEntity()->getData("course_id"));
        $frequency_unit = $this->getEntity()->getData("frequency_unit");
        $frequency_interval = $this->getEntity()->getData("frequency_interval");
        $value = $this->_subHelperData->getFrequencyIdByUnitAndInterval($frequency_unit, $frequency_interval);
        return isset($arrAllFrequencyHasConcat[$value]) ? $arrAllFrequencyHasConcat[$value] : $arrAllFrequencyHasConcat[array_keys($arrAllFrequencyHasConcat)[0]];
    }

    public function getListPaymentMethod()
    {
        $obj = $this->_registry->registry('subscription_profile_obj');

        return $obj->getListPaymentMethodAvailable();
    }

    public function getFormDataPost()
    {
        return $this->getProfileCache()->getProfileEntityFormData(true);
    }

    public function getSelectHtml2($options, $name, $id, $value = false, $class = '', $strAttr = '')
    {
        $arrData = ['id' => $id, 'class' => 'select global-scope ' . $class];

        $select = $this->getLayout()->createBlock(
            'Magento\Framework\View\Element\Html\Select'
        )->setData(
            $arrData
        )->setName(
            $name
        )->setValue(
            $value
        )->setOptions(
            $options
        );

        $select->setExtraParams($strAttr);

        return $select->getHtml();
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/*');
    }

    public function getListProduct()
    {
        $profile_id = $this->getEntity()->getData("profile_id");

        $arrProducts = $this->_helperProfile->getArrProductCart($profile_id);

        return $arrProducts;
    }


    /**
     * @param null|\Riki\Subscription\Model\Emulator\Order $simulatorOrder
     * @return array
     */
    public function getListProductByAddressAndByDeliveryType($simulatorOrder = null)
    {
        if (is_null($simulatorOrder)) {
            $profileId = $this->getEntity()->getProfileId();
            $simulatorOrder = $this->getSimulatorOrderOfProfile($profileId);
        }
        /** Validate rule applied if coupon not can applied then remove it */
        $this->getListRulIdsApplied($simulatorOrder, $this->getEntity());

        $changeShippingAddressId = '';
        $profileCache = $this->getEntity();
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
                $product = $this->loadProductModelFromProfileItem($objProductData);

                if (!$product) {
                    continue;
                }

                $deliveryType = $product->getData("delivery_type");


                if (!isset($arrReturn[$addressId][$deliveryType])) {
                    /** @var \Magento\Customer\Model\Address  $objAddress */
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

    /**
     * @param $objProductData
     * @return mixed
     */
    public function loadProductModelFromProfileItem($objProductData)
    {
        $productId = $objProductData->getData('product_id');
        try {
            $product = $this->_productRepository->getById($productId);
        } catch (\Exception $e) {
            return null;
        }

        $profile = $this->getEntity();

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

    /**
     * @return mixed
     */
    public function getListProductOfCourse()
    {
        $key = 'list_product_of_course';
        if (!$this->hasData($key)) {
            $products= $this->_subCourseResourceModel->getListOfProductGroupByCategory($this->getEntity()->getData("course_id"));

            $this->setData($key, $products);
        }

        return $this->getData($key);
    }
    public function getListProductOfCourseAdditional($isAdditional = false)
    {
        $key = 'list_product_of_course_addition';
        if (!$this->hasData($key)) {
            $products= $this->_subCourseResourceModel->getListOfProductGroupByCategory($this->getEntity()->getData("course_id"), $isAdditional);

            $this->setData($key, $products);
        }

        return $this->getData($key);
    }

    public function getAllAddress()
    {
        // Get all Address of current customer
        $customerId = $this->getEntity()->getData("customer_id");

        $objCollection = $this->customerAddressCollectionFactory->create()->addAttributeToFilter("parent_id", $customerId);

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
            $arrReturn[$objAddress->getData("entity_id")]['address'] = implode(", ", $arrAddr);
            $arrReturn[$objAddress->getData("entity_id")]['name'] = $this->_helperProfile->getAddressNameByAddressId($objAddress->getId());
        }

        return $arrReturn;
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
     * @return array
     */
    public function getAllCustomerAddressDataForNewDesign()
    {
        // Get all Address of current customer
        $customerId = $this->getEntity()->getData("customer_id");

        $objCollection = $this->customerAddressCollectionFactory->create()->addAttributeToFilter("parent_id", $customerId);

        $objCollection->load();

        $arrReturn = [];

        $isCNCorCISMember = $this->isCNCOrCISMember();

        /** @var \Magento\Customer\Model\Address $objAddress */
        foreach ($objCollection as $objAddress) {
            $addressId = $objAddress->getData("entity_id");

            $customerData = $this->_customerAddressRepository->getById($addressId);
            $arrReturn[$addressId]['riki_nickname'] = $customerData->getCustomAttribute('riki_nickname')->getValue();
            $arrReturn[$addressId]['riki_nickname_label'] = __('Address') . ': ' . $arrReturn[$addressId]['riki_nickname'];
            $arrReturn[$addressId]['fullname']
                = $customerData->getLastname() . $customerData->getFirstname();
            $arrReturn[$addressId]['street']
                = $this->getCustomerAddressByText($addressId);
            $arrReturn[$addressId]['telephone'] = $customerData->getTelephone();

            $arrReturn[$addressId]['able_to_edit'] = 1;

            if ($isCNCorCISMember) {
                if ($addressType = $customerData->getCustomAttribute('riki_type_address')) {
                    $addressType = $addressType->getValue();

                    if ($addressType != AddressType::SHIPPING) {
                        $arrReturn[$addressId]['able_to_edit'] = 0;
                    }
                }
            }
        }

        return $arrReturn;
    }

    /**
     * For show address new design
     *
     * @param $addressId
     */
    public function getAddressDetail($addressId)
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

        $arrReturn['riki_nickname'] = $rikiNickname;
        $arrReturn['riki_firstname'] = $rikiFirstName;
        $arrReturn['riki_lastname'] = $rikiLastname;
        $arrReturn['riki_type_address'] = $rikiTypeAddress;
        $arrReturn['telephone'] = $customerAddressObj ? $customerAddressObj->getTelephone() : '';
        return $arrReturn;
    }

    public function getAllTimeSlot()
    {
        $timeSlot = $this->_deliveryDate->getListTimeSlot();
        return $timeSlot;
    }

    /**
     * @return array
     */
    public function getCourseSetting()
    {
        $courseId = $this->getEntity()->getData("course_id");

        $objCourse = $this->_subCourseHelper->loadCourse($courseId);

        if (empty($objCourse) || empty($objCourse->getId())) {
            return [];
        }

        return $objCourse->getSettings();
    }

    /**
     * @return array
     */
    public function getCourse()
    {
        $courseId = $this->getEntity()->getData("course_id");

        $objCourse = $this->_subCourseHelper->loadCourse($courseId);

        if (empty($objCourse) || empty($objCourse->getId())) {
            return [];
        }

        return $objCourse;
    }

    public function getProductImagesProfile($product)
    {

        if (empty($product->getThumbnail())) {
            return $this->helperImage->init($product, 'cart_page_product_thumbnail')
                ->keepFrame(false)
                ->constrainOnly(true)
                ->resize(160, 160)->getUrl();
        }

        return $this->_mediaConfig->getMediaUrl($product->getThumbnail());
    }

    /**
     * Retrieve product image
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->_imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }

    /**
     *
     */
    public function getTotal()
    {
        $priceOfProduct = $this->getPriceProduct();
        $giftWrappingPrice = $this->getGiftWrappingFee();
        $shippingPrice = $this->getFinalShippingFee();
        $paymentFee = $this->getPaymentFee();

        $pricePoint = $this->getPointUsed();

        return $this->formatCurrency($priceOfProduct + $giftWrappingPrice + $shippingPrice + $paymentFee - $pricePoint);
    }

    public function getTentativePoint()
    {
        $obj = $this->_registry->registry('subscription_profile_obj');

        return $obj->getTentativePointEarned();
    }

    public function getTentativePointMoney()
    {
        $price = number_format($this->getTentativePoint(), 3);

        return $this->formatCurrency($price);
    }

    public function getPointUsedMoney()
    {
        $price = $this->getPointUsed();
        $priceFormat = number_format($price, 3);
        return $this->formatCurrency($priceFormat);
    }


    /**
     * @return int
     */
    public function getGiftWrappingFee()
    {

        $wrappingTax = $this->_helperWrapping->getWrappingTaxClass($this->_storeManager->getStore());
        $wrappingRate = $this->_taxCalculation->getCalculatedRate($wrappingTax);

        $objEntity = $this->getEntity();

        $arrProduct = $objEntity->getData("product_cart");
        $wrapping_fee = 0;

        foreach ($arrProduct as $product_id => $_arrData) {
            if ($_arrData->getData('gw_id') > 0 && $_arrData->getData('gw_id') != null) {
                $giftpriceCollection = $this->wrappingCollectionFactory->create()
                    ->addFieldToFilter('wrapping_id', $_arrData->getData('gw_id'))
                    ->setPageSize(1)
                    ->addWebsitesToResult()->load();
                if ($giftpriceCollection) {
                    $wrapping_fee += ($giftpriceCollection->getFirstItem()->getData('base_price') * $_arrData->getData('qty'));
                }
            }
        }
        if ($wrapping_fee > 0) {
            $taxRate = $wrappingRate / 100;
            $wrapping_fee = $wrapping_fee + ($taxRate * $wrapping_fee);
        }
        return $wrapping_fee;
    }

    /**
     * @return int
     */
    public function getPriceProduct()
    {
        $objEntity = $this->getEntity();

        $arrProduct = $objEntity->getData("product_cart");
        $price = 0;
        foreach ($arrProduct as $productcart_id => $_arrData) {
            $product_id = $_arrData['product_id'];

            $objProduct = $this->_subHelperData->loadProductWithCache($product_id);
            $qty = $_arrData->getData("qty");

            $amount = $this->getRenderPrice($objProduct, $qty);
            $_p = $amount * $qty;

            $price = $price + $_p;
        }

        return $price;
    }


    public function getFinalShippingFee()
    {

        return $this->_helperProfile->getShippingFeeView($this->getEntity()->getData('product_cart'), $this->getEntity()->getData('store_id'));
    }

    public function getPaymentFee()
    {
        $paymentMethod = $this->getEntity()->getData("payment_method");

        return $this->paymentFeeHelper->getPaymentCharge($paymentMethod);
    }

    public function getCustomer($cid)
    {

        $objCustomer = $this->_customerFactory->create();
        $objCustomer->load($cid);

        return $objCustomer;
    }

    public function getPointUsed()
    {
        $customerId = $this->getEntity()->getData('customer_id');
        $customer = $this->getCustomer($customerId);
        $customerCode = $customer->getData('consumer_db_id');
        $pointSetting = $this->_rewardManagement->getRewardUserSetting($customerCode);
        return $pointSetting['use_point_amount'];
    }

    public function getUrlDeleteProductCart()
    {
        if ($this->getAppState()->getAreaCode() === "frontend") {
            return $this->getUrl('subscriptions/profile/delete');
        } else {
            return $this->getUrl('profile/profile/delete');
        }
    }

    public function getUrlEditProfile($profileId){
        $profileId = $this->_helperProfile->getProfileOriginFromTmp($profileId);
        return $this->getUrl('subscriptions/profile/edit', ['id'=>$profileId]);
    }

    public function getUrlAddProduct()
    {

        if ($this->getAppState()->getAreaCode() === "frontend") {
            return $this->getUrl('subscriptions/profile/add', ['_current' => true]);
        } else {
            return $this->getUrl('profile/profile/add', ['_current' => true]);
        }
    }

    /**
     * GetUrlAddMultipleProduct
     *
     * @return string
     */
    public function getUrlAddMultipleProduct()
    {
        return $this->getUrl('subscriptions/profile/addmultipleproduct', ['_current' => true]);
    }


    public function formatCurrency($price, $websiteId = null)
    {
        return $this->_storeManager->getWebsite($websiteId)->getBaseCurrency()->format($price);
    }

    public function isDisableAll()
    {
        $obj = $this->_registry->registry('subscription_profile_obj');

        if ($obj->getData('create_order_flag') == 1) {
            return true;
        }
        if ($obj->getData('status') == 2) {
            return true;
        }
        if (!$this->_helperProfile->checkLeadTimeIsActiveForProfile($obj->getData('profile_id'))) {
            return true;
        }
        return $obj->isInStage();
    }

    /**
     * @return \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    public function getHelperCalculateDateTime()
    {
        return $this->calculateDeliveryDate;
    }

    /**
     * @return bool
     */
    public function checkBtnUpdateAllChangePressed()
    {
        if (!$this->isConfirmRequest) {
            $profileCache = $this->getProfileCache();
            if ($profileCache && $profileCache->getData(Constant::CACHE_BTN_UPDATE_PRESSED) == '1') {
                $profileCache->setData(Constant::CACHE_BTN_UPDATE_PRESSED, null);
                $this->isConfirmRequest = true;
            }
        }

        return $this->isConfirmRequest;
    }

    public function getArrDeliveryType()
    {
        return $this->deliveryHelper->getArrDeliveryType();
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        $listProductOfCourse = $this->getListProductOfCourse();
        $arrayProduct = $this->getListProductId($listProductOfCourse);
        if (count($arrayProduct) > 0) {
            $this->catalogRuleHelper->registerPreLoadedProductIds($arrayProduct);
        }

        return $this;
    }

    public function getRewardUserRedeem($customerId)
    {
        return $this->getCustomer($customerId)->getRewardUserRedeem();
    }

    public function getRewardUserSettingHtmlSelect($customerId)
    {
        $name = 'reward_user_setting';
        $id = 'reward_user_setting';
        $title = 'User shopping point setting';
        $defValue = $this->getCustomer($customerId)->getRewardUserSetting();
        $options = [];
        $options[] = ['label' => __('Not use point'), 'value' => 0];
        $options[] = ['label' => __('Automatically use all points'), 'value' => 1];
        $options[] = ['label' => __('Automatically redeem a specified maximum number of points'), 'value' => 2];

        $html = $this->getLayout()->createBlock(
            'Magento\Framework\View\Element\Html\Select'
        )->setName(
            $name
        )->setId(
            $id
        )->setTitle(
            __($title)
        )->setValue(
            $defValue
        )->setOptions(
            $options
        )->setExtraParams(
            'data-validate="{\'validate-select\':true}"'
        )->getHtml();

        return $html;
    }

    public function getUrlGenerateOrder()
    {
        return $this->getUrl('profile/order/create');
    }


    /**
     * @return mixed
     */
    public function isChangePaymentMethod()
    {
        $profile = $this->_registry->registry('subscription_profile_obj');
        return $profile->getListPaymentMethodAvailable();
    }

    /**
     *  Area Hanpukai
     */
    public function isSubscriptionHanpukai()
    {
        $courseId = $this->getEntity()->getData("course_id");
        $subscriptionType = $this->subscriptionHelper->getSubscriptionType($courseId);
        if ($subscriptionType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * GetUnitDisplay
     *
     * @param $product
     * @return array
     */
    public function getUnitDisplay($product)
    {
        return $this->_helperProfile->getUnitQtyTypeOptions($this->getEntity()->getProfileId(), $product);
    }

    /**
     * GetUnitQty
     *
     * @param $product
     * @return int
     */
    public function getUnitQty($product)
    {
        if ($product->getCaseDisplay() != \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            return 1;
        } elseif ($product->getUnitQty()) {
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
        return $this->_productRepository->getById($id);
    }

    /**
     * @param $attributeString
     * @return mixed
     */
    public function getAttributeArray($attributeString)
    {
        $arrayAttr = explode(',', $attributeString);
        $giftCollection = $this->wrappingCollectionFactory->create()
            ->addFieldToFilter('wrapping_id', ['IN' => $arrayAttr])
            ->addWebsitesToResult()->load();

        return $giftCollection;
    }

    /**
     * @param $attributeString
     * @return string
     */
    public function getAttributeName($attributeString)
    {
        $nameAtt = '';
        $giftnameCollection = $this->wrappingCollectionFactory->create()
            ->addFieldToFilter('wrapping_id', $attributeString)
            ->addWebsitesToResult()->load();
        if ($giftnameCollection) {
            $nameAtt = $giftnameCollection->getFirstItem()->getData('gift_name');
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
        return $this->_scopeConfig->getValue(
            \Magento\GiftMessage\Helper\Message::XPATH_CONFIG_GIFT_MESSAGE_ALLOW_ITEMS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId()
        );
    }

    /**
     * @param $wrapping_fee
     * @return mixed
     */
    public function calTax($wrapping_fee)
    {
        $wrappingTax = $this->_helperWrapping->getWrappingTaxClass($this->_storeManager->getStore());
        $wrappingRate = $this->_taxCalculation->getCalculatedRate($wrappingTax);
        if ($wrapping_fee > 0) {
            $taxRate = $wrappingRate / 100;
            $wrapping_fee = $wrapping_fee + ($taxRate * $wrapping_fee);
        }
        return $wrapping_fee;
    }

    public function getSimulatorOrderOfProfile($profileId)
    {
        $profileCache = $this->getEntity();
        if ($profileCache) {
            $simulatorOrder = $this->helperSimulator->createSimulatorOrderHasData($profileCache);
            if ($simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {

                /** update data for profile cache simulate  */
                $this->profileIndexerHelper->updateDataProfileCache($profileId, $simulatorOrder);
                return $simulatorOrder;
            }
        }
        return false;
    }

    public function getFreeGiftItem($profileId)
    {
        $simulatorOrder = $this->getSimulatorOrderOfProfile($profileId);
        $freeGift = [];
        foreach ($simulatorOrder->getAllItems() as $item) {
            $buyRequest = $item->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                $freeGift[] = $item;
            }
        }
        return $freeGift;
    }

    public function getShippingFeeAfterSimulator($simulatorOrder, $storeId)
    {
        $taxDisplay = $this->_scopeConfig->getValue(self::XML_PATH_TAX_ORDER_DISPLAY_CONFIG, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        if ($taxDisplay == 1) {//exclude tax
            return $simulatorOrder->getShippingAmount();
        } else {
            return $simulatorOrder->getShippingInclTax();
        }
    }

    public function getSystemConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->_scopeConfig->getValue($path, $storeScope);
        return $config;
    }

    /**
     * @param $product
     * @param $productQty
     * @return string
     * @throws \Exception
     */
    public function getProductPrice($product, $productQty = 1)
    {
        if ($product->getTypeId() != 'bundle') {
            $amount = $this->getRenderPrice($product, $productQty);

            return ($amount !== null) ? $this->formatCurrency($amount) : '-';
        } else {
            $price = $this->_subHelperData->getBundleMaximumPrice($product);
            return $price ? $this->formatCurrency($price) : '-';
        }
    }

    /**
     * Get product stock status
     *
     * @param $product
     * @return \Magento\Framework\Phrase
     */
    public function getStockStatus($product)
    {
        $stock = $this->_stockData->getStockStatusByEnv(
            $product,
            \Riki\ProductStockStatus\Helper\StockData::ENV_FO
        );

        return $stock;
    }

    public function getRolesOfAdminUser()
    {
        if ($this->_authorization->isAllowed('Riki_Subscription::change_earn_point')) {
            return true;
        }
        return false;
    }

    public function getSlotObject($slotId)
    {
        $slotModel = $this->_timeSlot->load($slotId);
        if ($slotModel && $slotModel->getId()) {
            return $slotModel;
        }
        return null;
    }

    /**
     * In case of sp profile has delivery type = 4 (subcarier) we will allow customer to change delivery date as demand
     *
     *
     * @return boolean
     */
    public function isAllowStockpointToChangeDl(){
        $profile = $this->getEntity();
        return $profile->getData("stock_point_delivery_type") == Profile::SUBCARRIER;
    }

    /**
     * Simulator
     *
     * @param $data
     *
     * @return array|bool|null
     */
    public function simulatorOrderWithData($data)
    {
        if ($data) {
            try {
                return $this->helperSimulator->createSimulatorOrderHasData($data);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                return null;
            }
        }
        return null;
    }

    /**
     * Get url index
     *
     * @param $id
     * @return string
     */
    public function getBaseUrlSubcriptionProfileList()
    {
        return $this->getUrl('subscriptions/profile/');
    }


    /**
     * Return profile id from request
     *
     * @return mixed
     */
    public function getMainProfileId()
    {
        return $profileId = $this->getRequest()->getParam('id');
    }
    /**
     * Get current profile url
     */
    public function getCurrentEditProfileUrl()
    {
        $profileId = $this->getMainProfileId();
        return $this->_urlBuilder->getUrl('subscriptions/profile/edit', ['id' => $profileId]);
    }


    /**
     * Get shipping fee include tax
     *
     * @param $shippingFee
     * @return float
     */
    public function getShippingFeeIncludeTax($shippingFee)
    {
        return $this->_helperProfile->getShippingInclueTax($shippingFee, $this->_storeManager->getStore()->getId());
    }

    /**
     * Get last used date of credit card
     *
     * @return bool | string
     */
    public function getCcLastUsedDate($customerId)
    {
        $profileId = $this->getRequest()->getParams('id');
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

    public function getSeasonalProductConfig($productId)
    {
        $productRepo = $this->_productRepository->getById($productId);
        $config = [
            'allow_seasonal_skip' => null,
            'seasonal_skip_optional' => null,
            'allow_skip_from' => null,
            'allow_skip_to' => null,
        ];
        if ($productRepo->getCustomAttribute('allow_seasonal_skip') != null) {
            $config['allow_seasonal_skip'] = $productRepo->getCustomAttribute('allow_seasonal_skip')->getValue();
        }
        if ($productRepo->getCustomAttribute('seasonal_skip_optional') != null) {
            $config['seasonal_skip_optional'] = $productRepo->getCustomAttribute('seasonal_skip_optional')->getValue();
        }
        if ($productRepo->getCustomAttribute('allow_skip_from') != null) {
            $config['allow_skip_from'] = $productRepo->getCustomAttribute('allow_skip_from')->getValue();
        }
        if ($productRepo->getCustomAttribute('allow_skip_to') != null) {
            $config['allow_skip_to'] = $productRepo->getCustomAttribute('allow_skip_to')->getValue();
        }
        return $config;
    }

    public function defineDateTime($dateValue)
    {
        $time = strtotime($dateValue);
        $result = [];
        $result['year'] = date('Y', $time);
        $result['month'] = date('m', $time);
        $result['date'] = date('d', $time);

        return $result;
    }

    public function getCurrentDate()
    {
        $today = $this->_timezoneHelper->date()->format('Y-m-d');
        return $today;
    }

    /**
     * Get render price of product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $productQty
     * @return float|int
     */
    public function getRenderPrice(\Magento\Catalog\Model\Product $product, $productQty = 1)
    {
        $amount = $this->_subHelperData->getProductPriceInProfileEditPage($product, $productQty);

        $unitQty = 1;
        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            $unitQty = $product->getUnitQty() ? $product->getUnitQty() : 1;
        }

        return $amount * $unitQty;
    }

    /**
     * @param $date
     * @return string
     */
    public function getDateFormat($date)
    {
        return $this->_timezoneHelper->date($date)->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
    }

    /**
     * @param $productId
     * @return mixed
     */
    public function loadProductById($productId)
    {
        return $this->_productRepository->getById($productId);
    }

    /**
     * GetProductType
     *
     * @param $product
     * @return mixed
     */
    public function getProductType($product)
    {
        return $product->getTypeId();
    }

    /**
     * @param $product
     * @return mixed
     */
    public function getProductUrlByStore($product)
    {
        return $product->getUrlInStore(["_store" => $this->getStoreId()]);
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * Gets minimal sales quantity for subscription page
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int|null
     */
    public function getMinimalQty($product)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $minSaleQty = $stockItem->getMinSaleQty();
        return $minSaleQty > 0 ? $minSaleQty : null;
    }

    /**
     * Gets maximum sales quantity for subscription page
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int|null
     */
    public function getMaximumQty($product)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
        $maxSaleQty = $stockItem->getMaxSaleQty();
        return $maxSaleQty > 0 ? $maxSaleQty : null;
    }

    /**
     * @param $configurableProduct
     * @return mixed
     */
    public function getJsonConfig($configurableProduct)
    {
        return $this->renderConfigurable->setProduct($configurableProduct);
    }

    /**
     * @param $key
     */
    public function deleteRegister($key)
    {
        $this->_coreRegistry->unregister($key);
    }

    /**
     * @param $configurableProduct
     * @return mixed
     */
    public function getJsonPriceConfig($configurableProduct)
    {
        $this->_coreRegistry->register('product', $configurableProduct);
        $configurableBlock = $this->blockProductView;
        return $configurableBlock;
    }

    /**
     * @return string
     */
    public function getPriceFormat()
    {
        $result['price_format'] = $this->_localeFormat->getPriceFormat(null, 'JPY');
        return \Zend_Json::encode($result);
    }

    /**
     * @return string
     */
    public function getMachineDataJson()
    {
        $result = [];
        $result['machineIds'] = '';
        $result['machineData'] = '';
        $result['machineSelected'] = '';
        return \Zend_Json::encode($result);
    }

    /**
     * @param $frequencyUnit
     * @param $frequencyInterval
     * @return int|string
     */
    public function getFrequencyIdByUnitAndInterval($frequencyUnit, $frequencyInterval)
    {
        return $this->_subHelperData->getFrequencyIdByUnitAndInterval($frequencyUnit, $frequencyInterval);
    }

    /**
     * Link edit home no company name
     *
     * @return string
     */
    public function getUrlEditHomeNoCompany()
    {
        return $this->_subCourseHelper->getStoreConfig(
            self::ADDRESS_LINK_EDIT_HOME_NO_COMPANY
        ) . $this->_urlBuilder->getCurrentUrl();
    }


    /**
     * Link edit home have company name
     *
     * @return string
     */
    public function getUrlEditHomeHaveCompany()
    {
        return $this->_subCourseHelper->getStoreConfig(
            self::ADDRESS_LINK_EDIT_HOME_HAVE_COMPANY
        ) . $this->_urlBuilder->getCurrentUrl();
    }


    /**
     * Link edit ambassador company
     *
     * @return string
     */
    public function getUrlEditCompany()
    {
        return $this->_subCourseHelper->getStoreConfig(
            self::ADDRESS_LINK_EDIT_AMBASSADOR_COMPANY
        ) . $this->_urlBuilder->getCurrentUrl();
    }

    public function getListProductId($arrayProduct)
    {
        $listProduct = [];
        if (count($arrayProduct) > 0) {
            foreach ($arrayProduct as $products) {
                foreach ($products as $product) {
                    $listProduct[] = $product->getId();
                }
            }
        }
        return $listProduct;
    }

    public function showMessageInvalidLeadTime() {
        $obj = $this->_registry->registry('subscription_profile_obj');
        if (!$this->_helperProfile->checkLeadTimeIsActiveForProfile($obj->getData('profile_id'))) {
            return true;
        }
        return false;
    }

    /** Check if customer have CNC or CIS membership
     * @return bool
     */
    public function isCNCOrCISMember(){
        $customer =$this->customerSession->getCustomerData();
        if ($customer->getCustomAttribute('membership') instanceof \Magento\Framework\Api\AttributeValue) {
            $membershipStr = $customer->getCustomAttribute('membership')->getValue();
            if ($membershipStr!='') {
                $memberships = explode(',', $membershipStr);
                foreach ($memberships as $membership) {
                    if ($membership == Membership::CODE_5 || $membership == Membership::CODE_6) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Get last delivery of Profile
     *
     * @return int|false
     */
    public function getLastDeliveryDate()
    {
        $profileId = $this->getMainProfileId();
        $profileModel = $this->getEntity();
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

    /**
     * get Available end date for calendar edit profile
     *
     * @param $frequencyUnit
     * @param $frequencyInterval
     * @return \DateTime
     */
    public function calculateAvailableEndDate()
    {
        if ($this->maxDate) {
            return $this->maxDate;
        }
        $currentDate = $this->_localeDate->date()->format('Y-m-d');
        $lastDeliveryDate = $this->getLastDeliveryDate();
        $calendarPeriod = $this->getHelperCalculateDateTime()->getEditProfileCalendarPeriod() ?: 0;
        $maxCalendarPeriod = $this->_deliveryDate->getMaximumEditProfileCalendarPeriod();
        $maxAvailableDate = strtotime($maxCalendarPeriod . " month", strtotime($lastDeliveryDate));
        $maxDateTimestamp = strtotime($currentDate);
        if (strtotime($currentDate) < $maxAvailableDate) {
            $maxDateTimestamp = strtotime($calendarPeriod . " day", $maxDateTimestamp);
            if ($maxDateTimestamp > $maxAvailableDate) {
                $maxDateTimestamp = $maxAvailableDate;
            }
        }
        $courseSettings = $this->getCourseSetting();
        $hanpukaiAllowChangeDeliveryDate = $courseSettings['hanpukai_delivery_date_allowed'];
        $hanpukaiDeliveryDateTo = $courseSettings['hanpukai_delivery_date_to'];
        if ($hanpukaiAllowChangeDeliveryDate) {
            $maxDateTimestamp = strtotime($hanpukaiDeliveryDateTo);
        }

        $this->maxDate = $this->_localeDate->scopeDate(null, date('Y-m-d', $maxDateTimestamp));

        return $this->maxDate;
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

        $minDate = $this->_localeDate->scopeDate(null, date('Y-m-d', $startDate + 86400));

        $courseSettings = $this->getCourseSetting();
        $hanpukaiAllowChangeDeliveryDate = $courseSettings['hanpukai_delivery_date_allowed'];
        $hanpukaiDeliveryDateFrom = $courseSettings['hanpukai_delivery_date_from'];
        if ($hanpukaiAllowChangeDeliveryDate && strtotime($hanpukaiDeliveryDateFrom) > $startDate) {
            $minDate = $this->_localeDate->scopeDate(null, date('Y-m-d', strtotime($hanpukaiDeliveryDateFrom)));
        }

        return $minDate;
    }

    /**
     * get Delivery Date to show text beside Calendar
     *
     * @return mixed|null
     */
    public function getMainDeliveryDateForText(){
        $profileId = $this->getMainProfileId();
        $profileModel = $this->getEntity();
        $type = $profileModel->getData('type');
        $deliveryDate = $profileModel->getData('next_delivery_date');
        switch ($type) {
            case 'version':
            {
                $profileOriginModel = $this->_helperProfile->loadProfileModel($profileModel->getData('profile_id'), true);
                if ($profileOriginModel->getId()) {
                    $deliveryDate = $profileOriginModel->getData('next_delivery_date');
                }
                break;
            }
            case 'tmp':
            {
                $profileLink = $this->_helperProfile->getTmpProfile($profileId);
                if ($profileLink and $profileLink->getId()) {
                    if ($profileLink->getData('change_type') == 1) {
                        $profileOriginModel =  $this->profileFactory->create()->load($profileId, null, true);
                        if ($profileOriginModel->getId()) {
                            $originDeliveryDate = $profileOriginModel->getData('next_delivery_date');
                            $deliveryDate = date(
                                'Y-m-d',
                                strtotime($profileOriginModel->getFrequencyInterval()." ".$profileOriginModel->getFrequencyUnit(), strtotime($originDeliveryDate))
                            );
                        }
                    }
                }
                break;
            }
            default:
                $deliveryDate = $profileModel->getData('next_delivery_date');
        }
        return $deliveryDate;
    }

    public function showProfileType1() {
        $profileId = $this->getMainProfileId();
        $profileModel = $this->getEntity();
        $productCart = $profileModel->getData('product_cart');
        $profileOriginalModel = $this->profileFactory->create()->load($profileId, null, true);
        if ($profileOriginalModel->getId()) {
            $nextDeliveryDateOriginal = $profileOriginalModel->getData('next_delivery_date');
            foreach ($productCart as $product) {
                if (strtotime($nextDeliveryDateOriginal) != strtotime($product->getData('delivery_date'))) {
                    return false;
                }
            }
        }

        return true;
    }
    public function getMinDeliveryDateOfProductCart() {
        $profileModel = $this->getEntity();
        $productCart = $profileModel->getData('product_cart');
        $minDeliveryDate = null;
        foreach ($productCart as $product) {
            $minDeliveryDate =  $product['delivery_date'];
            if (strtotime($minDeliveryDate) > strtotime($product['delivery_date'])) {
                $minDeliveryDate = $product['delivery_date'];
            }
        }
        return $minDeliveryDate;
    }

    /**
     * Get list rule coupon
     *
     * @param $orderSimulator
     * @param $dataFromSession
     * @return array|bool
     * @throws LocalizedException
     */
    public function getListRulIdsApplied($orderSimulator, $dataFromSession)
    {

        if (!$orderSimulator) {
            return false;
        }
        $profileId = $dataFromSession->getProfileId();
        if (!$profileId) {
            return false;
        }

        $couponCodeSession = $dataFromSession->getCouponCode();

        $ruleId = $orderSimulator->getAppliedRuleIds();
        $listCouponCode = $orderSimulator->getCouponCode();

        //add message when auto remove coupon code
        $this->_couponHelper->addMessageRemoveCouponCode($orderSimulator, $couponCodeSession);

        $data = $this->_couponHelper->checkCouponRealIdsWhenProcessSimulator($ruleId, $listCouponCode);
        if (is_array($data)) {
            return $data;
        }

        //clear session if not exist session coupon
        $this->clearCouponCodeNotApplied();

        return false;
    }

    /**
     * Build html list coupon
     *
     * @param $profileId
     * @return string
     */
    public function getHtmlListCouponApplied($profileId)
    {
        $html = [];
        $profileCache = $this->getEntity();
        if (isset($profileCache)) {
            $stringCoupon = $profileCache->getCouponCode();
            $listCouponApplied = explode(',', $stringCoupon);

            if (is_array($listCouponApplied) && count($listCouponApplied) > 0) {
                foreach ($listCouponApplied as $couponCode) {
                    if ($couponCode != '') {
                        $html[] = '
                            <div class="applied-coupon">
                                <div class="title">' . __('Coupon use') . '</div>
                                <div class="applied-coupon-item">
                                    <input type="hidden" class="amCouponsCode" value="' . $couponCode . '" />
                                    <span>' . $couponCode . '</span>
                                    <a data-profile-id="'.trim($profileId).'" data-coupon-code="'.trim($couponCode).'" class="delete-coupon"  href="javascript:;">' . __('Cancel Coupon') . '</a>
                                </div>
                            </div>                
                        ';
                    }
                }
            }
        }

        //clear session
        if (count($html)<=0) {
            $this->clearCouponCodeNotApplied();
        }

        return implode('', $html);
    }

    /**
     * Get validate message coupon
     *
     * @param $profileId
     * @return null|string
     */
    public function getMessageValidateCoupon()
    {
        $message = null;
        $profileCachae =$this->getEntity();
        if (isset($profileCachae)) {
            $dataValidateCoupon = $profileCachae->getData('validateCoupon');

            if ($dataValidateCoupon!=null && isset($dataValidateCoupon['message'])) {
                if ($dataValidateCoupon['is_validate']) {
                    $message ='<span data-bind="style: { color: \'green\' }">'.$dataValidateCoupon['message'].'</span>';
                } else {
                    $message ='<span data-bind="style: { color: \'red\' }">'.$dataValidateCoupon['message'].'</span>';
                }
            }
        }
        return $message;
    }

    /**
     * Clear session coupon can not applied
     *
     * @param $profileId
     */
    public function clearCouponCodeNotApplied()
    {
        $profileCache = $this->getEntity();
        if (isset($profileCache)) {
            $profileCache->setData('coupon_code', null);
        }
        $this->profileCacheRepository->save($profileCache);
    }

    /**
     * @param $simulatorOrder
     * @return array
     */
    public function getListProductOnProfile($simulatorOrder)
    {
        $productCart = $simulatorOrder->getItems();
        $arrData = [];
        if (!empty($productCart) && is_array($productCart)) {
            foreach ($productCart as $productId => $item) {
                $product = $this->loadProductById($item->getProductId());
                if ($product) {
                    $arrData[$item->getProductId()]   = [
                        'product' => $product,
                        'qty' =>$item->getQtyOrdered()
                    ];
                }
            }
        }

        return $arrData;
    }

    /**
     *
     */
    public function getUrlPostShowMap()
    {
        return $this->buildStockPointPostData->getUrlPostMap();
    }

    /**
     * Group data by delivery type
     *
     * @param $deliveryData
     * @return array
     */
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

    /**
     * Check show button stock point
     *
     * @param $simulatorOrderObject
     * @param $profileModelData
     * @param $dlInformation
     * @return bool|null
     */
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

    /**
     * Check show delivery message
     * If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
     * AND interval_unit="month"
     * AND not Stock Point
     *
     * @return boolean
     */
    public function isShowDeliveryMessage()
    {
        $profileData = $this->getEntity();

        $courseData = $this->_subCourseHelper->loadCourse($profileData->getData('course_id'));

        if (empty($courseData) || empty($courseData->getId())) {
            return false;
        }

        if (isset($courseData['next_delivery_date_calculation_option'])
            && $courseData['next_delivery_date_calculation_option']
            == \Riki\SubscriptionCourse\Model\Course::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK
            && $profileData->getData('frequency_unit') == 'month'
            && !$profileData->getData('stock_point_profile_bucket_id')
        ) {
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

    /**
     * Get url update next delivery date in message
     *
     * @return array
     */
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

    /**
     * Get url update next delivery date in message
     *
     * @return array
     */
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

    /**
     * Only get next delivery date of main if profile have temp
     * @return mixed|null
     */
    public function getNextDeliveryDateOfMain()
    {
        $profileId = $this->getMainProfileId();
        $profileLink = $this->_helperProfile->getTmpProfile($profileId);
        if ($profileLink and $profileLink->getId()) {
            $profileOriginModel =  $this->profileFactory->create()->load($profileId, null, true);
            if ($profileOriginModel->getId()) {
                return $profileOriginModel->getData('next_delivery_date');
            }
        }
        return null;
    }
}
