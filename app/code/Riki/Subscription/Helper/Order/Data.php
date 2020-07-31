<?php
namespace  Riki\Subscription\Helper\Order;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Model\Method\Free;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Nestle\Gillette\Api\Data\CartEstimationInterface;
use Nestle\Gillette\Api\Data\ProductInfoInterface;
use Riki\AdvancedInventory\Exception\AssignationException;
use Riki\Customer\Model\Address\AddressType;
use Riki\DeliveryType\Model\Delitype;
use Riki\Customer\Api\Data\CustomerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderManagementInterface;
use \Magento\Framework\App\State;
use \Magento\Framework\Stdlib\DateTime\Timezone;
use \Riki\AdvancedInventory\Helper\Inventory as InventoryHelper;
use Riki\EmailMarketing\Helper\Order as OrderHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Subscription\Model\Constant;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Riki\CatalogInventory\Model\StockRegistryProvider;
use Riki\AdvancedInventory\Model\Assignation as AssignationModel;
use Riki\Subscription\Model\Data\ApiProfile;
use Riki\Subscription\Model\ProductCart\ProductCart;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const PAYGENT_EMPTY_TRADING = 'empty_trading';
    /*CONST for setting email out of stock*/
    const CONFIG_SEND_EMAIL_OUT_OF_STOCK_ENABLE = 'subcreateorder/outofstock/enable';
    const CONFIG_SEND_EMAIL_OUT_OF_STOCK_TEMPLATE = 'subcreateorder/outofstock/email_template';
    const CONFIG_SEND_EMAIL_OUT_OF_STOCK_FROM = 'subcreateorder/outofstock/sender';
    /*CONST for setting email out of stock - send to ADMIN*/
    const CONFIG_SEND_EMAIL_OUT_OF_STOCK_ADMIN_ENABLE = 'subcreateorder/outofstockadmin/enable';
    const CONFIG_SEND_EMAIL_OUT_OF_STOCK_ADMIN_TEMPLATE = 'subcreateorder/outofstockadmin/email_template';
    const CONFIG_SEND_EMAIL_OUT_OF_STOCK_ADMIN_FROM = 'subcreateorder/outofstockadmin/sender';
    const CONFIG_SEND_EMAIL_OUT_OF_STOCK_ADMIN_TO = 'subcreateorder/outofstockadmin/receiver';

    /*CONST for setting email dsiabled or removed */
    const CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_ENABLE = 'subcreateorder/disabledorremoved/enable';
    const CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_TEMPLATE = 'subcreateorder/disabledorremoved/email_template';
    const CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_FROM = 'subcreateorder/disabledorremoved/sender';
    const CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_TO = 'subcreateorder/disabledorremoved/receiver';
    /*CONST for setting email authorization fail*/
    const CONFIG_SEND_EMAIL_AUTHORIZATION_FAIL_ENABLE = 'subcreateorder/authorizationfailed/enable';
    const CONFIG_SEND_EMAIL_AUTHORIZATION_FAIL_TEMPLATE = 'subcreateorder/authorizationfailed/email_template';
    const CONFIG_SEND_EMAIL_AUTHORIZATION_FAIL_FROM = 'subcreateorder/authorizationfailed/sender';
    const CONFIG_SEND_EMAIL_AUTHORIZATION_FAIL_TO = 'subcreateorder/authorizationfailed/receiver';

    const XPATH_PAYMENT_ERROR_EMAIL_SENDER = 'subcreateorder/payment_method_error/sender';
    const XPATH_PAYMENT_ERROR_EMAIL_TEMPLATE = 'subcreateorder/payment_method_error/email_template';

    /*CONST for free machine*/
    const CONFIG_FREE_MACHINE_EMAIL_ENABLE = 'freemachine/outofstock/enable';
    const CONFIG_FREE_MACHINE_EMAIL_TEMPLATE_OOS = 'freemachine/outofstock/email_template_oos';
    const CONFIG_FREE_MACHINE_EMAIL_TEMPLATE_AMBASSADOR = 'freemachine/outofstock/email_template_did_not_purchase';
    const CONFIG_FREE_MACHINE_EMAIL_TEMPLATE_AMBASSADOR_SUB =
        'freemachine/outofstock/email_template_did_not_purchase_sub';
    const CONFIG_FREE_MACHINE_EMAIL_SENDER = 'freemachine/outofstock/sender';
    const CONFIG_FREE_MACHINE_EMAIL_RECEIVER = 'freemachine/outofstock/receiver';
    const CONFIG_PATH_MONTHLY_FEE_SKU_FOR_VARIABLE_FEE = 'freemachine/monthly_fee/sku_for_variable_fee';
    const CONFIG_FREE_MACHINE_PAYGENT_AUTHORIZE_FAIL_SENDER = 'freemachine/paygent_authorize_fail/sender';
    const CONFIG_FREE_MACHINE_PAYGENT_AUTHORIZE_FAIL_EMAIL_TEMPLATE = 'freemachine/paygent_authorize_fail/email_template';

    const PROFILE_GENERATE_STATE_REGISTRY_NAME = 'generate_order_state';

    const IS_PROFILE_GENERATED_ORDER_KEY = 'is_generate';

    const ASSIGNED_WAREHOUSE_ID_KEY = 'profile_assigned_warehouse_id';

    const IS_INCOMPLETE_GENERATE_PROFILE_ORDER = 'is_incomplete_generate_profile_order';

    const SUBSCRIPTION_PROFILE_ID_FIELD_NAME = 'profile_id';

    const IS_SIMULATOR_PROFILE_NAME = 'is_simulator';

    /*const which will be use for stock point data*/
    const IS_STOCK_POINT_PROFILE = 'is_stock_point_profile';
    const PROFILE_STOCK_POINT_BUCKET_ID = 'stock_point_profile_bucket_id';
    const PROFILE_STOCK_POINT_DELIVERY_TYPE = 'stock_point_delivery_type';
    const PROFILE_STOCK_POINT_DELIVERY_INFORMATION = 'stock_point_delivery_information';

    /*flag to check new order is stock point order*/
    const IS_STOCK_POINT_ORDER = 'is_stock_point_order';

    const ORDER_STOCK_POINT_DELIVERY_BUCKET_ID = 'stock_point_delivery_bucket_id';
    const ORDER_IS_STOCK_POINT = 'is_stock_point';
    /*flag to check this profile will be generated an order that shipping address is stock point address*/
    const PROFILE_USE_STOCK_POINT_ADDRESS = 'is_used_stock_point_address';
    const PROFILE_GENERATE_USE_STOCK_POINT_ADDRESS = 'profile_generate_use_stock_point_address';
    const NED2831_LIST_PLACE_IDS_REGISTRY_KEY = 'ned2831_list_place_ids';
    const GILLETTE_CUSTOM_AVAILABLE_START_DATE = 'gillette/general/custom_available_start_date';

    const STOCK_POINT_ORIGINAL_DELIVERY_DATE = 'stock_point_original_delivery_date';
    const STOCK_POINT_ORIGINAL_DELIVERY_TIME_SLOT = 'stock_point_original_delivery_time_slot';
    const IS_REMOVE_STOCK_POINT = 'is_remove_stock_point';

    /**
     * @var
     */
    protected $customer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $_formkey;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quote;
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddress;
    /**
     * @var \Magento\Sales\Model\Service\OrderService
     */
    protected $orderService;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    protected $productCartFactory;
    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $_loggerOrder;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Riki\AutomaticallyShipment\Model\CreateShipment
     */
    protected $_createShipment;
    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $_modelAssignation;
    /**
     * @var \Wyomind\Core\Helper\Data
     */
    protected $_coreHelperData;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;
    /**
     * @var \Riki\Subscription\Model\Frequency\FrequencyFactory
     */
    protected $frequencyFactory;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_checkoutBackendSession;
    /**
     * @var \Riki\Prize\Model\PrizeFactory
     */
    protected $_prizeFactory;
    /**
     * @var \Riki\Prize\Helper\Data
     */
    protected $_prizeHelper;

    protected $_createOrderRepository;
    /**
     * @var \Riki\Subscription\Helper\Hanpukai\Data
     */
    protected $_hanpukaiHelper;
    /**
     * @var \Amasty\Promo\Model\Registry
     */
    protected $promoRegistry;
    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $promoItemHelper;
    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $_addressHelper;
    /**
     * @var \Riki\Checkout\Model\Order\Address\ItemFactory
     */
    protected $_addressItemFactory;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;
    /**
     * @var State
     */
    protected $state;
    /**
     * @var Timezone
     */
    protected $_tzHelper;

    protected $orderAddress;
    /**
     * @var InventoryHelper
     */
    protected $inventoryHelper;
    /**
     * @var Email
     */
    protected $emailOrderBuilder;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface
     */
    protected $giftWrapping;
    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $deliveryDate;
    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;
    /**
     * @var \Riki\Loyalty\Model\RewardQuoteFactory
     */
    protected $rewardQuoteFactory;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;
    /**
     * @var \Riki\SubscriptionMachine\Helper\Order\Generate
     */
    protected $freeMachine;

    protected $orderHelper;
    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $rikiCustomerRepository;
    /**
     * @var \Riki\BackOrder\Helper\Data
     */
    protected $backOrderHelper;
    /**
     * @var \Riki\Subscription\Model\Email\ProfilePaymentMethodError
     */
    protected $profilePaymentMethodErrorEmail;
    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile
     */
    protected $profileIndexer;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $giftWrappingHelper;
    /**
     * @var AddressRepositoryInterface
     */
    protected $customerAddressRepository;
    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $quoteAddressFactory;
    /**
     * @var \Riki\Coupons\Helper\Coupon
     */
    protected $couponHelper;

    protected $customerInfo;

    /** @var \Riki\Loyalty\Helper\Data  */
    protected $loyaltyHelper;

    /** @var \Magento\Catalog\Helper\Product  */
    protected $productHelper;

    /**
     * @var \Riki\ShipLeadTime\Api\StockStateInterface
     */
    protected $leadTimeStockStatus;
    /**
     * @var \Riki\AdvancedInventory\Helper\Assignation
     */
    protected $assignationHelper;
    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    protected $stockPointHelper;

    /**
     * @var \Riki\AdvancedInventory\Observer\OosCapture
     */
    protected $outOfStockCapture;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $helperCourse;

    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $deliveryTypeHelper;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Config
     */
    protected $quoteConfig;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Riki\AdvancedInventory\Model\Stock
     */
    protected $stockModel;

    /**
     * @var \Riki\CreateProductAttributes\Model\Product\CaseDisplay
     */
    protected $caseDisplay;

    protected $validatorMonthlyFee;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $gilletteLogger;

    /**
     * @var \Wyomind\AdvancedInventory\Api\StockRepositeryInterface
     */
    protected $wyomindStockRepository;

    /**
     * Data constructor.
     * @param InventoryHelper $inventoryHelper
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Data\Form\FormKey $formkey
     * @param \Magento\Quote\Model\QuoteFactory $quote
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\AddressFactory $customerAddress
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory
     * @param \Riki\Subscription\Logger\LoggerOrder $loggerOrder
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Riki\AutomaticallyShipment\Model\CreateShipment $createShipment
     * @param AssignationModel $modelAssignation
     * @param \Wyomind\Core\Helper\Data $coreHelperData
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param OrderManagementInterface $orderManagement
     * @param \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Riki\Subscription\Api\CreateOrderRepositoryInterface $createOrderRepository
     * @param \Riki\Subscription\Helper\Hanpukai\Data $hanpukaiHelper
     * @param \Amasty\Promo\Helper\Item $promoItemHelper
     * @param \Amasty\Promo\Model\Registry $promoRegistry
     * @param \Riki\Sales\Helper\Address $addressHelper
     * @param \Riki\Checkout\Model\Order\Address\ItemFactory $orderItemAddress
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param State $state
     * @param Timezone $timezone
     * @param \Magento\Sales\Model\Order\AddressRepository $orderAddressRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Email $emailOrder
     * @param \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository
     * @param \Riki\DeliveryType\Model\DeliveryDate $deliveryDate
     * @param \Riki\Loyalty\Model\RewardManagement $rewardManagement
     * @param \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Riki\SubscriptionMachine\Helper\Order\Generate $freeMachine
     * @param OrderHelper $orderHelper
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     * @param \Riki\BackOrder\Helper\Data $backOrderHelper
     * @param \Riki\Subscription\Model\Email\ProfilePaymentMethodError $profilePaymentMethodErrorEmail
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingHelper
     * @param AddressRepositoryInterface $customerAddressRepository
     * @param \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory
     * @param \Riki\Coupons\Helper\Coupon $couponHelper
     * @param \Riki\Loyalty\Helper\Data $loyaltyHelper
     * @param \Riki\ShipLeadTime\Api\StockStateInterface $leadTimeStockStatus
     * @param \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
     * @param \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper
     * @param \Riki\AdvancedInventory\Observer\OosCapture $oosCapture
     * @param \Riki\SubscriptionCourse\Helper\Data $helperCourse
     * @param ProductCollectionFactory $productCollectionFactory
     * @param \Riki\DeliveryType\Helper\Data $deliveryTypeHelper
     * @param \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile|null $profileIndexer
     */
    public function __construct(
        InventoryHelper $inventoryHelper,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Data\Form\FormKey $formkey,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\AddressFactory $customerAddress,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Riki\Subscription\Logger\LoggerOrder $loggerOrder,
        \Magento\Framework\Registry $registry,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Riki\AutomaticallyShipment\Model\CreateShipment $createShipment,
        \Riki\AdvancedInventory\Model\Assignation $modelAssignation,
        \Wyomind\Core\Helper\Data $coreHelperData,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        OrderManagementInterface $orderManagement,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Riki\Subscription\Api\CreateOrderRepositoryInterface $createOrderRepository,
        \Riki\Subscription\Helper\Hanpukai\Data $hanpukaiHelper,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Riki\Sales\Helper\Address $addressHelper,
        \Riki\Checkout\Model\Order\Address\ItemFactory $orderItemAddress,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        State $state,
        Timezone $timezone,
        \Magento\Sales\Model\Order\AddressRepository $orderAddressRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\Subscription\Helper\Order\Email $emailOrder,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\SubscriptionMachine\Helper\Order\Generate $freeMachine,
        OrderHelper $orderHelper,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        \Riki\Subscription\Model\Email\ProfilePaymentMethodError $profilePaymentMethodErrorEmail,
        \Magento\GiftWrapping\Helper\Data $giftWrappingHelper,
        AddressRepositoryInterface $customerAddressRepository,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Riki\Coupons\Helper\Coupon $couponHelper,
        \Riki\Loyalty\Helper\Data $loyaltyHelper,
        \Riki\ShipLeadTime\Api\StockStateInterface $leadTimeStockStatus,
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper,
        \Riki\AdvancedInventory\Observer\OosCapture $oosCapture,
        \Riki\SubscriptionCourse\Helper\Data $helperCourse,
        ProductCollectionFactory $productCollectionFactory,
        \Riki\DeliveryType\Helper\Data $deliveryTypeHelper,
        \Magento\Quote\Model\Quote\Config $quoteConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\AdvancedInventory\Model\Stock $stockModel,
        \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay,
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $validatorMonthlyFee,
        \Wyomind\AdvancedInventory\Api\StockRepositeryInterface $wyomindStockRepository,
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profileIndexer = null
    ) {
        $this->profileIndexer = $profileIndexer;
        $this->inventoryHelper = $inventoryHelper;
        $this->_tzHelper = $timezone;
        $this->state = $state;
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->_productFactory = $productFactory;
        $this->_formkey = $formkey;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->customerAddress =  $customerAddress;
        $this->orderService = $orderService;
        $this->profileFactory =$profileFactory;
        $this->productCartFactory =$productCartFactory;
        $this->_loggerOrder = $loggerOrder;
        $this->_registry = $registry;
        $this->quoteRepository = $quoteRepository;
        $this->_createShipment = $createShipment;
        $this->_modelAssignation = $modelAssignation;
        $this->_coreHelperData = $coreHelperData;
        $this->_objectManager = $objectManager;
        $this->_eventManager = $context->getEventManager();
        $this->_messageManager = $messageManager;
        $this->orderManagement = $orderManagement;
        $this->frequencyFactory = $frequencyFactory;
        $this->_checkoutBackendSession = $sessionQuote;
        $this->_createOrderRepository = $createOrderRepository;
        $this->_hanpukaiHelper = $hanpukaiHelper;
        $this->promoItemHelper = $promoItemHelper;
        $this->promoRegistry = $promoRegistry;
        $this->stockRegistry = $stockRegistry;
        $this->_addressItemFactory = $orderItemAddress;
        $this->_addressHelper = $addressHelper;
        $this->orderAddress = $orderAddressRepository;
        $this->checkoutSession = $checkoutSession;
        $this->emailOrderBuilder = $emailOrder;
        $this->giftWrapping = $wrappingRepository;
        $this->deliveryDate = $deliveryDate;
        $this->rewardManagement = $rewardManagement;
        $this->rewardQuoteFactory = $rewardQuoteFactory;
        $this->profileRepository = $profileRepository;
        $this->freeMachine = $freeMachine;
        $this->orderHelper = $orderHelper;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
        $this->backOrderHelper = $backOrderHelper;
        $this->profilePaymentMethodErrorEmail = $profilePaymentMethodErrorEmail;
        $this->giftWrappingHelper = $giftWrappingHelper;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->couponHelper = $couponHelper;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->leadTimeStockStatus = $leadTimeStockStatus;
        $this->assignationHelper = $assignationHelper;
        $this->stockPointHelper = $stockPointHelper;
        $this->outOfStockCapture = $oosCapture;
        $this->helperCourse = $helperCourse;
        $this->productHelper = $backOrderHelper->getProductHelper();
        $this->productCollectionFactory = $productCollectionFactory;
        $this->deliveryTypeHelper = $deliveryTypeHelper;
        $this->quoteConfig = $quoteConfig;
        $this->resourceConnection = $resourceConnection;
        $this->stockModel = $stockModel;
        $this->caseDisplay = $caseDisplay;
        $this->validatorMonthlyFee = $validatorMonthlyFee;
        $this->gilletteLogger = $context->getLogger();
        $this->wyomindStockRepository = $wyomindStockRepository;
        parent::__construct($context);
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * @return \Magento\Framework\Registry
     */
    public function getRegistry()
    {
        return $this->_registry;
    }

    /**
     * @return \Riki\Customer\Model\CustomerRepository
     */
    public function getRikiCustomerRepository()
    {
        return $this->rikiCustomerRepository;
    }

    /**
     * @param $profileId
     * @param null $isSimulator
     * @param null $iDeliveryNumber
     * @param null $cartData
     * @return array|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProfile(
        $profileId,
        $isSimulator = null,
        $iDeliveryNumber = null,
        $cartData = null,
        $isExportBi = false,
        $changedData = []
    ) {

        $orderData = [];
        $addresses = [];
        $profileModel = $this->profileFactory->create()->load($profileId);
        if ($profileModel->getId()) {
            if ($this->_registry->registry('subscription_profile_obj')) {
                $this->_registry->unregister('subscription_profile_obj');
            }
            $this->_registry->register('subscription_profile_obj', $profileModel);
            $orderData['profile_id'] = $profileId;
            $orderData['customer_id'] = $profileModel->getData('customer_id');
            $orderData['store_id'] = $profileModel->getData('store_id');
            $orderData['payment_method'] = $profileModel->getData('payment_method');
            $orderData['trading_id'] = $profileModel->getData('trading_id');
            $orderData['shipping_method'] = $profileModel->getData('shipping_condition');
            $orderData['course_id'] =  $profileModel->getData('course_id');
            $orderData['course_name'] =  $profileModel->getData('course_name');
            $orderData['order_times'] = $profileModel->getData('order_times');
            $orderData['order_channel'] = $profileModel->getData('order_channel');
            $orderData['frequency_unit'] =  $profileModel->getData('frequency_unit');
            $orderData['frequency_interval'] =  $profileModel->getData('frequency_interval');
            $orderData['create_order_flag'] =  $profileModel->getData('create_order_flag');
            $orderData['earn_point_on_order'] =  $profileModel->getData('earn_point_on_order');
            $orderData['coupon_code'] =  isset($changedData['coupon_code'])? $changedData['coupon_code'] : $profileModel->getData('coupon_code');
            $orderData['next_delivery_date'] =  $profileModel->getData('next_delivery_date');
            $orderData[self::ASSIGNED_WAREHOUSE_ID_KEY] =  $profileModel->getData('specified_warehouse_id');
            $orderData[\Riki\Subscription\Api\Data\ApiProfileInterface::IS_MONTHLY_FEE_CONFIRMED]
                = $profileModel->getData(\Riki\Subscription\Api\Data\ApiProfileInterface::IS_MONTHLY_FEE_CONFIRMED);


            /*Monthly Fee*/
            if($this->validatorMonthlyFee->isMonthlyFeeProfile($profileId)) {
                $referenceProfile = $this->profileRepository->get($profileModel->getData('reference_profile_id'));
                $orderData['reference_trading_id'] =  $referenceProfile->getTradingId();
                $orderData['payment_method'] = \Bluecom\Paygent\Model\Paygent::CODE;
                $orderData['is_monthly_fee'] = true;
            } else {
                $orderData['is_monthly_fee'] = false;
            }


            /*data that will be use for stock point flow*/
            $orderData[self::IS_STOCK_POINT_PROFILE] = $profileModel->isStockPointProfile();
            $orderData[self::PROFILE_STOCK_POINT_BUCKET_ID] = $profileModel->getData(self::PROFILE_STOCK_POINT_BUCKET_ID);
            $orderData[self::PROFILE_STOCK_POINT_DELIVERY_TYPE] = $profileModel->getData(self::PROFILE_STOCK_POINT_DELIVERY_TYPE);
            $orderData[self::PROFILE_STOCK_POINT_DELIVERY_INFORMATION] = $profileModel->getData(self::PROFILE_STOCK_POINT_DELIVERY_INFORMATION);
            $orderData[self::PROFILE_USE_STOCK_POINT_ADDRESS] = $profileModel->isUsedStockPointAddress();


            $versionProfileId = $profileModel->getId();
            if ($cartData) {
                $productCartModel = $cartData;
            } else {
                $productCartModel = $this->productCartFactory->create()->getCollection();
                $productCartModel->addFieldToFilter('profile_id', $versionProfileId, $isExportBi);
            }

            $productCartData = [];

            $subscriptionType = $this->_hanpukaiHelper->getSubscriptionCourseType($orderData['course_id']);
            $hanpukaiType = $this->_hanpukaiHelper->getHanpukaiType($orderData['course_id']);
            $multiQty = $profileModel->getData('hanpukai_qty');

            if ($iDeliveryNumber) { // for prepare product hanpukai with specific delivery number
                if (is_array($productCartModel) && isset($productCartModel[0])) {
                    $productHanpukaiInfo = $productCartModel[0];
                } else {
                    $productHanpukaiInfo = $productCartModel->getFirstItem();
                }

                $productHanpukai = $this->_hanpukaiHelper->getHanpukaiProductDataPieceCase(
                    $hanpukaiType,
                    $orderData['course_id'],
                    (int)$iDeliveryNumber
                );

                $multiQty = $profileModel->getData('hnapukai_qty');

                foreach ($productHanpukai as $productId => $value) {
                    $productModel = $this->_productFactory->create()->load($productId);

                    $unitCase = (null != $value['unit_case'])?$value['unit_case']:'EA';
                    $unitQty = (null != $value['unit_qty'])?$value['unit_qty']:1;

                    $productData = [
                        'product_id' => $productId,
                        'parent_item_id' => (null != $productHanpukaiInfo->getData('parent_item_id'))?
                            $productHanpukaiInfo->getData('parent_item_id'):0,
                        'qty' => $value['qty'] * $multiQty,
                        'unit_qty' => $unitQty,
                        'unit_case' => $unitCase,
                        'price' => $productModel->getPrice(),
                        'gw_id' => null,
                        'billing_address_id' => $productHanpukaiInfo->getData('billing_address_id'),
                        'shipping_address_id' => $productHanpukaiInfo->getData('shipping_address_id'),
                        'delivery_date' => $profileModel->getData('next_delivery_date'),
                        'is_skip_seasonal' => $productHanpukaiInfo->getData('is_skip_seasonal'),
                        'skip_from' => $productHanpukaiInfo->getData('skip_from'),
                        'skip_to' => $productHanpukaiInfo->getData('skip_to'),
                        'is_spot' => $productHanpukaiInfo->getData('is_spot'),
                        'is_addition' => $productHanpukaiInfo->getData('is_addition'),
                        'stock_point_discount_rate' => $productHanpukaiInfo->getData('stock_point_discount_rate'),
                        'original_delivery_date' => $productHanpukaiInfo->getData(ProductCart::ORIGINAL_DELIVERY_DATE),
                        'original_delivery_time_slot' => $productHanpukaiInfo->getData(
                            ProductCart::ORIGINAL_DELIVERY_TIME_SLOT
                        )
                    ];
                    $addresses[] = $productHanpukaiInfo->getData('billing_address_id');
                    $addresses[] = $productHanpukaiInfo->getData('shipping_address_id');
                    $productData['delivery_time'] = null;
                    $productData['delivery_time_id'] = null;
                    $productData['delivery_time_from'] = null;
                    $productData['delivery_time_to'] = null;
                    $productData['original_delivery_time'] = null;
                    $productData['original_delivery_time_from'] = null;
                    $productData['original_delivery_time_to'] = null;
                    $productCartData[$productHanpukaiInfo->getData('shipping_address_id')][] = $productData;
                }
            } else {
                foreach ($productCartModel as $productCartItem) {
                    $productModel = $this->_productFactory->create()->load($productCartItem->getData('product_id'));
                    $timeSlot = $this->deliveryDate->getTimeSlotInfo($productCartItem->getData('delivery_time_slot'));

                    $originTimeSlot = $this->deliveryDate->getTimeSlotInfo(
                        $productCartItem->getData(ProductCart::ORIGINAL_DELIVERY_TIME_SLOT)
                    );
                    $productData = [
                        'product_id' => $productCartItem->getData('product_id'),
                        'parent_item_id' => (null != $productCartItem->getData('parent_item_id'))?
                            $productCartItem->getData('parent_item_id'):0,
                        'unit_qty' => (null != $productCartItem->getData('unit_qty'))?
                            $productCartItem->getData('unit_qty'):1,
                        'unit_case' => (null != $productCartItem->getData('unit_case'))?
                            $productCartItem->getData('unit_case'):'EA',
                        'price' => $productModel->getPrice(),
                        'gw_id' => $productCartItem->getData('gw_id'),
                        'gift_message_id' => $productCartItem->getData('gift_message_id'),
                        'billing_address_id' => $productCartItem->getData('billing_address_id'),
                        'shipping_address_id' => $productCartItem->getData('shipping_address_id'),
                        'delivery_date' => $productCartItem->getData('delivery_date'),
                        'is_skip_seasonal' => $productCartItem->getData('is_skip_seasonal'),
                        'skip_from' => $productCartItem->getData('skip_from'),
                        'skip_to' => $productCartItem->getData('skip_to'),
                        'is_spot' => $productCartItem->getData('is_spot'),
                        'is_addition' => $productCartItem->getData('is_addition'),
                        'stock_point_discount_rate' => $productCartItem->getData('stock_point_discount_rate')
                    ];
                    $addresses[] = $productCartItem->getData('billing_address_id');
                    $addresses[] = $productCartItem->getData('shipping_address_id');
                    if ($subscriptionType == 'hanpukai' and $hanpukaiType == 'hsequence') {
                        $productData['qty'] = $productCartItem->getData('qty') * $multiQty;
                    } else {
                        $productData['qty'] = $productCartItem->getData('qty');
                    }
                    if ($timeSlot != false and $timeSlot->hasData('id')) {
                        $productData['delivery_time'] = $timeSlot->getData('slot_name');
                        $productData['delivery_time_id'] = $productCartItem->getData('delivery_time_slot');
                        $productData['delivery_time_from'] = $timeSlot->getData('from');
                        $productData['delivery_time_to'] = $timeSlot->getData('to');
                    } else {
                        $productData['delivery_time'] = null;
                        $productData['delivery_time_id'] = null;
                        $productData['delivery_time_from'] = null;
                        $productData['delivery_time_to'] = null;
                    }
                    if ($originTimeSlot != false) {
                        $productData['original_delivery_time'] = $originTimeSlot->getData('slot_name');
                        $productData['original_delivery_time_slot'] =
                            $productCartItem->getData(ProductCart::ORIGINAL_DELIVERY_TIME_SLOT);
                        $productData['original_delivery_time_from'] = $originTimeSlot->getData('from');
                        $productData['original_delivery_time_to'] = $originTimeSlot->getData('to');
                    } else {
                        $productData['original_delivery_time'] = null;
                        $productData['original_delivery_time_slot'] = null;
                        $productData['original_delivery_time_from'] = null;
                        $productData['original_delivery_time_to'] = null;
                    }
                    $productData['original_delivery_date'] =
                        $productCartItem->getData(ProductCart::ORIGINAL_DELIVERY_DATE);
                    $productCartData[$productCartItem->getData('shipping_address_id')][] = $productData;
                }

                // If this is subscription type monthly fee and is_monthly_fee_confirmed is 1
                // Add item with sku for variable fee from config to
                if ($subscriptionType == SubscriptionType::TYPE_MONTHLY_FEE &&
                    $profileModel->getData('is_monthly_fee_confirmed')
                ) {
                    $result = $this->addItemWithSkuVariableFeeForSubscriptionMonthlyFee(
                        $profileModel,
                        $productCartModel,
                        $productCartData
                    );

                    if (!$result) {
                        return false;
                    }
                }
            }

            if (count($productCartData) >= 1) {
                /*Call update customer before generate order*/

                if ($this->customer && isset($this->customer[$orderData['customer_id']])) {
                    $customer = $this->customer[$orderData['customer_id']];
                } else {
                    $customer = $this->customerRepository->getById($orderData['customer_id']);
                    $this->customer[$orderData['customer_id']] = $customer;
                }
                $consumerDBId = $customer->getCustomAttribute('consumer_db_id');
                if ($consumerDBId and !$isSimulator) {
                    $consumerId = $consumerDBId->getValue();
                    $this->customerFactory->create()->getResource()->setNeedHandleDuplicateEmailException(true);
                    try {
                        $customerAllInfo = $this->rikiCustomerRepository->prepareAllInfoCustomer($consumerId);
                        if (!isset($this->customerInfo[$consumerId])
                            || (isset($this->customerInfo[$consumerId]) &&
                                $this->customerInfo[$consumerId] !=
                                md5(\Zend\Serializer\Serializer::serialize($customerAllInfo))
                            )
                        ) {
                            $this->customer[$orderData['customer_id']] = $this->rikiCustomerRepository->createUpdateEcCustomer(
                                $customerAllInfo,
                                $consumerId,
                                null,
                                $customer
                            );
                            $this->customerInfo[$consumerId] =
                                md5(\Zend\Serializer\Serializer::serialize($customerAllInfo));
                        }
                    } catch (\Exception $e) {
                        $this->_loggerOrder->critical($e);
                        return false;
                    }
                }

                $shippingAddressId = key($productCartData);
                try {
                    $shippingAddress = $this->customerAddressRepository->getById($shippingAddressId);
                } catch (NoSuchEntityException $e) {
                    $shippingAddress = false;
                }

                if ($shippingAddress) {
                    $orderData['shipping_address'] = $shippingAddress;
                } else {
                    if (!$isSimulator) {
                        $this->_loggerOrder->addError(
                            'Subscription profile '.
                            $profileId .
                            ' has a invalid shipping address '.
                            $shippingAddressId
                        );
                        $this->_messageManager->addError(
                            'Subscription profile ' .
                            $profileId .
                            ' has a invalid shipping address '.
                            $shippingAddressId
                        );
                    }
                    return false;
                }

                $billingAddressId = $productCartData[$shippingAddressId][0]['billing_address_id'];
                try {
                    $billingAddress = $this->customerAddressRepository->getById($billingAddressId);
                } catch (NoSuchEntityException $e) {
                    $billingAddress = false;
                }
                if ($billingAddress) {
                    $orderData['billing_address'] = $billingAddress;
                } else {
                    if (!$isSimulator) {
                        $this->_loggerOrder->addError(
                            'Subscription profile '.
                            $profileId .
                            ' has a invalid billing address '.
                            $billingAddressId
                        );
                        $this->_messageManager->addError(
                            'Subscription profile ' .
                            $profileId .
                            ' has a invalid billing address '.
                            $billingAddressId
                        );
                    }
                    return false;
                }
                $orderData['items'] = [];
                foreach ($productCartData as $product) {
                    foreach ($product as $item) {
                        $orderData['items'] = $this->_addItemToProductCart($orderData['items'], $item);
                    }
                }
            }

            $addresses = array_unique($addresses);
            if (!empty($addresses)) {
                $addressModel = $this->customerAddress->create()->getCollection();
                $addressModel->addFieldToFilter('entity_id', $addresses);
                if ($addressModel->getSize() < count($addresses)) {
                    if (!$isSimulator) {
                        $this->_loggerOrder->addError(
                            'Subscription profile ' .
                            $profileId .
                            ' has invalid shipping/billing addresses.'
                        );
                        $this->_messageManager->addError(
                            'Subscription profile ' .
                            $profileId .
                            ' has invalid shipping/billing addresses.'
                        );
                    }

                    return false;
                }
            }

            if (!isset($orderData['items'])) {
                if (!$isSimulator) {
                    $this->_loggerOrder->info(
                        'Subscription profile #'.
                        $orderData['profile_id'].
                        ' does not has any products'
                    );
                    $this->_messageManager->addError(
                        'Subscription profile #' .
                        $orderData['profile_id'] .
                        ' does not has any products'
                    );
                }

                return false;
            }
            if (!$this->validateHanpukaiSubscriptionLimit($hanpukaiType, $profileModel)) {
                $hanpukaiValidateMsg = 'Subscription profile #'.$orderData['profile_id'].' was reached hanpukai maximum order times.';
                $orderData['hanpukai_error_msg'] = $hanpukaiValidateMsg;
            }
            return $orderData;
        }

        return false;
    }

    /**
     * Create Order On Your Store
     *
     * @param array $orderData
     * @param null $arrPost
     * @param null $isSimulator
     * @param null $isNewPaygent
     * @param bool $isList
     * @param bool $isAdmin
     *
     * @return mixed
     * @throws \Exception
     */
    public function createMageOrder(
        $orderData,
        $arrPost = null,
        $isSimulator = null,
        $isNewPaygent = null,
        $isList = false,
        $isAdmin = false
    ) {
        $origParams = [$orderData, $arrPost, $isSimulator, $isNewPaygent, $isList, $isAdmin];
        $consumerId = null;
        if (!$orderData) {
            return false;
        }
        $this->_registry->unregister('generate_order_state');
        $this->_registry->register('generate_order_state', true);
        $profileId = $orderData['profile_id'];

        if ($this->customer && !empty($this->customer[$orderData['customer_id']])) {
            $customer = $this->customer[$orderData['customer_id']];
        } else {
            $customer = $this->customerRepository->getById($orderData['customer_id']);
            $this->customer[$orderData['customer_id']] = $customer;
        }
        $customer = $this->customerRepository->getById($orderData['customer_id']);
        $this->_customer[$orderData['customer_id']] = $customer;
        $consumerDBId = $customer->getCustomAttribute('consumer_db_id');
        if ($consumerDBId) {
            $consumerId = $consumerDBId->getValue();
        }
        $this->_registry->unregister('is_generate');
        $this->_registry->register('is_generate', true);

        $store = $this->_storeManager->getStore($orderData['store_id']);
        if ($this->_registry->registry('cron_store_id')) {
            $this->_registry->unregister('cron_store_id');
        }
        $this->_storeManager->setCurrentStore($store->getId());
        $this->_registry->register('cron_store_id', $orderData['store_id']);

        /** @var \Riki\Catalog\Model\Quote $quote */
        $quote=$this->quote->create()->setIsActive(0); //Create object of quote
        $quote->setStore($store); //set store for which you create quote
        // if you have allready buyer id then you can load customer directly
        $quote->setData(self::IS_PROFILE_GENERATED_ORDER_KEY, 1);
        if ($isSimulator) {
            $this->_registry->unregister('is_simulator');
            $this->_registry->register('is_simulator', true);

            $quote->setData('is_simulator', true);

            $this->productHelper->setSkipSaleableCheck(true);
        }

        /*flag to check this profile is used stock point or not*/
        $isStockPointProfile = $orderData[self::IS_STOCK_POINT_PROFILE];

        /*flag to check new order is stock point order*/
        $isStockPointOrder = false;

        /*specified Warehouse Id that profile will be assigned to*/
        $specifiedWarehouseId = $orderData[self::ASSIGNED_WAREHOUSE_ID_KEY];

        if ($isStockPointProfile) {
            $isStockPointOrder = true;
            /*for profile that will be used stock point, all products will be assigned to this warehouse*/
            $specifiedWarehouseId = $this->assignationHelper->getDefaultPosForStockPoint();
        }

        /*flag to check this quote is stock point or not*/
        $quote->setData(self::IS_STOCK_POINT_PROFILE, $isStockPointProfile);
        /*flag to check this quote will create stock point order or normal order*/
        $quote->setData(self::IS_STOCK_POINT_ORDER, $isStockPointOrder);

        $quote->setCurrency();
        $quote->setData('profile_id', $profileId);
        $quote->setData(self::SUBSCRIPTION_PROFILE_ID_FIELD_NAME, $profileId);
        $quote->setData(self::ASSIGNED_WAREHOUSE_ID_KEY, $specifiedWarehouseId);
        $quote->setData(AssignationModel::ASSIGNED_WAREHOUSE_ID, $specifiedWarehouseId);

        if (isset($orderData['order_channel'])) {
            $quote->setData('order_channel', $orderData['order_channel']);
        }

        $result = [];
        if (!isset($orderData['items'])) {
            $this->_loggerOrder->info('Profile #'.$orderData['profile_id'].' does not has any products');
            if (!$isSimulator) {
                $this->_messageManager->addError('Profile #' . $orderData['profile_id'] . ' does not has any products');
            }
            return $result;
        }

        $billingAddress = $this->quoteAddressFactory->create();
        $billingAddress = $billingAddress->importCustomerAddressData($orderData['billing_address']);

        $customerShippingAddress = $this->quoteAddressFactory->create();
        $customerShippingAddress = $customerShippingAddress->importCustomerAddressData($orderData['shipping_address']);

        /*this profile will be got shipping address via stock point address*/
        if ($orderData[self::PROFILE_USE_STOCK_POINT_ADDRESS]) {
            /*add new quote address with special type - "customer"*/
            $quote->setCustomerShippingAddress($customerShippingAddress);

            /**
             * This is the flag for profile has to use the address of the stock point
             */
            $quote->setData(self::PROFILE_GENERATE_USE_STOCK_POINT_ADDRESS, true);

            /*for this case, order shipping address is stock point address*/
            $shippingAddress = $this->getStockPointAddressByProfileId($orderData['profile_id']);

            if (!$shippingAddress) {
                /*cannot get stock point address*/
                $this->_loggerOrder->info('Profile #'.$orderData['profile_id'].' - Stock point address is invalid.');
                if (!$isSimulator) {
                    $this->_messageManager->addError('Profile #' . $orderData['profile_id'] . ' - Stock point address is invalid.');
                }
                return $result;
            }
        } else {
            /*shipping address is customer shipping address for remaining case*/
            $shippingAddress = $customerShippingAddress;
        }

        $quote->assignCustomerWithAddressChange($customer, $billingAddress, $shippingAddress);

        /*add item to quote before generate order*/
        $this->addItemToQuoteBeforeGenerateOrder($quote, $orderData, $isSimulator);

        if (!$quote->getAllItems() && $isStockPointOrder) {
            /**
             *  for case stock point profile can not create stock point order
             *      (because all items are out of stock in stock point warehouse)
             *      try to create normal order for this profile
             */
            $stockPointMessage = 'The profile #'.$orderData['profile_id']
                .' can not create stock point order because all products are not available in stock point warehouse';
            $this->_loggerOrder->addError($stockPointMessage);
            $this->_loggerOrder->addInfo('Try to create normal order for profile #'.$orderData['profile_id']);

            /*reset stock point order flag - to reject some stock point order logic*/
            $isStockPointOrder = false;

            /**
             * reset stock point order flag for current quote
             *      - remember that we do not reset stock point profile flag to make sure normal order
             *        can apply stock point discount rate
             */
            $quote->setData(self::IS_STOCK_POINT_ORDER, false);
            /*reset stock point warehouse for current quote*/
            $quote->setData(self::ASSIGNED_WAREHOUSE_ID_KEY, 0);
            $quote->setData(AssignationModel::ASSIGNED_WAREHOUSE_ID, 0);
            /*reset current quote shipping address by customer shipping address*/
            $quote->setShippingAddress(
                $customerShippingAddress->setAddressType(\Magento\Customer\Model\Address\AbstractAddress::TYPE_SHIPPING)
            );
            /**
             * Reset data for generating profile file stock point
             */
            $quote->setData(self::PROFILE_GENERATE_USE_STOCK_POINT_ADDRESS, false);

            /*clean out of stock item has been generated for stock point flow*/
            $this->outOfStockCapture->cleanOutOfStocks($quote->getId());

            /*re-try to add item to current quote again*/
            $this->addItemToQuoteBeforeGenerateOrder($quote, $orderData, $isSimulator);
        }

        /*list out of stock product - try to get it again because data has change*/
        $productOutOfStock = $this->getListOfOutOfStockProduct();

        if (!$quote->getAllItems()) {
            $message = 'The profile #'.$orderData['profile_id']
                .' can not create order because all product is not available';

            $this->_loggerOrder->addError($message);

            if (!$isSimulator) {
                if (!empty($productOutOfStock)) {
                    /*send out of stock email*/
                    $this->sendEmailStockOutOfStock(null, $productOutOfStock, $orderData, $customer->getEmail());
                }

                $this->_messageManager->addError($message);
            }
            return false;
        }

        /*list of products are disabled or need to be removed out of profile*/
        $productDisabledAndRemoved = $this->getListOfProductDisabledAndRemoved();
        /*list of products need to be removed out of profile*/
        $productRemoved = $this->getListOfProductNeedToBeRemoved();
        /*list of spot product need to be remove out of profile after generated order success*/
        $spotProduct = $this->getListOfSpotProduct();

        // Collect Rates and Set Shipping & Payment Method
        $shippingAddress=$quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->setShippingMethod($orderData['shipping_method']); //shipping method
        $quote->setShippingAddress($shippingAddress);
        $quote->setInventoryProcessed($isSimulator);
        $quote->save(); //Now Save quote and your quote is ready

        /**
         * Although address has been saved, isObjectNew flag still is true.
         * We set isObjectNew flag to false so rule validator's processRule function can use the cache.
         */
        $quote->getShippingAddress()->isObjectNew(false);

        // Set Sales Order Payment
        if (isset($orderData['payment_method']) &&
            $orderData['payment_method'] == \Bluecom\Paygent\Model\Paygent::CODE &&
            !$isNewPaygent
        ) {
            if ($orderData['trading_id']) {
                //set trading id for automatically make authorize
                if ($this->_registry->registry('trading_id') !== null) {
                    $this->_registry->unregister('trading_id');
                }
                $this->_registry->register('trading_id', $orderData['trading_id']);

            } elseif($orderData['is_monthly_fee']) {
                if ($this->_registry->registry('trading_id') !== null) {
                    $this->_registry->unregister('trading_id');
                }

                $orderData['trading_id'] = $orderData['reference_trading_id'];

                $this->_registry->register('trading_id', $orderData['trading_id']);
            } else {
                if ($this->_registry->registry('trading_id') !== null) {
                    $this->_registry->unregister('trading_id');
                }
                $this->_registry->register('trading_id', self::PAYGENT_EMPTY_TRADING);
                $this->_loggerOrder->info(
                    'Subscription Profile Id '.$orderData['profile_id'].
                    ' can not get Sequence Id(trading id) for authorize'
                );
            }
        }
        //implement task #RIKI-5209, send notification email when generate order with payment method is null
        if ($this->_registry->registry('quote_admin')) {
            $this->_registry->unregister('quote_admin');
        }
        $this->_registry->register('quote_admin', $quote);
        $this->profilePaymentMethodErrorEmail
            ->getVariables()
            ->setData('quote', $quote);

        $orderData['payment_method'] = $this->preparePaymentMethod(
            $quote,
            $orderData['payment_method'],
            $isSimulator,
            isset($orderData['is_generate_by_cron'])
        );

        if (!$orderData['payment_method']) {
            return false;
        }

        $paymentData['method'] = $orderData['payment_method'];
        $paymentData['checks'] = [
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_INTERNAL,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
        ];
        $quote->getPayment()->addData($paymentData);
        $this->setOtherInfoForQuote($quote, $orderData, $orderData['profile_id']);
        // Collect Totals & Save Quote
        $quote->setIsAutoAddFirstItem(true);

        if (!$isList && !$isSimulator || $isAdmin) {
            $this->addRewardPoint($quote, $arrPost, $customer, true);
        }

        $quote->setData('allowed_earned_point', $orderData['earn_point_on_order']);
        /*Apply coupon code*/
        if (isset($orderData['coupon_code']) and $orderData['coupon_code']) {
            $validCoupon = $this->couponHelper->getValidCouponCode($orderData['coupon_code']);
            if ($validCoupon) {
                $quote->setCouponCode($validCoupon);
            }
        }
        if (isset($orderData[\Riki\Subscription\Api\Data\ApiProfileInterface::IS_MONTHLY_FEE_CONFIRMED])
            && $orderData[\Riki\Subscription\Api\Data\ApiProfileInterface::IS_MONTHLY_FEE_CONFIRMED]) {
            $quote->setData(\Riki\Subscription\Api\Data\ApiProfileInterface::IS_MONTHLY_FEE_CONFIRMED, 1);
        }
        $quote->collectTotals()->save();

        $earnPoint = $quote->getBonusPointAmount();

        // Create Order From Quote
        $this->_eventManager->dispatch(
            'checkout_submit_before',
            [
                'quote' => $quote,
                'profile' => $orderData
            ]
        );
        $courseModel = $this->helperCourse->loadCourse($orderData['course_id']);
        $subCourseType = $courseModel->getData('subscription_type');

        $deliveryDate = null;
        $emptyDDateItems = [];
        $timeSlotInfo = [];
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getDeliveryDate()) {
                $deliveryDate = $quoteItem->getDeliveryDate();
            } else {
                $emptyDDateItems[] = $quoteItem;
            }

            /** Sync delivery time for all items */
            if ($quoteItem->getDeliveryTimeslotId() && empty($timeSlotInfo)) {
                $timeSlotInfo['delivery_timeslot_id'] = $quoteItem->getData('delivery_timeslot_id');
                $timeSlotInfo['delivery_timeslot_from'] = $quoteItem->getData('delivery_timeslot_from');
                $timeSlotInfo['delivery_timeslot_to'] = $quoteItem->getData('delivery_timeslot_to');
                $timeSlotInfo['delivery_time'] = $quoteItem->getData('delivery_time');
            }
        }

        if ($deliveryDate) {
            foreach ($emptyDDateItems as $quoteItem) {
                $quoteItem->setDeliveryDate($deliveryDate);
                if (!empty($timeSlotInfo)) {
                    $quoteItem->setDeliveryTimeslotId($timeSlotInfo['delivery_timeslot_id']);
                    $quoteItem->setDeliveryTimeslotFrom($timeSlotInfo['delivery_timeslot_from']);
                    $quoteItem->setDeliveryTimeslotTo($timeSlotInfo['delivery_timeslot_to']);
                    $quoteItem->setDeliveryTime($timeSlotInfo['delivery_time']);
                }
            }
        }
        // check grand total = 0 and paygent , set payment back to free method (in case use all point)
        if ($quote->getGrandTotal() == 0
            && $paymentData['method'] == \Bluecom\Paygent\Model\Paygent::CODE
        ) {
            $paymentData['method'] = \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE;
            $quote->getPayment()->addData($paymentData);
        }

        $profileModel = $this->profileFactory->create()->load($profileId);
        $lastOrderTimeIsDelayPayment = intval($courseModel->getData('last_order_time_is_delay_payment'));
        $isProfileDelay = $this->isDelayPayment($courseModel->isDelayPayment(), $profileModel->getOrderTimes(), $lastOrderTimeIsDelayPayment);

        try {
            $order = $this->quoteManagement->submit($quote);
        } catch (\Riki\CatalogInventory\Exception\StockQtySubmitQuoteException $e) {
            if (!isset($orderData['stock_submit_exception']) && !$isSimulator) {
                $origParams[0]['stock_submit_exception'] = $orderData['profile_id'];
                return $this->createMageOrder(...$origParams);
            }
            throw $e;
        } // Retry create order from point failed
        catch (\Magento\Framework\Exception\PaymentException $e) {
            if ($this->_registry->registry('order_retry-'.$consumerId)) {
                $this->_loggerOrder->info(
                    'Retry create order using point times '.
                    $this->_registry->registry('order_retry-'.$consumerId).
                    'for customer'.$consumerId
                );
                return $this->createMageOrder(...$origParams);
            }

            throw $e;
        } catch (AssignationException $e) {
            if (!$isSimulator) {
                if ($this->_registry->registry('skus_cannot_assign')) {
                    $this->_registry->unregister('skus_cannot_assign');
                    $this->_loggerOrder->addError($e->getMessage());
                } elseif ($oosProductIds = $this->_registry->registry('ai_assign_oos_product_ids')) {
                    if (!isset($orderData['stock_submit_exception']) && !$isSimulator) {
                        $origParams[0]['stock_submit_exception'] = $orderData['profile_id'];
                        $origParams[0]['ai_assign_oos_product_ids'] = $oosProductIds;
                        return $this->createMageOrder(...$origParams);
                    }
                }
            }
            throw $e;
        } catch (LocalizedException $e) {
            if (!$isSimulator) {
                $this->_loggerOrder->addError($e->getMessage());
            }
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }

        if (!$isSimulator) {
            $order->setData('allowed_earned_point', $orderData['earn_point_on_order']);
            $order->setData(self::IS_PROFILE_GENERATED_ORDER_KEY, 1);
            /*linked new order to this profile*/
            $order->setData('subscription_profile_id', $orderData['profile_id']);
            $order->setData('subscription_order_time', ($orderData['order_times']+1));

            /*set default value to pass Fraud validation*/
            $order->setData('fraud_score', 50);
            $order->setData('fraud_status', 'accept');

            /*set payment method again for case grand total is 0*/
            if ($order->getGrandTotal() == 0) {
                $order->getPayment()->setMethod('free');
            }
            $isRemoveStockPoint = $this->_registry->registry(self::IS_REMOVE_STOCK_POINT);
            /*special value for stock point order*/
            if ($isStockPointOrder && $isRemoveStockPoint != true) {
                $stockPointData = $this->stockPointHelper->getStockPointByBucketId(
                    $orderData[self::PROFILE_STOCK_POINT_BUCKET_ID]
                );

                if (!$stockPointData) {
                    $this->_loggerOrder->addError(
                        'Cannot get stock point id for profile: ' .
                        $orderData['profile_id'] .
                        ' - bucket id: '.
                        $orderData[self::PROFILE_STOCK_POINT_BUCKET_ID] .
                        ' - delivery date: '.
                        $orderData['next_delivery_date']
                    );
                } else {
                    $deliveryBucketId = $this->stockPointHelper->
                    getDeliveryBucketIdByStockPointIdAndDeliveryDate(
                        $stockPointData->getStockPointId(),
                        $orderData['next_delivery_date'],
                        $orderData[self::PROFILE_STOCK_POINT_BUCKET_ID]
                    );

                    if (!$deliveryBucketId) {
                        $this->_loggerOrder->addError(
                            'Cannot get delivery bucket id for profile: ' .
                            $orderData['profile_id'] .
                            ' - bucket id: '.
                            $orderData[self::PROFILE_STOCK_POINT_BUCKET_ID] .
                            ' - delivery date: '.
                            $orderData['next_delivery_date']
                        );
                    } else {
                        $order->setData(
                            self::ORDER_STOCK_POINT_DELIVERY_BUCKET_ID,
                            $deliveryBucketId
                        );
                        $order->setData(
                            self::ORDER_IS_STOCK_POINT,
                            1
                        );
                    }

                    /*set stock point data for order- delivery type*/
                    $order->setData(
                        self::PROFILE_STOCK_POINT_DELIVERY_TYPE,
                        $orderData[self::PROFILE_STOCK_POINT_DELIVERY_TYPE]
                    );

                    /*set stock point data for order- delivery information*/
                    $order->setData(
                        self::PROFILE_STOCK_POINT_DELIVERY_INFORMATION,
                        $orderData[self::PROFILE_STOCK_POINT_DELIVERY_INFORMATION]
                    );
                }
            }

            //do not send mail when generate order subscription
            $order->setEmailSent(1);

            if (!empty($productOutOfStock)) {
                try {
                    $this->sendEmailStockOutOfStock($order, $productOutOfStock, $orderData, $customer->getEmail());
                } catch (Exception $e) {
                    if ($this->checkDeadlock($e)) {
                        throw $e;
                    } else {
                        $this->_loggerOrder->critical($e);
                    }
                }
            }
        } else {
            $order->setBonusPointAmount($earnPoint);
            $this->productHelper->setSkipSaleableCheck(false);
        }

        //unregister trading id
        $this->_registry->unregister('trading_id');

        $isFreeOrder = false;
        if ($order->getGrandTotal() ==0) {
            $isFreeOrder = true;
        }

        if ($courseModel->isDelayPayment() && !$isFreeOrder && $isProfileDelay) {
            $order->setData('riki_type', \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT);
        } elseif ($subCourseType == SubscriptionType::TYPE_HANPUKAI) {
            $order->setData('riki_type', \Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI);
        } elseif ($subCourseType == SubscriptionType::TYPE_SUBSCRIPTION ||
            $subCourseType == SubscriptionType::TYPE_MONTHLY_FEE
        ) {
            $order->setData('riki_type', \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION);
        } else {
            $order->setData('riki_type', \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION);
        }

        //check payment_paygent
        if (!$isProfileDelay) {
            $listAgents = [
                \Riki\DelayPayment\Helper\Data::PAYMENT_AGENT_NICOS,
                \Riki\DelayPayment\Helper\Data::PAYMENT_AGENT_JCB
            ];
            $paymentAgent = $order->getData('payment_agent');
            $matchPaymentAgent = (str_replace($listAgents, '', $paymentAgent) != $paymentAgent);
            if ($matchPaymentAgent) {
                $order->setData('payment_agent', str_replace('2', '', $paymentAgent));
            }
        }

        if ($isNewPaygent) {
            $order->setData('use_ivr', 1);
        }

        if (isset($orderData['order_channel'])) {
            $order->setData('order_channel', $orderData['order_channel']);
        }

        try {
            $order->save();
        } catch (\Exception $e) {
            $this->_loggerOrder->addError('Cannot set riki_type for order #' . $order->getIncrementId());
            throw $e;
        }

        if (!$isSimulator) {
            $this->orderManagement->notify($order->getEntityId());
        }

        if ($isSimulator) {
            $this->_eventManager->dispatch(
                'simulator_checkout_submit_all_after',
                ['order' => $order, 'quote' => $quote]
            );
        } else {
            $this->_eventManager->dispatch(
                'checkout_submit_all_after',
                ['order' => $order, 'quote' => $quote]
            );

            $this->_eventManager->dispatch(
                'subscription_order_place_after',
                [
                    'order' => $order,
                    'quote' => $quote,
                    'profile' => $orderData
                ]
            );
        }

        if (!$isSimulator) {
            try {
                $this->updateProfileAfterCreateOrder(
                    $orderData['profile_id'],
                    $order,
                    $productDisabledAndRemoved,
                    $productRemoved,
                    $spotProduct,
                    $isNewPaygent
                );
                /*Cal API to consumer DB to update delivery number*/
                if ($subCourseType != 'hanpukai' and $consumerId) {
                    $this->updateDeliveryNumberToConsumerDB($consumerId);
                }
            } catch (\Exception $e) {
                if ($this->checkDeadlock($e)) {
                    throw $e;
                } else {
                    $this->_loggerOrder->critical($e);
                    $this->_loggerOrder->addError(
                        'Cannot update profile after generate order #' . $order->getIncrementId()
                    );
                }
            }

            $this->_messageManager->addSuccess('Next Order #' . $order->getIncrementId() . ' created successfully');
        }

        $order->setData(self::IS_INCOMPLETE_GENERATE_PROFILE_ORDER, 0)
            ->save();

        $this->_registry->unregister('cron_store_id');

        $this->_registry->unregister('order_retry-'.$consumerId);

        if ($isSimulator) {
            $this->_registry->unregister('is_simulator');
        }

        return $order;
    }

    /**
     * @param array $items
     * @return array
     */
    private function sortProductsByDeliveryType(array $items)
    {
        $result = [];

        $coolNormalDmTypes = $this->deliveryDate->getDeliveryTypeHelper()->getCoolNormalDmTypes();

        $typeToItems = [];

        foreach ($items as $item) {
            try {
                $product = $this->_productRepository->getById($item['product_id']);
            } catch (\Exception $e) {
                $result[] = $item;
                continue;
            }

            $deliveryType = $product->getDeliveryType();

            if (in_array($deliveryType, $coolNormalDmTypes)) {
                if (!isset($typeToItems[$deliveryType])) {
                    $typeToItems[$deliveryType] = [];
                }

                $typeToItems[$deliveryType][] = $item;
            } else {
                $result[] = $item;
            }
        }

        foreach ($coolNormalDmTypes as $type) {
            if (isset($typeToItems[$type])) {
                foreach ($typeToItems[$type] as $item) {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $paymentMethod
     * @param bool|false $isSimulator
     * @param bool|false $isCronRequest
     * @return bool|string
     */
    protected function preparePaymentMethod(
        \Magento\Quote\Model\Quote $quote,
        $paymentMethod,
        $isSimulator = false,
        $isCronRequest = false
    ) {
        if (!$paymentMethod || $paymentMethod == Free::PAYMENT_METHOD_FREE_CODE) {
            if ($isSimulator) {
                $paymentMethod = Free::PAYMENT_METHOD_FREE_CODE;
            } else {
                $paymentMethod = false;
                $this->_loggerOrder->error('Subscription Order Generation - Payment Issue for Customer Id: ' . $quote->getCustomerId() . ' - Quote Id:' . $quote->getId());
            }
        } elseif ($paymentMethod == \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE) {
            $customer = $quote->getCustomer();
            $sShoshaBusinessCode = $customer->getCustomAttribute('shosha_business_code');
            if (!$sShoshaBusinessCode || !$sShoshaBusinessCode->getValue()) {
                $paymentMethod = false;
                $this->_loggerOrder->error(
                    'Subscription Order Generation - Payment Issue for Customer Id: ' .
                    $quote->getCustomerId() . ' - Quote Id: ' . $quote->getId() .
                    '  - Missing Shosha Code'
                );
            }
        }

        if (!$paymentMethod) {
            $this->_loggerOrder->addError('Payment method is invalid');
            if ($this->profilePaymentMethodErrorEmail->send()) {
                $this->_loggerOrder->addInfo('An email about payment method error was sent to customer');
                $msg = __('Can not generate order. An email about payment method error was sent to customer');
            } else {
                $this->_loggerOrder->addError('Unable to send email payment method is invalid to customer');
                $msg = __('Unable to send email to customer');
            }

            if (!$isSimulator || !$isCronRequest) {
                $this->_messageManager->addErrorMessage($msg);
            }
        }

        return $paymentMethod;
    }

    /**
     * @param $wrappingId
     * @param $quoteItem
     * @return bool
     */
    public function updateWrappingItem($wrappingId, $quoteItem)
    {
        if ($this->giftWrappingHelper->isGiftWrappingAvailableForItems()) {
            if ($wrappingId == -1) {
                $gCode = '';
                $sapCode = '';
                $gw = '';
                $gwBasePrice = '';
            } else {
                $wrapping = null;

                try {
                    $wrapping = $this->giftWrapping->get($wrappingId);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $wrapping = null;
                }

                if (!$wrapping) {
                    return false;
                }

                $gCode = $wrapping->getGiftCode();
                $sapCode = $wrapping->getSapCode();
                $gw = $wrapping->getGiftName();
                $gwBasePrice = $wrapping->getBasePrice();
            }

            $quoteItem->setGiftCode($gCode)
                ->setSapCode($sapCode)
                ->setGwBasePrice($gwBasePrice)
                ->setGiftWrapping($gw);
        }
        return true;
    }

    /**
     * Create Quote
     *
     * @param $arrProductInfo
     * @param bool $isSimulator
     *
     * @return mixed
     * @internal param bool $isSimulate
     */
    public function createMageQuote($arrProductInfo, $isSimulator = true)
    {
        $store = $this->_storeManager->getStore();
        $quote = $this->quote->create(); //Create object of quote
        $quote->setStore($store); //set store for which you create quote
        // if you have already buyer id then you can load customer directly
        if ($isSimulator) {
            $quote->setData('is_simulator', true);
        }
        $quote->setData(Constant::RIKI_COURSE_ID, $arrProductInfo[Constant::RIKI_COURSE_ID]);
        $quote->setData(Constant::RIKI_FREQUENCY_ID, $arrProductInfo[Constant::RIKI_FREQUENCY_ID]);
        if ($arrProductInfo['customer_id'] != null) {
            $customer= $this->customerRepository->getById($arrProductInfo['customer_id']);
            $quote->assignCustomer($customer);
        }
        $quote->setCurrency();

        $numOutOffStock = 0;
        $productOutOffStock = [];
        $numDisabledAndRemoved = 0;
        $productDisabledAndRemoved = [];
        $productRemoved = [];
        $productDisabled = [];

        //add items in quote
        foreach ($arrProductInfo['product_info'] as $item) {
            try {
                $product = $this->_productRepository->getById($item['product_id']);
                if (isset($item['parent_item_id']) && $item['parent_item_id'] != 0) {
                    $parentProduct = $this->_productRepository->getById($item['parent_item_id']);
                    if ($parentProduct && $parentProduct->getTypeId() == 'bundle') {
                        continue;
                    }
                }
                if ($product->getId() and $product->getStatus() == 1) {
                    $StockRegistry = $this->stockRegistry;
                    $stock = $StockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
                    if ($this->checkProductInStock($quote, $product, $item['qty'], $stock, $item) || $isSimulator) {
                        try {
                            $quoteItem = $quote->addProduct(
                                $product,
                                (int)($item['qty'])
                            );
                            if ($quoteItem instanceof \Magento\Quote\Model\Quote\Item) {
                                $quote->save();
                            }
                        } catch (\Exception $e) {
                            if (!$isSimulator) {
                                $this->_messageManager->addError(
                                    'Cannot add product' . $product->getName() . " into Cart "
                                );
                            }
                            $this->_loggerOrder->critical($e);
                        }
                    } else {
                        $numOutOffStock++;
                        $productOutOffStock[] = $item;
                        if (!$isSimulator) {
                            $this->_messageManager->addError("Product " . $product->getName() . " out of stock");
                        }
                    }
                } else {
                    $numDisabledAndRemoved++;
                    if (!$product->getId()) {
                        $productRemoved['removed'][] = $product;
                    } else {
                        $productDisabled['disabled'][] = $product;
                    }
                    $productDisabledAndRemoved[] = $product;
                }
            } catch (\Exception $e) {
                $productDisabledAndRemoved[] = $item['product_id'];
            }
        }

        //Set Address to quote
        $quote->getBillingAddress();
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->collectTotals();
        $this->_eventManager->dispatch('checkout_cart_save_after', ['cart' => $this]);
        return $quote;
    }

    public function setOtherInfoForQuote($quote, $orderData, $profileId)
    {
        /*Set riki_course_id and riki_frequency_id into quote*/
        if (isset($orderData['course_id'])) {
            $quote->setData('riki_course_id', $orderData['course_id']);
            /*$quote->save();*/
        }
        if (isset($orderData['frequency_interval']) and isset($orderData['frequency_unit'])) {
            $frequencyId = $this->getFrequencyIdFromProfile(
                $orderData['frequency_unit'],
                $orderData['frequency_interval']
            );
            if ($frequencyId) {
                $quote->setData('riki_frequency_id', $frequencyId);
                /*$quote->save();*/
            }
        }
        /*set N delivery to quote */
        if (isset($orderData['order_times'])) {
            if (isset($orderData['create_order_flag']) and $orderData['create_order_flag'] == 1) {
                $nDelivery = $orderData['order_times'];
            } else {
                $nDelivery = (int)($orderData['order_times'] + 1);
            }
            $quote->setData('n_delivery', $nDelivery);
        }
        $isMultipleAddress = $this->checkIsMultipleAddressCheckout($profileId);
        if ($isMultipleAddress) {
            $quote->setData('is_multiple_address', 1);
        }
    }

    /**
     * Get frequency_id from frequency_unit and frequency_interval
     *
     * @param $frequencyUnit
     * @param $frequencyInterval
     * @return null
     */
    public function getFrequencyIdFromProfile($frequencyUnit, $frequencyInterval)
    {
        if ($frequencyUnit and $frequencyInterval) {
            $frequencyFactory = $this->frequencyFactory->create()->getCollection();
            $frequencyFactory->addFieldToFilter('frequency_unit', $frequencyUnit);
            $frequencyFactory->addFieldToFilter('frequency_interval', $frequencyInterval);
            $frequencyFactory->setPageSize(1);
            if ($frequencyFactory->getFirstItem()  != null) {
                $frequency = $frequencyFactory->getFirstItem();
                return $frequency->getData('frequency_id');
            }
        }
        return null;
    }

    /**
     * @param CustomerInterface $customer
     * @return int
     */
    protected function getCustomerPointBalance($customer)
    {
        $customerCode = $customer->getCustomAttribute('consumer_db_id');
        if ($customerCode) {
            $customerCode = $customerCode->getValue();
        } else {
            return 0;
        }

        return $this->rewardManagement->getPointBalance($customerCode, false);
    }

    /**
     * Add reward point to Order
     *
     * @param Quote $quote
     * @param $arrPost
     * @param CustomerInterface $customer
     * @return self
     */
    public function addRewardPoint($quote, $arrPost, $customer)
    {
        $customerCode = $customer->getCustomAttribute('consumer_db_id');
        if ($customerCode) {
            $customerCode = $customerCode->getValue();
        } else {
            return $this;
        }
        // Not use point when can not set point
        $timesRetry = $this->loyaltyHelper->getDefaultRetryPoint();
        if ($this->_registry->registry('order_retry-'.$customerCode) &&
            $this->_registry->registry('order_retry-'.$customerCode) >= $timesRetry
        ) {
            return $this;
        }

        $totalPoint = $this->getCustomerPointBalance($customer);

        $customerSetting = $this->rewardManagement->getRewardUserSetting($customerCode);
        if (!$arrPost) {
            $arrPost['reward_user_setting'] = $customerSetting['use_point_type'];
            $arrPost['reward_user_redeem'] = $customerSetting['use_point_amount'];
        }
        $settingPoint = $arrPost['reward_user_setting'];

        switch ($settingPoint) {
            case 1:
                $arrPost['reward_user_redeem'] = $totalPoint;
                break;
            case 2:
                if ($totalPoint < $arrPost['reward_user_redeem']) {
                    $arrPost['reward_user_redeem'] = $totalPoint;
                }
                break;
            default:
                $arrPost['reward_user_redeem'] = 0;
        }

        return $this->saveRewardQuote($quote, $settingPoint, $arrPost['reward_user_redeem']);
    }

    /**
     * @param Quote $quote
     * @param $userSetting
     * @param $userRedeem
     * @return $this
     */
    protected function saveRewardQuote(Quote $quote, $userSetting, $userRedeem)
    {
        /** @var \Riki\Loyalty\Model\RewardQuote $rewardQuote */
        $rewardQuote = $this->rewardQuoteFactory->create();
        $rewardQuote->load($quote->getId(), 'quote_id');
        $rewardQuote->setData('quote_id', $quote->getId());
        $rewardQuote->setData('reward_user_setting', $userSetting);
        $rewardQuote->setData('reward_user_redeem', $userRedeem);
        try {
            $rewardQuote->save();
        } catch (\Exception $e) {
            $this->getLogger()->critical($e);
        }


        return $this;
    }

    /**
     * Update sub-profile info after create order successfully.
     *
     * @param $profileModel
     * @param $order \Magento\Sales\Model\Order
     */
    public function updateProfileAfterCreateOrder(
        $profileId,
        $order,
        $productDisabledAndRemoved,
        $productRemoved,
        $deleteSpot,
        $isNewPaygent
    ) {
        /*$profile_id will be profile_version_id if this profile has version is active
        $profileId = $this->profileRepository->getProfileIdVersion($profileId);*/
        $profileModel = $this->profileFactory->create()->load($profileId);
        /*Calculate sales_count in subscription profile*/
        /*1. Calculate sales_qty*/
        $productQty = 0;
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() ==  \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                continue;
            }
            $buyRequest = $item->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }
            if ($item->getData('prize_id')) {
                continue;
            }
            if ($item->getData('is_riki_machine') and $item->getData('price') == 0) {
                continue;
            }
            $productQty += $item->getQtyOrdered();
        }
        /*2. Calculate sales_amount === grand total*/
        $salesAmount = $order->getGrandTotal();
        if ($profileModel->getId()) {
            $profileData = [];
            $profileData['profile_id'] = $profileModel->getId();
            $profileData['order_times'] = $profileModel->getData('order_times') + 1;
            $profileData['sales_count'] = $profileModel->getData('sales_count') + $productQty;
            $profileData['sales_value_count'] = $profileModel->getData('sales_value_count') + $salesAmount;

            $this->_eventManager->dispatch('subscription_profile_create_order_after', [
                'profile' => $profileModel,
                'order' =>  $order
            ]);

            foreach ($profileData as $key => $value) {
                $profileModel->setData($key, $value);
            }

            $profileModel->save();

            $this->_eventManager->dispatch('subscription_profile_disengaged_after', [
                'profile' => $profileModel
            ]);

            // cache index cache for profile
            if ($this->profileIndexer) {
                $this->profileIndexer->removeCacheInvalid($profileModel->getId());
            }
        } else {
            $this->_logger->info("Cannot update information for subscription profile");
        }
        if (!empty($productDisabledAndRemoved) >0) {
            $this->sendEmailProductDisabledOrRemoved($productDisabledAndRemoved);
        }
        if (!empty($productRemoved) > 0) {
            $this->_loggerOrder->info("Process delete removed product ". json_encode($productRemoved));
            $this->deleteProductCartHasRemoved($productRemoved, $profileId);
        }
        if (!empty($deleteSpot) > 0) {
            $this->_loggerOrder->info("Process delete spot product ". json_encode($deleteSpot));
            $this->deleteProductCartHasRemoved($deleteSpot, $profileId);
        }
        return $profileModel;
    }

    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }

    public function checkIsMultipleAddressCheckout($profileId)
    {
        if ($profileId) {
            $profileModel = $this->profileFactory->create()->load($profileId);
            if ($profileModel->getId()) {
                $shippingAddress = [];
                $productCartModel = $this->productCartFactory->create()->getCollection();
                $productCartModel->addFieldToFilter('profile_id', $profileModel->getId());
                foreach ($productCartModel as $product) {
                    if (!in_array($product->getData('shipping_address_id'), $shippingAddress)) {
                        $shippingAddress[] = $product->getData('shipping_address_id');
                    }
                }
                if (count($shippingAddress) > 1) {
                    return true;
                }
                return false;
            }
            return false;
        }
    }

    public function addPromotionFreeGiftAms($quote)
    {

        $items = $quote->getAllItems();

        $addAutomatically = $this->scopeConfig->isSetFlag(
            'ampromo/general/auto_add',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($addAutomatically) {
            $toAdd  = $this->promoRegistry->getPromoItems();
            if (is_array($toAdd['_groups'])) {
                foreach ($toAdd['_groups'] as $ruleId => $freeGift) {
                    if (isset($freeGift['sku'])) {
                        foreach ($freeGift['sku'] as $sku) {
                            $product = $this->_productRepository->get($sku);
                            $availableQty = $this->checkAvailableQty($quote, $product, $freeGift['qty']);
                            if ($availableQty >= $freeGift['qty']) {
                                $toAdd[$sku] = [
                                    'sku' => $sku,
                                    'rule_id' => $ruleId,
                                    'qty' => $freeGift['qty'],
                                    'auto_add' => true
                                ];
                                break;
                            }
                        }
                    }
                }
            }
            unset($toAdd['_groups']);

            foreach ($items as $item) {
                $sku = $item->getProduct()->getData('sku');

                if (!isset($toAdd[$sku])) {
                    continue;
                }

                if ($this->promoItemHelper->isPromoItem($item)) {
                    $toAdd[$sku]['qty'] -= $item->getQty();
                }
            }

            $deleted = $this->promoRegistry->getDeletedItems();

            $this->_registry->unregister('ampromo_to_add');
            $collectorData = [];

            foreach ($toAdd as $sku => $item) {
                if ($item['qty'] > 0 && $item['auto_add'] && !isset($deleted[$sku])) {
                    $product = $this->_productFactory->create()->loadByAttribute('sku', $sku);

                    if (isset($collectorData[$product->getId()])) {
                        $collectorData[$product->getId()]['qty'] += $item['qty'];
                    } else {
                        $collectorData[$product->getId()] = [
                            'product' => $product,
                            'qty'     => $item['qty'],
                            'rule_id' => $item['rule_id']
                        ];
                    }
                }
            }

            $this->_registry->register('ampromo_to_add', $collectorData);
        }
    }

    public function checkAvailableQty($quote, $product, $qtyRequested)
    {
        if ($product->getTypeId() != \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
            return $qtyRequested;
        }

        $stockItem = $this->stockRegistry->getStockItem(
            $product->getId(),
            $product->getStore()->getWebsiteId()
        );

        if (!$stockItem->getManageStock()) {
            return $qtyRequested;
        }

        $qtyAdded = 0;
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductId() == $product->getId()) {
                $qtyAdded += $item->getQty();
            }
        }

        $qty = $stockItem->getQty() - $qtyAdded;

        return min($qty, $qtyRequested);
    }

    /**
     * Simulate order for calculate total next delivery hanpukai
     *
     * @param Order  $order
     * @param string $profileId
     * @param array  $arrData [
     * 'course_id' => $courseId, 'frequency_unit' => $frequencyUnit,
     * 'frequency_interval' => $frequencyInterval
     * ]
     *
     * @return bool|\Magento\Framework\Model\AbstractExtensibleModel|\Magento\Sales\Api\Data\OrderInterface|null|object
     */
    public function simulateMageOrder($order, $profileId, $arrData, $isSimulator = null)
    {
        try {
            $orderData = $this->simulateOrderData($order, $profileId, $arrData);
        } catch (\Exception $e) {
            $orderData = false;
        }
        if (!$orderData) {
            return false;
        }

        $customer= $this->customerRepository->getById($orderData['customer_id']);
        $store = $this->_storeManager->getStore($orderData['store_id']);
        if ($this->_registry->registry('cron_store_id')) {
            $this->_registry->unregister('cron_store_id');
        }
        $this->_storeManager->setCurrentStore($store->getId());
        $this->_registry->register('cron_store_id', $orderData['store_id']);
        $quote=$this->quote->create(); //Create object of quote
        $quote->setStore($store); //set store for which you create quote
        // if you have allready buyer id then you can load customer directly

        $quote->setCurrency();
        $error = 0;
        $result = [];
        if (!isset($orderData['items'])) {
            return 0;
        }

        $billingAddress = $this->quoteAddressFactory->create();
        $billingAddress = $billingAddress->importCustomerAddressData($orderData['billing_address']);
        $shippingAddress = $this->quoteAddressFactory->create();
        $shippingAddress = $shippingAddress->importCustomerAddressData($orderData['shipping_address']);

        $quote->assignCustomerWithAddressChange($customer, $billingAddress, $shippingAddress);

        //add items in quote
        foreach ($orderData['items'] as $item) {
            $product = $this->_productRepository->getById($item['product_id']);
            if ($product->getId()) {
                $StockRegistry = $this->stockRegistry;
                $stock =  $StockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
                if ($stock->getQty() >0 and $stock->getQty() >= $item['qty']) {
                    $this->_loggerOrder->info('Processing product: '.$product->getName());
                    try {
                        $quoteItem = $quote->addProduct(
                            $product,
                            (int)($item['qty'])
                        );
                        $quoteItem->setData('delivery_date', $item['delivery_date']);
                        $quoteItem->setData('delivery_time', $item['delivery_time']);
                        if (isset($item['gw_id'])) {
                            $quoteItem->setData('gw_id', $item['gw_id']);
                            $this->updateWrappingItem($item['gw_id'], $quoteItem);
                        }
                        if (isset($item['gift_message_id'])) {
                            $quoteItem->setData('gift_message_id', $item['gift_message_id']);
                        }
                        $quote->save();
                        $this->_createOrderRepository->saveQuoteItemAddress(
                            $quote,
                            $quoteItem,
                            $item['shipping_address_id']
                        );
                        $this->_loggerOrder->info('Product '.$product->getName().' has been added to the cart');
                    } catch (\Exception $e) {
                        $this->_loggerOrder->critical($e);
                    }
                } else {
                    $this->_loggerOrder->info("Product ".$product->getName()." out of stock");
                }
            }
        }
        if ($error) {
            return false;
        }
        if (empty($quote->getAllItems())) {
            return false;
        }

        // Collect Rates and Set Shipping & Payment Method

        $shippingAddress=$quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->setShippingMethod($orderData['shipping_method']); //shipping method
        $quote->setShippingAddress($shippingAddress);
        $quote->setInventoryProcessed($isSimulator);
        $quote->save(); //Now Save quote and your quote is ready
        // Set Sales Order Payment
        if (isset($orderData['payment_method']) &&
            $orderData['payment_method'] == \Bluecom\Paygent\Model\Paygent::CODE
        ) {
            if (array_key_exists('trading_id', $orderData) && $orderData['trading_id']) {
                //set trading id for automatically make authorize
                if ($this->_registry->registry('trading_id')) {
                    $this->_registry->unregister('trading_id');
                }
                $this->_registry->register('trading_id', $orderData['trading_id']);
            }
        }
        $paymentData['method'] = $orderData['payment_method'];
        $paymentData['checks'] = [
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_INTERNAL,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
        ];
        $quote->getPayment()->addData($paymentData);
        /*$this->addRewardPoint($quote,$customer->getId(),$arrPost,$customer);*/
        $this->setOtherInfoForQuoteHanpukai($quote, $orderData, $profileId);
        if ($this->_registry->registry('quote_admin')) {
            $this->_registry->unregister('quote_admin');
        }
        $this->_registry->register('quote_admin', $quote);
        // Collect Totals & Save Quote
        $quote->setIsAutoAddFirstItem(true);
        $quote->collectTotals();
        /*$this->addPromotionFreeGiftAms($quote);*/

        $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
        /** @var $checkoutSession \Magento\Checkout\Model\Session */
        $checkoutSession = $this->_objectManager->get(\Magento\Checkout\Model\Session::class);
        $checkoutSession->setAmpromoItems(['_groups' => []]);
        $this->resolveItems($quote);
        // Create Order From Quote
        try {
            $quote->setData('riki_course_id', null);
            $quote->setData('riki_frequency_id', null);
            $this->_eventManager->dispatch('checkout_submit_before', ['quote' => $quote]);
            $order = $this->quoteManagement->submit($quote);
            if (!$isSimulator) {
                try {
                    $order->save();
                } catch (\Exception $e) {
                    $this->_loggerOrder->critical($e);
                }
            }
            //unregister trading id
            $this->_registry->unregister('trading_id');
            $subCourseType = $this->_hanpukaiHelper->getSubscriptionCourseType($orderData['course_id']);
            if ($subCourseType == 'hanpukai') {
                $order->setData("riki_type", "HANPUKAI");
            } elseif ($subCourseType == 'subscription') {
                $order->setData("riki_type", "SUBSCRIPTION");
            }
            try {
                $order->save();
            } catch (\Exception $e) {
                $this->_loggerOrder->addError('Cannot set riki_type for order #'.$order->getIncrementId());
            }
            $quote->setIsActive(false)->save();
            if ($isSimulator) {
                $this->_eventManager->dispatch(
                    'simulator_checkout_submit_all_after',
                    ['order'=>$order, 'quote' => $quote]
                );
            } else {
                $this->_eventManager->dispatch('checkout_submit_all_after', ['order' => $order, 'quote' => $quote]);
            }
        } catch (\Exception $e) {
            $this->_loggerOrder->critical($e);
            return false;
        }

        if (!isset($order) || !$order->getRealOrderId()) {
            return false;
        }
        $this->_registry->unregister('cron_store_id');
        return $order;
    }

    /*
     * For hanpukai
     */
    public function simulateOrderData($order, $profileId, $arrData)
    {
        $orderData = [];
        if ($profileId) {
            return $this->getProfile($profileId);
        } else {
            $orderData['is_multiple_checkout'] = false;
            $orderData['customer_id'] = $order->getCustomerId();
            $orderData['store_id'] = $order->getStoreId();
            $orderData['payment_method'] = $order->getData("payment")->getData("method");
            if ($orderData['payment_method'] == \Bluecom\Paygent\Model\Paygent::CODE) {
                $orderData['trading_id'] = $order->getIncrementId();
            } else {
                $orderData['trading_id'] = '';
            }
            $orderData['shipping_method'] = $order->getData("shipping_method");
            $orderData['course_id'] =  $arrData['course_id'];
            $orderData['order_times'] = 1;
            $orderData['frequency_unit'] =  $arrData['frequency_unit'];
            $orderData['frequency_interval'] =  $arrData['frequency_interval'];
            $checkoutSession = $this->_objectManager->get(\Magento\Checkout\Model\Session::class);
            if ($this->state->getAreaCode() !== 'adminhtml') { // frontend
                $jsonDD = $checkoutSession->getDeliveryDateTmp();
                $arrDD = json_decode($jsonDD, true);
            } else {
                $arrParam = $this->_request->getParams();
                $arrInputName = [
                    'chilled',
                    'cosmetic',
                    'cold',
                    'CoolNormalDm'
                ];
                $arrDD = [];
                $k = 0;
                foreach ($arrInputName as $i => $name) {
                    if (! isset($arrParam['order']['next_delivery_date'][0][$name])) {
                        continue;
                    }

                    $arrDD[$k]['deliveryName'] = $name;
                    $arrDD[$k]['deliveryTime'] = $arrParam['order']['next_delivery_date'][0][$name];
                    $arrDD[$k]['nextDeliveryDate'] = $arrParam['order']['delivery_timeslot'][0][$name];
                    $k++;
                }
            }

            if (count($arrDD) == 1) {
                $strMinNextDD = $arrDD[0]['nextDeliveryDate'];
            } else {
                if ($arrDD == null || empty($arrDD)) {
                    $orderData['is_multiple_checkout'] = true;
                    $arrDD = $this->getDeliveryDateOfOrderItem($order);
                    $strMinNextDD = empty($arrDD)?null:min($arrDD);
                } else {
                    $strMinNextDD = $arrDD[0]['nextDeliveryDate'];
                    $countArrDD = count($arrDD);
                    for ($i = 1; $i < $countArrDD; $i++) {
                        $strMinNextDD = $arrDD[$i]['nextDeliveryDate'] < $strMinNextDD ?
                            $arrDD[$i]['nextDeliveryDate'] : $strMinNextDD;
                    }
                }
            }

            /*In case client don't choose anything*/
            if (! $strMinNextDD) {
                $intCurrentDate = strtotime($this->_tzHelper->date()->format("Y-m-d"));
                $strMinNextDD = $this->_calNextDeliveryDate(
                    $intCurrentDate,
                    $order['frequency_interval'],
                    $order['frequency_unit']
                );
            }

            $nextDeliveryDate = $strMinNextDD;
            $productCartData = [];
            $customerAddressDefault = 0;
            $subscriptionType = $this->_hanpukaiHelper->getSubscriptionCourseType($orderData['course_id']);
            $hanpukaiType = $this->_hanpukaiHelper->getHanpukaiType($orderData['course_id']);
            if ($subscriptionType == 'hanpukai' and $hanpukaiType == 'hsequence') {
                $productHanpukai = $this->_hanpukaiHelper->getHanpukaiProductDataPieceCase(
                    $hanpukaiType,
                    $orderData['course_id'],
                    (int)$orderData['order_times']+1
                );
                foreach ($productHanpukai as $productId => $value) {
                    $productModel = $this->_productFactory->create()->load($productId);
                    $productData = [
                        'product_id' => $productId,
                        'qty' => $value['qty'],
                        'price' => $productModel->getPrice(),
                        'gw_id' => null,
                        'billing_address_id' => $order->getBillingAddress()->getCustomerAddressId() == null ?
                            $customerAddressDefault : $order->getBillingAddress()->getCustomerAddressId(),
                        'shipping_address_id' => $order->getShippingAddress()->getCustomerAddressId() == null ?
                            $customerAddressDefault : $order->getShippingAddress()->getCustomerAddressId(),
                        'delivery_date' => $nextDeliveryDate,
                        'delivery_time' => null
                    ];
                    $productCartData[$productData['shipping_address_id']][] = $productData;
                }
            } else {
                foreach ($order->getAllItems() as $item) {
                    $productModel = $this->_productFactory->create()->load($item->getProductId());
                    $productData = [
                        'product_id' => $item->getProductId(),
                        'qty' => $item->getQtyOrdered(),
                        'price' => $productModel->getPrice(),
                        'gw_id' => null,
                        'billing_address_id' => $order->getBillingAddress()->getCustomerAddressId() == null ?
                            $customerAddressDefault : $order->getBillingAddress()->getCustomerAddressId(),
                        'shipping_address_id' => $order->getShippingAddress()->getCustomerAddressId() == null ?
                            $customerAddressDefault : $order->getShippingAddress()->getCustomerAddressId(),
                        'delivery_date' => $nextDeliveryDate,
                        'delivery_time' => null
                    ];
                    $productCartData[$productData['shipping_address_id']][] = $productData;
                }
            }

            if (count($productCartData) >= 1) {
                $shippingAddressId = key($productCartData);
                try {
                    $shippingAddress = $this->customerAddressRepository->getById($shippingAddressId);
                } catch (NoSuchEntityException $e) {
                    $shippingAddress = false;
                }

                if ($shippingAddress) {
                    $orderData['shipping_address'] = $shippingAddress;
                }

                try {
                    $billingAddress = $this->customerAddressRepository->getById(
                        $productCartData[$shippingAddressId][0]['billing_address_id']
                    );
                } catch (NoSuchEntityException $e) {
                    $billingAddress = false;
                }

                if ($billingAddress) {
                    $orderData['billing_address'] = $billingAddress;
                }
                $orderData['items'] = [];
                foreach ($productCartData as $product) {
                    foreach ($product as $item) {
                        $orderData['items'] = $this->_addItemToProductCart($orderData['items'], $item);
                    }
                }
            }

            return $orderData;
        }
    }

    /**
     * @param $order
     * @return array
     */
    public function getDeliveryDateOfOrderItem($order)
    {
        $dDate =[];
        foreach ($order->getAllItems() as $item) {
            if ($item->getData('delivery_date') != null || $item->getData('delivery_date') != '') {
                $dDate[] = $item->getData('delivery_date');
            }
        }
        return $dDate;
    }

    /**
     * @param $time
     * @param $frequencyInterval
     * @param $strFrequencyUnit
     * @return string
     */
    private function _calNextDeliveryDate($time, $frequencyInterval, $strFrequencyUnit)
    {

        $timestamp = strtotime($frequencyInterval . " " . $strFrequencyUnit, $time);

        $objDate  = new \DateTime();
        $objDate->setTimestamp($timestamp);

        return $objDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }

    /**
     * @param $quote
     * @return $this
     */
    public function resolveItems($quote)
    {

        $maxItemId = 0;
        $emptyIdItems = [];

        foreach ($quote->getAllItems() as $item) {
            if ($item->getId()) {
                if ($item->getId() > $maxItemId) {
                    $maxItemId = $item->getId();
                }
            } else {
                $emptyIdItems[] = $item;
            }
        }

        foreach ($emptyIdItems as $item) {
            $maxItemId++;
            $item->setId($maxItemId);
        }

        return $this;
    }

    /**
     * @param $quote
     * @param $orderData
     * @param $profileId
     */
    public function setOtherInfoForQuoteHanpukai($quote, $orderData, $profileId)
    {
        /*Set riki_course_id and riki_frequency_id into quote*/
        if (isset($orderData['course_id'])) {
            $quote->setData('riki_course_id', $orderData['course_id']);
            $quote->save();
        }
        if (isset($orderData['frequency_interval']) and isset($orderData['frequency_unit'])) {
            $frequencyFactory = $this->frequencyFactory->create()->getCollection()
                ->setPageSize(1);
            $frequencyFactory->addFieldToFilter('frequency_unit', $orderData['frequency_unit']);
            $frequencyFactory->addFieldToFilter('frequency_interval', $orderData['frequency_interval']);
            if ($frequencyFactory->getFirstItem()  != null) {
                $frequency = $frequencyFactory->getFirstItem();
                $quote->setData('riki_frequency_id', $frequency->getData('frequency_id'));
                $quote->save();
            }
        }
        /*set N delivery to quote */
        if (isset($orderData['order_times'])) {
            if (isset($orderData['create_order_flag']) and $orderData['create_order_flag'] == 1) {
                $nDelivery = $orderData['order_times'];
            } else {
                $nDelivery = (int)($orderData['order_times'] + 1);
            }
            $quote->setData('n_delivery', $nDelivery);
        }
        if ($profileId) {
            $isMultipleAddress = $this->checkIsMultipleAddressCheckout($profileId);
        } else {
            $isMultipleAddress = $this->checkIsMultipleAddressCheckout($orderData['is_multiple_checkout']);
        }
        if ($isMultipleAddress) {
            $quote->setData('is_multiple_address', 1);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $product
     * @param $qty
     * @param $stock
     * @param $item
     * @return bool
     */
    public function checkProductInStock(\Magento\Quote\Model\Quote $quote, $product, $qty, $stock, $item)
    {
        $availableQty = $this->leadTimeStockStatus->checkAvailableQty($quote, $product->getSku(), $qty);

        // check stock again then log if error occurs
        if  ($availableQty < $qty){
            $this->checkStockFromDB($quote, $product, $qty);
        }

        return $availableQty >= $qty;
    }

    /**
     * @param $order \Magento\Sales\Model\Order
     */
    public function sendEmailStockOutOfStock($order, $productOutOffStock, $profileData, $customerEmail)
    {
        /*Controlled by Email Marketing*/
        if ($order and $order->getId()) {
            $variableEmail = $this->orderHelper->getOrderVariables($order);
            $variableEmail['customer_last_name'] = $order->getCustomerLastname();
            $variableEmail['customer_first_name'] = $order->getCustomerFirstname();
            $variableEmail['address_name'] = $order->getShippingAddress()->getName();
            $variableEmail['subscription_course_name'] = isset($profileData['course_name']) ?
                $profileData['course_name'] : null;
            $variableEmail['order_times'] = isset($profileData['order_times']) ?
                $profileData['order_times'] : null;
        } else {
            $shippingAddress = $profileData['shipping_address'];
            $billingAddress = $profileData['billing_address'];
            $addressName = $shippingAddress->getCustomAttribute('riki_nickname')?
                $shippingAddress->getCustomAttribute('riki_nickname')->getValue():null;
            $variableEmail['customer_last_name'] = $billingAddress->getLastName();
            $variableEmail['customer_first_name'] = $billingAddress->getFirstName();
            $variableEmail['address_name'] = $addressName;
            $variableEmail['subscription_course_name'] = isset($profileData['course_name']) ?
                $profileData['course_name'] : null;
            $variableEmail['order_times'] = isset($profileData['order_times']) ? $profileData['order_times'] : null;
        }
        $productPerAddress = [];
        foreach ($productOutOffStock as $product) {
            $shippingAddress = $this->customerAddress->create()->load($product['shipping_address_id']);
            $productModel = $this->_productRepository->getById($product['product_id']);
            $product['name'] = $productModel->getName();
            $product['price'] = $productModel->getPrice();
            $product['sku'] = $productModel->getSku();
            $product['type'] = $productModel->getTypeId();
            $product['unit'] = $productModel->getData('case_display');
            if ((int) $product['gw_id'] > 0) {
                $gw = $this->giftWrapping->get($product['gw_id']);
                $product['wrapping_name'] = $gw->getWrappingId();
                $product['wrapping_price'] = $gw->getBasePrice();
            } else {
                $product['wrapping_name'] = null;
                $product['wrapping_price'] = null;
            }
            $productPerAddress[$shippingAddress->getData('riki_nickname')][] = $product;
        }
        $variableEmail['product'] = $this->orderHelper->processProductUnavailable($productPerAddress);

        $variableEmailAdmin['product'] = $this->orderHelper->processProductUnavailableAdmin($productPerAddress);

        if ($this->scopeConfig->getValue(self::CONFIG_SEND_EMAIL_OUT_OF_STOCK_ENABLE)) {
            $emailTemplate = $this->scopeConfig->getValue(
                self::CONFIG_SEND_EMAIL_OUT_OF_STOCK_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $emailSender = $this->scopeConfig->getValue(
                self::CONFIG_SEND_EMAIL_OUT_OF_STOCK_FROM,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $emailReceiver = $customerEmail;
            $this->emailOrderBuilder->sendEmailNotification(
                $variableEmail,
                $emailTemplate,
                'adminhtml',
                $emailSender,
                $emailReceiver
            );
        }

        /*send Email to admin team*/
        /* controlled by Email Marketing */
        /* Email: Subscription order stock unavailability (Business user) */
        if ($this->scopeConfig->getValue(self::CONFIG_SEND_EMAIL_OUT_OF_STOCK_ADMIN_ENABLE)) {
            $variableEmailAdmin['year'] = $this->_tzHelper->date()->format("Y");
            $variableEmailAdmin['month'] = $this->_tzHelper->date()->format("m");
            $variableEmailAdmin['day'] = $this->_tzHelper->date()->format("d");
            $variableEmailAdmin['hour'] = $this->_tzHelper->date()->format("H");
            $emailTemplateAdmin = $this->scopeConfig->getValue(
                self::CONFIG_SEND_EMAIL_OUT_OF_STOCK_ADMIN_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $emailSenderAdmin = $this->scopeConfig->getValue(
                self::CONFIG_SEND_EMAIL_OUT_OF_STOCK_ADMIN_FROM,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $emailReceiverAdmin = $this->scopeConfig->getValue(
                self::CONFIG_SEND_EMAIL_OUT_OF_STOCK_ADMIN_TO,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $this->emailOrderBuilder->sendEmailNotification(
                $variableEmailAdmin,
                $emailTemplateAdmin,
                'adminhtml',
                $emailSenderAdmin,
                $emailReceiverAdmin
            );
        }
    }

    /**
     * @param $arrProduct
     */
    public function sendEmailProductDisabledOrRemoved($arrProduct)
    {
        if ($this->getConfig(self::CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_ENABLE)) {
            $variableEmail = [];
            if (is_array($arrProduct)) {
                foreach ($arrProduct as $product) {
                    if ($product instanceof \Magento\Catalog\Model\Product) {
                        $variableEmail['product'][] = [
                            'name' =>$product->getName(),
                            'sku' => $product->getSku()
                        ];
                    } else {
                        $variableEmail['product'][] = [
                            'id' => $product,
                            'name' => null,
                            'sku' => null
                        ];
                    }
                }
            }
            $emailTemplate = $this->scopeConfig->getValue(
                self::CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $emailSender = $this->scopeConfig->getValue(
                self::CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_FROM,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $emailReceiver = $this->scopeConfig->getValue(
                self::CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_TO,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $this->emailOrderBuilder->sendEmailNotification(
                $variableEmail,
                $emailTemplate,
                'adminhtml',
                $emailSender,
                $emailReceiver
            );
        }
    }

    /**
     * @param $arrProduct
     * @param $profileId
     */
    public function deleteProductCartHasRemoved($arrProduct, $profileId)
    {
        if (is_array($arrProduct)) {
            $productCart =  $this->productCartFactory->create()->getCollection();
            $productCart->addFieldToFilter('product_id', $arrProduct);
            $productCart->addFieldToFilter('profile_id', $profileId);
            foreach ($productCart as $product) {
                try {
                    $product->delete();
                } catch (\Exception $e) {
                    $this->_loggerOrder->critical($e);
                }
            }
        }
    }

    /**
     * @param array $productOutOffStock
     * @return array
     */
    public function preparedProductVariableForOutOfStockMail(array $productOutOffStock)
    {
        $productPerAddress = [];
        foreach ($productOutOffStock as $product) {
            $productModel = false;

            try {
                $productModel = $this->_productRepository->getById($product['product_id']);
            } catch (\Exception $e) {
                $this->_loggerOrder->critical($e);
            }

            if ($productModel) {
                if ($this->canPutToOutOfStockMail($productModel)) {
                    $shippingAddress = $this->customerAddress->create()
                        ->load($product['shipping_address_id']);
                    $product['name'] = $productModel->getName();
                    if ($product['gw_id'] != null) {
                        $gw = $this->giftWrapping->get($product['gw_id']);
                        $product['wrapping_name']  = $gw->getWrappingId();
                        $product['wrapping_price']  = $gw->getBasePrice();
                    } else {
                        $product['wrapping_name']  = null;
                        $product['wrapping_price']  = null;
                    }
                    $productPerAddress[$shippingAddress->getData('riki_nickname')][] = $product;
                }
            }
        }

        return  $productPerAddress;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function canPutToOutOfStockMail(\Magento\Catalog\Api\Data\ProductInterface $product)
    {
        return true;
    }

    /**
     * Send delivery number to Consumer DB
     *
     * @param $consumerId
     */
    public function updateDeliveryNumberToConsumerDB($consumerId)
    {
        $customerSub = $this->rikiCustomerRepository->prepareInfoSubCustomer($consumerId);
        if (is_array($customerSub)) {
            $previousDeliveryNumber = 0;
            if (isset($customerSub['SUBSCRIPTION_CUMU_DELIVERY'])) {
                $previousDeliveryNumber = $customerSub['SUBSCRIPTION_CUMU_DELIVERY'];
            }
            $deliveryNumber = $previousDeliveryNumber + 1;

            $this->rikiCustomerRepository->setCustomerSubAPI($consumerId, [1132 => $deliveryNumber]);
        }
    }

    /**
     * Subscription profile payment method error
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Customer\Model\Customer $customer
     * @param int $profileId
     * @return boolean
     */
    public function sendMailPaymentError($quote, $customer, $profileId)
    {
        $quote->collectTotals();
        $profile = $this->profileRepository->get($profileId);
        $course = $this->helperCourse->loadCourse($profile->getCourseId());
        $emailVars = [];
        $emailVars['profile_id'] = $profileId;
        $emailVars['next_delivery_date'] = $profile->getNextDeliveryDate() ?
            $this->_tzHelper->formatDate($profile->getNextDeliveryDate(), \IntlDateFormatter::MEDIUM) : '';
        $emailVars['next_order_amount'] = $this->orderHelper->priceCurrency($quote->getBaseGrandTotal());
        $emailVars['customer_first_name'] = $customer->getFirstname();
        $emailVars['customer_last_name'] = $customer->getLastName();
        $emailVars['order_time'] = $profile->getOrderTimes();
        $emailVars['order_number_time'] = (int) $course->getData('hanpukai_maximum_order_times');
        $emailVars['subscription_course_name'] = $profile->getCourseName();
        $emailVars['subscription_profile_page'] = $this->_coreHelperData->_getUrl('subscriptions/profile');
        $billingInformation = __("Billing Title");
        $addressBilling = $quote->getBillingAddress();
        $address = $addressBilling->getRegion()
            . ', '. $addressBilling->getCity()
            . ', '.$addressBilling->getStreetLine(1);

        $firstName = $addressBilling->getFirstname();
        $lastName = $addressBilling->getLastname();
        $billingInformation.= \Riki\EmailMarketing\Helper\Order::NEWLINE .
            sprintf(__("Billing Name %s %s"), $lastName, $firstName);
        $billingInformation.= \Riki\EmailMarketing\Helper\Order::NEWLINE .
            __("Billing Postcode"). $addressBilling->getPostcode();
        $billingInformation.= \Riki\EmailMarketing\Helper\Order::NEWLINE . $address;
        $billingInformation.= \Riki\EmailMarketing\Helper\Order::NEWLINE . sprintf(
                __("Billing Telephone: %s"),
                $addressBilling->getTelephone()
            );
        $emailVars['billing_information'] = $billingInformation;
        $emailTemplate = $this->scopeConfig->getValue(
            self::XPATH_PAYMENT_ERROR_EMAIL_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $emailSender = $this->scopeConfig->getValue(
            self::XPATH_PAYMENT_ERROR_EMAIL_SENDER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $this->emailOrderBuilder->sendEmailNotification(
            $emailVars,
            $emailTemplate,
            'adminhtml',
            $emailSender,
            $customer->getEmail()
        );
    }

    /**
     * @return mixed
     */
    public function getMaxConsumerCreateOrder()
    {
        return $this->scopeConfig->getValue(
            'subscriptioncourse/createorder/number_consumer_createorder',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * Send email notify to admin if free machine is not attached
     *
     * @param $consumerDbId
     * @param $variable
     * @param $type
     */
    public function sendEmailNotifyToAdmin($consumerDbId, $variable, $type)
    {
        if ($type == 'oos') {
            $emailTemplateVariables = [
                'consumer_db_id' => $consumerDbId,
                'machine_type_code' => $variable['machine_type_code'],
                'sku' => isset($variable['sku_oos'])?$variable['sku_oos']:null
            ];
            $emailTemplate = $this->scopeConfig->getValue(self::CONFIG_FREE_MACHINE_EMAIL_TEMPLATE_OOS);
        } else {
            if ($type == 'ambassador') {
                $emailTemplateVariables = [
                    'consumer_db_id' => $consumerDbId,
                    'machine_type_code' => $variable
                ];
                $emailTemplate = $this->scopeConfig->getValue(self::CONFIG_FREE_MACHINE_EMAIL_TEMPLATE_AMBASSADOR);
            } else {
                $emailTemplateVariables = [
                    'consumer_db_id' => $consumerDbId,
                    'machine_type_code' => $variable
                ];
                $emailTemplate = $this->scopeConfig->getValue(self::CONFIG_FREE_MACHINE_EMAIL_TEMPLATE_AMBASSADOR_SUB);
            }
        }
        $from = $this->scopeConfig->getValue(self::CONFIG_FREE_MACHINE_EMAIL_SENDER);
        $to = $this->scopeConfig->getValue(self::CONFIG_FREE_MACHINE_EMAIL_RECEIVER);
        if ($emailTemplate && $to) {
            $this->emailOrderBuilder->sendEmailNotification(
                $emailTemplateVariables,
                $emailTemplate,
                'adminhtml',
                $from,
                $to
            );
        }
    }

    /**
     * @param $e
     * @return bool
     */
    public function checkDeadlock($e)
    {
        if (preg_match('#SQLSTATE\[HY000\]: [^:]+: 1205[^\d]#', $e->getMessage()) ||
            preg_match('#SQLSTATE\[40001\]: [^:]+: 1213[^\d]#', $e->getMessage())
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $profileModel \Riki\Subscription\Model\Profile\Profile
     */
    public function resetCouponCode($profileModel)
    {
        $profileId = $profileModel->getProfileId();
        if ($profileId == $profileModel->getOrigData('profile_id')) {
            $profileModel->setData('coupon_code', null);
        } else {
            $profileModel = $this->profileFactory->create()->load($profileId, null, true);
            if ($profileModel->getId()) {
                $profileModel->setData('coupon_code', null);
            }
        }
        try {
            $profileModel->save();
        } catch (\Exception $e) {
            $this->_loggerOrder->addError('Profile '.$profileId.' cannot reset coupon code');
            $this->_loggerOrder->addCritical($e);
        }
    }

    /**
     * @param $orderData
     * @return bool|\Magento\Framework\Model\AbstractExtensibleModel|\Magento\Sales\Api\Data\OrderInterface|null|object
     * @throws \Exception
     */
    public function createMageSimulateQuote($orderData)
    {
        $profileId = $orderData['profile_id'];

        if ($this->customer && isset($this->customer[$orderData['customer_id']])) {
            $customer = $this->customer[$orderData['customer_id']];
        } else {
            try {
                $customer = $this->customerRepository->getById($orderData['customer_id']);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
            $this->customer[$orderData['customer_id']] = $customer;
        }

        $store = $this->_storeManager->getStore($orderData['store_id']);
        $this->_storeManager->setCurrentStore($store->getId());

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote=$this->quote->create()->setIsActive(0); //Create object of quote
        $quote->setStore($store); //set store for which you create quote
        // if you have allready buyer id then you can load customer directly
        $quote->setData(self::IS_PROFILE_GENERATED_ORDER_KEY, 1);
        $quote->setData(self::IS_SIMULATOR_PROFILE_NAME, true);

        $quote->setCurrency();
        $quote->setData(self::SUBSCRIPTION_PROFILE_ID_FIELD_NAME, $profileId);
        /*flag to check this profile is used stock point or not*/
        $isStockPointProfile = isset($orderData[self::IS_STOCK_POINT_PROFILE]) &&
            $orderData[self::IS_STOCK_POINT_PROFILE];

        /*flag to check this quote is stock point or not*/
        $quote->setData(self::IS_STOCK_POINT_PROFILE, $isStockPointProfile);
        /*flag to check new order is stock point order*/
        $quote->setData(self::IS_STOCK_POINT_ORDER, $isStockPointProfile);

        $quote->setData('is_monthly_fee', $orderData['is_monthly_fee']);

        if (isset($orderData['order_channel'])) {
            $quote->setData('order_channel', $orderData['order_channel']);
        }

        if (!isset($orderData['items'])) {
            return [];
        }

        $billingAddress = $this->quoteAddressFactory->create();
        $billingAddress = $billingAddress->importCustomerAddressData($orderData['billing_address']);
        $shippingAddress = $this->quoteAddressFactory->create();
        $shippingAddress = $shippingAddress->importCustomerAddressData($orderData['shipping_address']);

        $quote->assignCustomerWithAddressChange($customer, $billingAddress, $shippingAddress);

        //add items in quote
        $this->productHelper->setSkipSaleableCheck(true);

        $products = $this->getProductListFromSimulatedItems($orderData['items'], $orderData['store_id']);


        $quote->setData(Delitype::DELIVERY_TYPE_FLAG, true);
        foreach ($orderData['items'] as $item) {
            try {
                if (isset($products[$item['product_id']])) {
                    /** @var \Magento\Catalog\Model\Product $product */
                    $product = $products[$item['product_id']];

                    /*Begin check skip seasonal product*/
                    $allowSeasonalSkip = $product->getData('allow_seasonal_skip');
                    $seasonalSkipOptional = $product->getData('seasonal_skip_optional');
                    $allowSkipFrom = $product->getData('allow_skip_from');
                    $allowSkipTo = $product->getData('allow_skip_to');
                    if ($item['is_skip_seasonal']) {
                        if (strtotime($item['skip_from']) <= strtotime($item['delivery_date']) &&
                            strtotime($item['delivery_date']) <= strtotime($item['skip_to'])
                        ) {
                            continue;
                        }
                    } else {
                        if ($item['is_skip_seasonal'] !== '0' and !$seasonalSkipOptional) {
                            if ($allowSeasonalSkip &&
                                strtotime($allowSkipFrom) <= strtotime($item['delivery_date']) &&
                                strtotime($item['delivery_date']) <= strtotime($allowSkipTo)
                            ) {
                                continue;
                            }
                        }
                    }
                    /*End check skip seasonal product*/

                    try {
                        $product->setData('is_subscription_product', 1);
                        $product->setData('is_spot', isset($item['is_spot'])? $item['is_spot'] : false);
                        $product->setData('is_addition', isset($item['is_addition'])? $item['is_addition'] : false);

                        $quoteItem = $quote->addProduct(
                            $product,
                            (int)($item['qty'])
                        );
                        if ($quoteItem instanceof \Magento\Quote\Model\Quote\Item) {
                            $quoteItem->setData('delivery_date', $item['delivery_date']);
                            $quoteItem->setData('delivery_time', $item['delivery_time']);
                            if (isset($item['unit_qty'])) {
                                $quoteItem->setData('unit_qty', $item['unit_qty']);
                            }

                            if (isset($item['unit_case'])) {
                                $quoteItem->setData('unit_case', $item['unit_case']);
                            }

                            if (isset($item['gw_id'])) {
                                $quoteItem->setData('gw_id', $item['gw_id']);
                                $this->updateWrappingItem($item['gw_id'], $quoteItem);
                            }
                            if (isset($item['gift_message_id'])) {
                                $quoteItem->setData('gift_message_id', $item['gift_message_id']);
                            }
                            if (isset($item['stock_point_discount_rate'])) {
                                $quoteItem->setData('stock_point_discount_rate', $item['stock_point_discount_rate']);
                            }
                            /**
                             * Although address has been saved, isObjectNew flag still is true.
                             * We set isObjectNew flag to false so rule validator's processRule
                             * function can use the cache.
                             */
                            $quote->getShippingAddress()->isObjectNew(false);
                        }
                    } catch (LocalizedException $e) {
                        if ($this->checkDeadlock($e)) {
                            throw $e;
                        } else {
                            $this->_logger->critical($e);
                        }
                    } catch (\Exception $e) {
                        if ($this->checkDeadlock($e)) {
                            throw $e;
                        } else {
                            $this->_logger->critical($e);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }

        $this->deliveryTypeHelper->setDeliveryTypeForQuote($quote);
        $quote->unsetData(Delitype::DELIVERY_TYPE_FLAG);
        // Collect Rates and Set Shipping & Payment Method
        $shippingAddress=$quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->setShippingMethod($orderData['shipping_method']); //shipping method
        $quote->setShippingAddress($shippingAddress);

        // Set Sales Order Payment
        //TODO check is newPaygent;
        if (isset($orderData['payment_method']) &&
            $orderData['payment_method'] == \Bluecom\Paygent\Model\Paygent::CODE
        ) {
            if (!$orderData['trading_id']) {
                if ($this->_registry->registry('trading_id') !== null) {
                    $this->_registry->unregister('trading_id');
                }
                $this->_registry->register('trading_id', self::PAYGENT_EMPTY_TRADING);
            } else {
                //set trading id for automatically make authorize
                if ($this->_registry->registry('trading_id') !== null) {
                    $this->_registry->unregister('trading_id');
                }
                $this->_registry->register('trading_id', $orderData['trading_id']);
            }
        }
        //implement task #RIKI-5209, send notification email when generate order with payment method is null
        if ($this->_registry->registry('quote_admin')) {
            $this->_registry->unregister('quote_admin');
        }
        $orderData['payment_method'] = $this->preparePaymentMethod(
            $quote,
            $orderData['payment_method'],
            true,
            isset($orderData['is_generate_by_cron'])
        );

        if (!$orderData['payment_method']) {
            return false;
        }

        $paymentData['method'] = $orderData['payment_method'];
        $paymentData['checks'] = [
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_INTERNAL,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
        ];
        $quote->getPayment()->addData($paymentData);
        $this->setOtherInfoForQuote($quote, $orderData, $orderData['profile_id']);
        // Collect Totals & Save Quote
        $quote->setIsAutoAddFirstItem(true);

        $quote->setData('allowed_earned_point', $orderData['earn_point_on_order']);
        /*Apply coupon code*/
        if (isset($orderData['coupon_code']) and $orderData['coupon_code']) {
            $validCoupon = $this->couponHelper->getValidCouponCode($orderData['coupon_code']);
            if ($validCoupon) {
                $quote->setCouponCode($validCoupon);
            }
        }

        $this->addRewardPoint($quote, null, $customer, true);
        $quote->collectTotals()->save();

        $earnPoint = $quote->getBonusPointAmount();

        $deliveryDate = null;
        $emptyDDateItems = [];

        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getDeliveryDate()) {
                $deliveryDate = $quoteItem->getDeliveryDate();
            } else {
                $emptyDDateItems[] = $quoteItem;
            }
        }

        if ($deliveryDate) {
            foreach ($emptyDDateItems as $quoteItem) {
                $quoteItem->setDeliveryDate($deliveryDate);
            }
        }
        // check grand total = 0 and paygent , set payment back to free method (in case use all point)
        if ($quote->getGrandTotal() == 0
            && $paymentData['method'] == \Bluecom\Paygent\Model\Paygent::CODE
        ) {
            $paymentData['method'] = \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE;
            $quote->getPayment()->addData($paymentData);
        }
        // Create Order From Quote
        $this->_eventManager->dispatch('checkout_submit_before', ['quote' => $quote]);

        try {
            $order = $this->quoteManagement->submit($quote);
            $this->productHelper->setSkipSaleableCheck(true);
        } catch (\Exception $e) {
            throw $e;
        }
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            $order->setBonusPointAmount($earnPoint);
            return $order;
        }
        return false;
    }

    /**
     * @param array $items
     * @param null $storeId
     * @return array
     */
    private function getProductListFromSimulatedItems(array $items, $storeId = null)
    {
        $result = [];

        $productIds = array_map(function ($item) {
            if (!empty($item['parent_item_id'])) {
                return 0;
            }

            return $item['product_id'];
        }, $items);

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $this->productCollectionFactory->create();

        $productCollection->addAttributeToSelect($this->quoteConfig->getProductAttributes())
            ->addAttributeToFilter('status', 1)
            ->addIdFilter($productIds)
            ->addStoreFilter($storeId);

        foreach ($productCollection as $product) {
            $result[$product->getId()] = $product;
        }

        return $result;
    }

    /**
     * Get stock point address and convert to quote address object
     *
     * @param $profileId
     * @return bool|\Magento\Quote\Model\Quote\Address
     */
    public function getStockPointAddressByProfileId($profileId)
    {
        $stockPoint = $this->stockPointHelper->getStockPointByProfileId($profileId);

        if (!$stockPoint) {
            return false;
        }

        $addressObject = $this->quoteAddressFactory->create();
        $addressObject->setData($stockPoint->getData());

        $addressObject->setData(
            'city',
            \Riki\Quote\Model\Quote\Address::ADDRESS_DEFAULT_CITY
        );

        $addressObject->setData(
            'country_id',
            \Riki\Quote\Model\Quote\Address::ADDRESS_DEFAULT_COUNTRY
        );

        $addressObject->setData('firstnamekana', $stockPoint->getData('firstname_kana'));
        $addressObject->setData('lastnamekana', $stockPoint->getData('lastname_kana'));
        $addressObject->setData(
            'riki_nickname',
            $stockPoint->getLastname() .  $stockPoint->getFirstname()
        );

        $addressObject->setCustomAttribute('firstnamekana', $stockPoint->getData('firstname_kana'));
        $addressObject->setCustomAttribute('lastnamekana', $stockPoint->getData('lastname_kana'));
        $addressObject->setCustomAttribute(
            'riki_nickname',
            $stockPoint->getLastname() .  $stockPoint->getFirstname()
        );

        return $addressObject;
    }

    /**
     * add item to quote before generate order
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param $orderData
     * @param $isSimulator
     */
    protected function addItemToQuoteBeforeGenerateOrder(
        \Magento\Quote\Model\Quote $quote,
        $orderData,
        $isSimulator
    ) {
        /*list out of stock items*/
        $productOutOfStock = [];
        /*list items are disabled or need to be removed*/
        $productDisabledAndRemoved = [];
        /*list items need to be removed*/
        $productRemoved = [];
        /*list SPOT item - need to be remove after generated order*/
        $spotProduct = [];

        $orderData['items'] = $this->sortProductsByDeliveryType($orderData['items']);
        foreach ($orderData['items'] as $item) {
            if (!empty($item['is_spot'])) {
                $this->_loggerOrder->info('Product ' . $item['product_id']. ' is SPOT product & will be remove after order generate');
                $spotProduct[] = $item['product_id'];
            }
            try {
                $product = $this->_productRepository->getById($item['product_id']);
                if (!empty($item['parent_item_id'])) {
                    $parentProduct = $this->_productRepository->getById($item['parent_item_id']);
                    if ($parentProduct
                        && $parentProduct->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE
                    ) {
                        continue;
                    }
                }

                /*product is disabled*/
                if ($product->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                    $this->_loggerOrder->info('Product ' . $item['product_id']. ' is no longer available and will be remove after order generate.');
                    $productDisabledAndRemoved[] = $product;
                    continue;
                }


                $this->removeNegativeStockFromProduct($product, $orderData);

                $this->stockRegistryStorage = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\CatalogInventory\Model\StockRegistryStorage::class)->removeStockItem($product->getId());

                $stock = $this->stockRegistry->getStockItem(
                    $product->getId(),
                    $product->getStore()->getWebsiteId()
                );

                $this->_registry->unregister(StockRegistryProvider::UNREGISTER_STOCK_ITEM);
                $this->_registry->register(StockRegistryProvider::UNREGISTER_STOCK_ITEM, $product->getId());
                $oosFlag = isset($orderData['ai_assign_oos_product_ids']) &&
                    in_array($product->getId(), $orderData['ai_assign_oos_product_ids']);

                if ($isSimulator
                    || (!$oosFlag && $this->checkProductInStock($quote, $product, $item['qty'], $stock, $item))
                ) {
                    /*for the case this product is skipped for this season*/
                    if ($this->isProductSkipped($product, $item)) {
                        $this->_loggerOrder->info('Product ' . $product->getName(). ' is skipped for this season.');
                        continue;
                    }

                    $this->_loggerOrder->info('Product ' . $product->getName());

                    try {
                        /**
                         * flag to check product is come from subscription profile
                         *      will pass some case that no need to apply for subscription
                         */
                        $product->setData('is_subscription_product', 1);
                        $product->setData('is_spot', isset($item['is_spot'])? $item['is_spot'] : false);
                        $product->setData('is_addition', isset($item['is_addition'])? $item['is_addition'] : false);


                        $quoteItem = $quote->addProduct(
                            $product,
                            (int)($item['qty'])
                        );

                        if (is_string($quoteItem)) {
                            throw new LocalizedException(__($quoteItem));
                        }

                        if ($orderData[self::IS_STOCK_POINT_PROFILE]) {
                            $prepareDeliveryDate = $this->prepareDeliveryDateForStockPoint($item);
                            $quoteItem->setData('delivery_date', $prepareDeliveryDate['delivery_date']);
                            $quoteItem->setData('delivery_timeslot_id', $prepareDeliveryDate['delivery_time_id']);
                            $quoteItem->setData('delivery_time', $prepareDeliveryDate['original_delivery_time']);
                            $quoteItem->setData('delivery_timeslot_from',
                                $prepareDeliveryDate['original_delivery_time_from']);
                            $quoteItem->setData('delivery_timeslot_to',
                                $prepareDeliveryDate['original_delivery_time_to']);
                        } else {
                            $quoteItem->setData('delivery_date', $item['delivery_date']);
                            $quoteItem->setData('delivery_timeslot_id', $item['delivery_time_id']);
                            $quoteItem->setData('delivery_time', $item['delivery_time']);
                            $quoteItem->setData('delivery_timeslot_from', $item['delivery_time_from']);
                            $quoteItem->setData('delivery_timeslot_to', $item['delivery_time_to']);
                        }
                        $quoteItem->setData('visible_user_account', 1);

                        if (isset($item['unit_qty'])) {
                            $quoteItem->setData('unit_qty', $item['unit_qty']);
                        }

                        if (isset($item['unit_case'])) {
                            $quoteItem->setData('unit_case', $item['unit_case']);
                        }

                        if (isset($item['gw_id'])) {
                            $quoteItem->setData('gw_id', $item['gw_id'] > 0? $item['gw_id']: null);
                            $this->updateWrappingItem($item['gw_id'], $quoteItem);
                        }
                        if (isset($item['gift_message_id'])) {
                            $quoteItem->setData('gift_message_id', $item['gift_message_id']);
                        }

                        // Add data variable fee to additional_data of quote item
                        // and use for add variable fee to product price when collect total
                        if (isset($item['is_variable_fee']) && $item['is_variable_fee'] &&
                            isset($item['variable_fee'])
                        ) {
                            try {
                                $additionalData = json_decode(
                                    $quoteItem->getData('additional_data') ?: '{}',
                                    true
                                );
                                $additionalData['is_variable_fee'] = $item['is_variable_fee'];
                                $additionalData['variable_fee'] = $item['variable_fee'];
                                $quoteItem->setData('additional_data', json_encode($additionalData));
                            } catch (\Zend_Json_Exception $e) {
                                $this->_loggerOrder->info((string)$quoteItem->getData('additional_data'));
                            }
                        }

                        $quote->save();
                        $this->_createOrderRepository->saveQuoteItemAddress(
                            $quote,
                            $quoteItem,
                            $item['shipping_address_id']
                        );

                        $messageAddedProduct = 'Product ' . $product->getName() . ' has been added to the cart';
                        // Write log product added to cart with variable fee.
                        if (isset($item['is_variable_fee']) && $item['is_variable_fee'] &&
                            isset($item['variable_fee'])
                        ) {
                            $messageAddedProduct .= ' with variable fee ' . $item['variable_fee'];
                        }
                        $this->_loggerOrder->info($messageAddedProduct);
                    } catch (LocalizedException $e) {
                        if (!$isSimulator) {
                            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
                            foreach ($quote->getAllItems() as $quoteItem) {
                                if ($quoteItem->getParentItemId()) {
                                    continue;
                                }

                                if (!$this->isValidQuoteItem($quoteItem)) {
                                    $quoteItemError = $quoteItem;
                                    $quoteItem->isDeleted(true);
                                    $quote->deleteItem($quoteItem);
                                    if ($quoteItemError->getProductId() == $product->getId()) {
                                        $productOutOfStock[] = $item;
                                        $this->_messageManager->addError(
                                            "Product " . $product->getName() . " out of stock"
                                        );
                                        $this->_loggerOrder->info(
                                            "There was an error when process Product ID " . $product->getId() . " "
                                        );
                                        $this->_loggerOrder->info(
                                            "Move Error Product " . $product->getName() . " to out of stock"
                                        );
                                        $this->_loggerOrder->info("Product " . $product->getName() . " stock info: ". json_encode($stock->getData()));
                                        $this->_eventManager->dispatch(
                                            \Riki\AdvancedInventory\Observer\OosCapture::EVENT,
                                            [
                                                'quote' => $quote,
                                                'product' => $product,
                                                'qty' => $item['qty'],
                                                'unit_qty' => isset($item['unit_qty']) ? $item['unit_qty'] : null,
                                                'unit_case' => isset($item['unit_case']) ? $item['unit_case'] : null,
                                                'original_delivery_date' => isset($item['delivery_date']) ?
                                                    $item['delivery_date'] : null,
                                                'gw_id'    => isset($item['gw_id']) ? $item['gw_id'] : null,
                                            ]
                                        );
                                    }
                                    // Remove from quote->getErrors()
                                    $this->_removeErrorsFromQuoteAndItem($quoteItem, \Magento\CatalogInventory\Helper\Data::ERROR_QTY);
                                    //
                                }
                            }
                            if (isset($quoteItemError)) {
                                $quote->save();
                            } else {
                                $this->exceptionProcess($e);
                            }
                        }
                    } catch (\Exception $e) {
                        $this->exceptionProcess($e);
                    }
                } else {
                    $productOutOfStock[] = $item;
                    if (!$isSimulator) {
                        $this->_messageManager->addError("Product " . $product->getName() . " out of stock");
                    }
                    $this->_loggerOrder->info("Product " . $product->getName() . " out of stock");
                    $this->_loggerOrder->info("Product " . $product->getName() . " stock info: ". json_encode($stock->getData()));

                    $courseModel = $this->helperCourse->loadCourse($orderData['course_id']);
                    $isDelayPayment = $courseModel->getIsDelayPayment();
                    $subscriptionType = $courseModel->getSubscriptionType();

                    $profileModel = $this->helperCourse->loadProfile($orderData['profile_id']);
                    $orderTimes = $profileModel->getOrderTimes();
                    $lastOrderTimeIsDelayPayment = intval($courseModel->getData('last_order_time_is_delay_payment'));
                    $isProfileDelay = $this->isDelayPayment($isDelayPayment, $orderTimes, $lastOrderTimeIsDelayPayment);

                    $this->_eventManager->dispatch(\Riki\AdvancedInventory\Observer\OosCapture::EVENT, [
                        'quote' => $quote,
                        'product' => $product,
                        'qty' => $item['qty'],
                        'unit_qty' => isset($item['unit_qty'])? $item['unit_qty'] : null,
                        'unit_case' => isset($item['unit_case'])? $item['unit_case'] : null,
                        'original_delivery_date' => isset($item['delivery_date']) ?
                            $item['delivery_date'] : null,
                        'is_delay_payment' => intval($isProfileDelay),
                        'subscription_type' => $subscriptionType
                    ]);
                }
            } catch (\Exception $e) {
                $this->exceptionProcess($e);
                $this->_loggerOrder->info('Product ' . $item['product_id']. ' encounter an exeception & will be remove after order generate');
                $productRemoved[] = $item['product_id'];
                $productDisabledAndRemoved[] = $item['product_id'];
            }
        }
        /*register data for main process*/
        /*list of out of stock product*/
        $this->_registry->unregister('generate_order_out_of_stock_product');
        $this->_registry->register('generate_order_out_of_stock_product', $productOutOfStock);

        /*list of products need to be removed out of profile*/
        $this->_registry->unregister('generate_order_product_removed');
        $this->_registry->register('generate_order_product_removed', $productRemoved);

        /*list of products are disabled or need to be removed out of profile*/
        $this->_registry->unregister('generate_order_product_disabled_and_removed');
        $this->_registry->register('generate_order_product_disabled_and_removed', $productDisabledAndRemoved);

        /*list of spot product need to be removed out of profile after generated order success*/
        $this->_registry->unregister('generate_order_spot_product');
        $this->_registry->register('generate_order_spot_product', $spotProduct);
    }

    /**
     * Removes error statuses from quote and item, set by this observer
     *
     * @param Item $item
     * @param int $code
     * @return void
     */
    protected function _removeErrorsFromQuoteAndItem($item, $code)
    {
        if ($item->getHasError()) {
            $params = ['origin' => 'cataloginventory', 'code' => $code];
            $item->removeErrorInfosByParams($params);
        }

        $quote = $item->getQuote();
        if ($quote->getHasError()) {
            $quoteItems = $quote->getItemsCollection();
            $canRemoveErrorFromQuote = true;
            foreach ($quoteItems as $quoteItem) {
                if ($quoteItem->getItemId() == $item->getItemId()) {
                    continue;
                }

                $errorInfos = $quoteItem->getErrorInfos();
                foreach ($errorInfos as $errorInfo) {
                    if ($errorInfo['code'] == $code) {
                        $canRemoveErrorFromQuote = false;
                        break;
                    }
                }

                if (!$canRemoveErrorFromQuote) {
                    break;
                }
            }

            if ($canRemoveErrorFromQuote) {
                $params = ['origin' => 'cataloginventory', 'code' => $code];
                $quote->removeErrorInfosByParams(null, $params);
            }
        }
    }

    /**
     * @param array $profileData
     * @return array
     */
    protected function prepareDeliveryDateForStockPoint($profileData)
    {
        $now = $this->_tzHelper->scopeDate(
            null,
            date('Y-m-d H:i:s', strtotime($this->_tzHelper->date()->format('Y-m-d H:i:s')))
        );
        $nextDeliveryDate = $this->_tzHelper->scopeDate(
            null,
            date('Y-m-d H:i:s', strtotime($profileData['delivery_date']))
        );

        $originalDeliveryDate = $this->_tzHelper->scopeDate(
            null,
            date('Y-m-d H:i:s', strtotime($profileData['original_delivery_date']))
        );

        $deliveryTime = $profileData['delivery_time'];
        $deliveryTimeFrom = $profileData['delivery_time_from'];
        $deliveryTimeTo = $profileData['delivery_time_to'];

        if ($nextDeliveryDate >= $now) {
            $deliveryDate = $profileData['delivery_date'];
            $deliveryTimeSlot = $profileData['delivery_time_id'];
        } elseif ($originalDeliveryDate >= $now) {
            $deliveryDate = $profileData['original_delivery_date'];
            $deliveryTimeSlot = $profileData['original_delivery_time_slot'];
            $deliveryTime = $profileData['original_delivery_time'];
            $deliveryTimeFrom = $profileData['original_delivery_time_from'];
            $deliveryTimeTo = $profileData['original_delivery_time_to'];

            $this->_registry->unregister(self::IS_REMOVE_STOCK_POINT);
            $this->_registry->register(self::IS_REMOVE_STOCK_POINT, true);
        } else {
            $deliveryDate = null;
            $deliveryTimeSlot = $profileData['original_delivery_time_slot'];
            $deliveryTime = $profileData['original_delivery_time'];
            $deliveryTimeFrom = $profileData['original_delivery_time_from'];
            $deliveryTimeTo = $profileData['original_delivery_time_to'];

            $this->_registry->unregister(self::IS_REMOVE_STOCK_POINT);
            $this->_registry->register(self::IS_REMOVE_STOCK_POINT, true);
        }
        $this->_registry->unregister(self::STOCK_POINT_ORIGINAL_DELIVERY_DATE);
        $this->_registry->register(self::STOCK_POINT_ORIGINAL_DELIVERY_DATE,
            $profileData['original_delivery_date']);

        $this->_registry->unregister(self::STOCK_POINT_ORIGINAL_DELIVERY_TIME_SLOT);
        $this->_registry->register(self::STOCK_POINT_ORIGINAL_DELIVERY_TIME_SLOT,
            $profileData['original_delivery_time_slot']);
        return [
            'delivery_date' => $deliveryDate,
            'delivery_time_id' => $deliveryTimeSlot,
            'original_delivery_time' => $deliveryTime,
            'original_delivery_time_from' => $deliveryTimeFrom,
            'original_delivery_time_to' => $deliveryTimeTo
        ];
    }
    
    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param array $orderData
     */
    private function removeNegativeStockFromProduct($product, $orderData)
    {

        if ($product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            /** @var \Riki\Catalog\Model\Product\Bundle\Type $bundleTypeInstance */
            $bundleTypeInstance = $product->getTypeInstance();
            $optionCollection = $bundleTypeInstance->getOptionsCollection($product);
            $selectionOptions = $bundleTypeInstance->getSelectionsCollection($optionCollection->getAllIds(), $product);

            foreach ($selectionOptions as $selection) {
                $this->removeNegativeStockFromProduct($selection, $orderData);
            }
        } else {
            /**
             * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
             */
            $functionCache = $this->_objectManager->get(\Riki\Framework\Helper\Cache\FunctionCache::class);            $cacheTags = ['stock_update_qty_' . $product->getId()];
            $functionCache->invalidateByCacheTag($cacheTags);
            // Check and remove negative quantity from stock setting
            $stockSetting = $this->stockModel->getStockSettingsByStoreId($product->getId(),
                $orderData['store_id'])->getData();

            $negativeStocks = array_filter($stockSetting, function ($value, $key) {
                return preg_match('/^quantity_[\d]+/', $key) && $value < 0;
            }, ARRAY_FILTER_USE_BOTH);

            if (!empty($negativeStocks)) {
                foreach ($negativeStocks as $stockKey => $quantity) {
                    $placeId = explode('quantity_', $stockKey)[1];

                    if ($stockSetting['use_config_setting_for_backorders_'. $placeId] == 1 ) {
                        if ($stockSetting['default_use_default_setting_for_backorder_' . $placeId] == 1) {
                            if ($stockSetting['backorders_' . $placeId] != 0) {
                                continue;
                            }
                        } else if ($stockSetting['default_allow_backorder_'. $placeId] != 0) {
                            continue;
                        }
                    } else if ($stockSetting['backorder_allowed_' . $placeId] != 0) {
                        continue;
                    }

                    $this->wyomindStockRepository->updateStock(
                        $product->getId(),
                        1,
                        $placeId,
                        $stockSetting['manage_stock_' . $placeId], 0,
                        $stockSetting['backorder_allowed_' . $placeId],
                        $stockSetting['default_use_default_setting_for_backorder_' . $placeId]
                    );
                }
                $this->wyomindStockRepository->updateInventory($product->getId());
            }
        }
    }

    private function isDelayPayment($isDelayPayment, $orderTimes, $lastOrderTimeIsDelayPayment) {
        $isProfileDelay = false;
        if ($isDelayPayment) {
            if ($lastOrderTimeIsDelayPayment == 0) {
                $isProfileDelay = true;
            } else if (intval($orderTimes) < $lastOrderTimeIsDelayPayment){
                $isProfileDelay = true;
            }
        }
        return $isProfileDelay;
    }

    /**
     * get list of out of stock product
     *
     * @return mixed
     */
    protected function getListOfOutOfStockProduct()
    {
        return $this->_registry->registry('generate_order_out_of_stock_product');
    }

    /**
     * get list of products are disabled or need to be removed out of profile
     *
     * @return mixed
     */
    protected function getListOfProductDisabledAndRemoved()
    {
        return $this->_registry->registry('generate_order_product_disabled_and_removed');
    }

    /**
     * get list of products need to be removed out of profile
     *
     * @return mixed
     */
    protected function getListOfProductNeedToBeRemoved()
    {
        return $this->_registry->registry('generate_order_product_removed');
    }

    /**
     * get list spot product need to be removed out of profile after generated order success
     *
     * @return mixed
     */
    protected function getListOfSpotProduct()
    {
        return $this->_registry->registry('generate_order_spot_product');
    }

    /**
     * Product is skipped by seasonal logic
     *
     * @param $product
     * @param $item
     * @return bool
     */
    protected function isProductSkipped($product, $item)
    {
        /*flag to check this product is allowed seasonal skip*/
        $allowSeasonalSkip = $product->getCustomAttribute('allow_seasonal_skip')
            ? $product->getCustomAttribute('allow_seasonal_skip')->getValue()
            : null;

        /*seasonal skip option*/
        $seasonalSkipOptional = $product->getCustomAttribute('seasonal_skip_optional')
            ? $product->getCustomAttribute('seasonal_skip_optional')->getValue()
            : null;

        /*time to skip for this product - from*/
        $allowSkipFrom = $product->getCustomAttribute('allow_skip_from')
            ? $product->getCustomAttribute('allow_skip_from')->getValue()
            : null;

        /*time to skip for this product - to*/
        $allowSkipTo = $product->getCustomAttribute('allow_skip_to')
            ? $product->getCustomAttribute('allow_skip_to')->getValue()
            : null;

        if ($item['is_skip_seasonal']) {
            if (strtotime($item['skip_from']) <= strtotime($item['delivery_date']) &&
                strtotime($item['delivery_date']) <= strtotime($item['skip_to'])
            ) {
                return true;
            }
        } else {
            if ($item['is_skip_seasonal'] !== '0' && !$seasonalSkipOptional) {
                if ($allowSeasonalSkip &&
                    strtotime($allowSkipFrom) <= strtotime($item['delivery_date']) &&
                    strtotime($item['delivery_date']) <= strtotime($allowSkipTo)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Process for exceptional case
     *
     * @param $e
     * @throws \Exception
     */
    private function exceptionProcess(\Exception $e)
    {
        if ($this->checkDeadlock($e)) {
            throw $e;
        } else {
            $this->_loggerOrder->critical($e);
        }
    }

    /**
     * validate quote item
     *
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return bool
     */
    private function isValidQuoteItem(
        \Magento\Quote\Model\Quote\Item $quoteItem
    ) {
        if ($quoteItem->getHasError()) {
            return false;
        }

        if ($quoteItem->getHasChildren()) {
            foreach ($quoteItem->getChildren() as $childItem) {
                if ($childItem->getHasError()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param $hanpukaiType
     * @param $profileModel
     * @return bool
     */
    private function validateHanpukaiSubscriptionLimit($hanpukaiType, $profileModel)
    {
        if ($hanpukaiType) {
            $courseId = $profileModel->getData('course_id');
            $courseModel = $this->helperCourse->loadCourse($courseId);
            if ($courseModel->getId()) {
                if ($hanpukaiLimit = $courseModel->getData('hanpukai_maximum_order_times')) {
                    $profileOrderTimes = $profileModel->getData('order_times');
                    if ($hanpukaiLimit <= $profileOrderTimes) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @param Quote $quote
     * @param $product
     * @param $qtyRequested
     */
    private function checkStockFromDB(Quote $quote, $product, $qtyRequested)
    {
        $qtyAdded = 0;
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductId() == $product->getEntityId()) {
                $qtyAdded += $item->getQty();
            }
        }
        // total quantity need to be checked
        $totalQty = $qtyRequested + $qtyAdded;

        // get list of place ids which are active for this order
        $places = $this->getRegistry()->registry(self::NED2831_LIST_PLACE_IDS_REGISTRY_KEY);
        $placeIds = [];
        foreach ($places as $place) {
            $placeIds[] = $place->getId();
        }

        if ($placeIds) {
            $connection = $this->resourceConnection->getConnection('default');
            $table = $connection->getTableName('advancedinventory_stock');
            $sql = $connection->select()
                ->from($table)
                ->where('product_id = ?', $product->getEntityId())
                ->where('place_id IN(?)', $placeIds)
                ->where('manage_stock = ?', 1);

            $items = $connection->fetchAll($sql);

            // calculate actual stock remain
            $actualStock = 0;
            foreach ($items as $item) {
                $backOrderLimit = $this->getBackOrderLimit($item);
                $actualStock += $item['quantity_in_stock'] + $backOrderLimit;
            }
            if ($actualStock >= $totalQty) {
                $this->_loggerOrder->addError(__(
                    'Product #%1 is actually in stock, we have %2',
                    $product->getName(),
                    $actualStock
                ));
                $this->_loggerOrder->addInfo(json_encode($items));
            }
        }
    }

    /**
     * Get back order limit
     * Code is based on \Riki\AdvancedInventory\Model\Stock::repairStockSettingsByPlaceIds
     * @param $item
     * @return int
     */
    private function getBackOrderLimit($item)
    {
        if (isset($item['use_config_setting_for_backorders']) && $item['use_config_setting_for_backorders'] = 1){
            return 0;
        }

        if (isset($item['backorder_allowed']) && $item['backorder_allowed'] <= 0){
            return 0;
        }

        $isExpired = false;
        $backorderLimitAtWarehouse = 0;
        if ($item['backorder_expire']) {
            $isExpired = $this->stockModel->isExpiredDate($item[$item['backorder_expire']]);
        }

        if (!$isExpired) {
            /*back order limit at warehouse*/
            $backorderLimitAtWarehouse = $item['backorder_limit'];

            /*back order limit is 0 - unlimited*/
            if ($backorderLimitAtWarehouse == 0) {
                $backorderLimitAtWarehouse = \Riki\AdvancedInventory\Model\Stock::BACKORDER_UNLIMIT_QTY;
            }

            /**
             * for case qty at warehouse is less than 0,
             * it means qty has been deducted for back order limit
             * so for this case, limit will be calculate again
             */
            if ($item['quantity_in_stock'] < 0) {
                $backorderLimitAtWarehouse += $item['quantity_in_stock'];
            }
        }

        return $backorderLimitAtWarehouse;
    }

    /**
     * Add item with sku for variable fee for subscription monthly fee
     *
     * @param \Riki\Subscription\Model\Profile\Profile $profileModel
     * @param $productCartModel
     * @param array $productCartData
     * @return mixed
     */
    public function addItemWithSkuVariableFeeForSubscriptionMonthlyFee($profileModel, $productCartModel, &$productCartData)
    {
        $skuForVariableFee = $this->scopeConfig->getValue(
            self::CONFIG_PATH_MONTHLY_FEE_SKU_FOR_VARIABLE_FEE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($skuForVariableFee) {
            try {
                $productModel = $this->_productRepository->get($skuForVariableFee);
                if ($productModel && $productModel->getTypeId() != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                    $firstProductCartItem = $productCartModel->getFirstItem();
                    $unitCase = $this->caseDisplay->getCaseDisplayKey($productModel->getData('case_display'));
                    $variableFee = $profileModel->getData('variable_fee');
                    $productData = [
                        'product_id' => $productModel->getData('entity_id'),
                        'parent_item_id' => 0,
                        'unit_qty' => ($productModel->getData('unit_qty')) ? $productModel->getData('unit_qty') : 1,
                        'unit_case' => ($unitCase) ? $unitCase : 'EA',
                        'price' => ($variableFee) ? $variableFee : 0,
                        'gw_id' => $firstProductCartItem->getData('gw_id'),
                        'gift_message_id' => $firstProductCartItem->getData('gift_message_id'),
                        'billing_address_id' => $firstProductCartItem->getData('billing_address_id'),
                        'shipping_address_id' => $firstProductCartItem->getData('shipping_address_id'),
                        'delivery_date' => $firstProductCartItem->getData('delivery_date'),
                        'is_skip_seasonal' => $firstProductCartItem->getData('is_skip_seasonal'),
                        'skip_from' => $firstProductCartItem->getData('skip_from'),
                        'skip_to' => $firstProductCartItem->getData('skip_to'),
                        'is_spot' => 0,
                        'is_addition' => null,
                        'stock_point_discount_rate' => $firstProductCartItem->getData('stock_point_discount_rate'),
                        'qty' => 1,
                        'delivery_time' => null,
                        'delivery_time_id' => null,
                        'delivery_time_from' => null,
                        'delivery_time_to' => null,
                        'is_variable_fee' => true,
                        'variable_fee' => ($variableFee) ? $variableFee : 0
                    ];
                    return $productCartData[$firstProductCartItem->getData('shipping_address_id')][] = $productData;
                } else {
                    $this->_loggerOrder->addError(
                        'Subscription profile ' . $profileModel->getProfileId() .
                        ' has config a SKU #' . $skuForVariableFee . ' for variable fee is bundle'
                    );
                    return false;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->_loggerOrder->addError(
                    'Subscription profile ' . $profileModel->getProfileId() .
                    ' has SKU #' . $skuForVariableFee . ' for variable fee doesn\'t exist'
                );
                return false;
            }
        } else {
            $this->_loggerOrder->addError(
                'Subscription profile ' . $profileModel->getProfileId() .
                ' has no config a SKU for variable fee'
            );
            return false;
        }
    }

    /**
     * Verify duplicated item before add to productCart
     *
     * @param array $productCart
     * @param array $item
     * @return array
     */
    private function _addItemToProductCart($productCart, $item)
    {
        $compareKeys = ['product_id', 'parent_item_id', 'unit_qty', 'unit_case', 'price'];
        $totalProducts = count($productCart);
        $hasDuplicatedItem = false;
        for ($i = 0; $i < $totalProducts; $i++) {
            $duplicatedFlag = true;
            foreach ($compareKeys as $key) {
                if (array_key_exists($key, $productCart[$i]) && array_key_exists($key, $item)) {
                    if ($productCart[$i][$key] != $item[$key]) {
                        $duplicatedFlag = false;
                    }
                }
            }
            if ($duplicatedFlag) {
                /**
                 * Increase qty of item
                 */
                $productCart[$i]['qty'] += $item['qty'];
                $hasDuplicatedItem = true;
            }
        }

        if (!$hasDuplicatedItem) {
            $productCart[] = $item;
        }
        return $productCart;
    }

    /**
     * @param CartEstimationInterface $cartEstimation
     * @param \Magento\Customer\Api\Data\CustomerInterface|null $customer
     * @return \Magento\Framework\Model\AbstractExtensibleModel|\Magento\Sales\Api\Data\OrderInterface|object|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createMageOrderForGillette($cartEstimation, $customer = null) {
        $this->_registry->unregister(self::PROFILE_GENERATE_STATE_REGISTRY_NAME);
        $this->_registry->register(self::PROFILE_GENERATE_STATE_REGISTRY_NAME, true);
        $isSimulator = $cartEstimation->getData('is_simulator');
        $courseCode = $cartEstimation->getCourseCode();
        $frequencyId = $cartEstimation->getFrequencyId();
        $products = $cartEstimation->getProducts();
        $paymentMethod = $cartEstimation->getPaymentMethod();
        $shippingAddressId = $cartEstimation->getShippingAddressId();
        $deliveryDate = $cartEstimation->getDeliveryDate();
        $deliveryTime = $cartEstimation->getDeliveryTime();
        $shippingMethod = 'riki_shipping_riki_shipping';
        $customAvailableDate = $this->scopeConfig->getValue(
            self::GILLETTE_CUSTOM_AVAILABLE_START_DATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $bladeSku = $this->scopeConfig->getValue(
            \Nestle\Gillette\Model\ProductInfo::GILLETTE_BLADE_SKU,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $cartRulesCanApply = $this->scopeConfig->getValue(
            \Nestle\Gillette\Model\ProductInfo::GILLETTE_CART_RULES_CAN_APPLY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $address = $cartEstimation->getAddress();
        $courseId = null;
        $haveBladeSku = false;
        if ($courseCode) {
            $course = $this->helperCourse->getCourseByCode($courseCode);
            $courseId = $course->getId();
            $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
            $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);
        }
        $this->_registry->unregister('is_gillette_order');
        $this->_registry->register('is_gillette_order', 1);

        $quote = $this->quote->create()->setIsActive(0); //Create object of quote
        $quote->setStoreId(1); //set store for which you create quote
        $quote->setCurrency();
        /*Set other information to cart*/
        $quote->setData('riki_course_id', $courseId);
        $quote->setData('riki_frequency_id', $frequencyId);
        $quote->setData('n_delivery', 1);
        $quote->setData('is_gillette_quote', 1);
        $quote->setData('cart_rules_can_apply', $cartRulesCanApply);
        $quote->setData('custom_available_date', $customAvailableDate);

        /* shipping address */
        
        /* main controller goes here */
        if ( \Zend_Validate::is($cartEstimation->getAddress(),'NotEmpty') ){
            goto withCustomAddress;
        } else {
            goto withChosenAddress;
        }
        
        withCustomAddress: {
             //Set Address to quote
            if (isset($address['save_in_address_book']) and $address['save_in_address_book']) {
                unset($address['save_in_address_book']);
                if (!$isSimulator) {
                    $connection = $this->resourceConnection->getConnection();
                    $connection->beginTransaction();
                    $customerAddressFactory = $this->customerAddress->create();
                    $customerAddressFactory->addData($address);
                    $customerAddressFactory->setParentId($customer->getId());
                    $newShippingAddress = $customerAddressFactory->save();
                    $shippingAddressId = $newShippingAddress->getId();
                    $customer = $this->customerRepository->getById($customer->getId());
                    goto withChosenAddress;
                } else {
                    goto withNewAddress;
                }
            } else {
                goto withNewAddress;
            }
        }
        /*In case using new address but do not save in address book*/
        withNewAddress: {
            $quote->getShippingAddress()->addData($address);
            $billingAddress = $this->_addressHelper->getDefaultHomeQuoteAddressByCustomerId($customer->getId());
            $quoteBillingAddress = $this->quoteAddressFactory->create();
            $quoteBillingAddress = $quoteBillingAddress->importCustomerAddressData($billingAddress);
            /* Assign address to cart*/
            $quote->assignCustomerWithAddressChange($customer, $quoteBillingAddress, $quote->getShippingAddress());
            $quote->setData('new_shipping_address', true);
            goto setFlag;
        }
        
        withChosenAddress: {
            if (!$shippingAddressId) {
                $shippingAddressId = $customer->getDefaultShipping();
                foreach ($customer->getAddresses() as $address) {
                    $shippingAddress = $address;
                    break;
                }
            }
           $shippingAddress = $billingAddress = !$shippingAddressId ? $shippingAddress : $this->customerAddressRepository->getById($shippingAddressId);
            if($addressType = $shippingAddress->getCustomAttribute('riki_type_address')){
                $addressType = $addressType->getValue();
                if($addressType != AddressType::OFFICE) {
                    $billingAddress = $this->_addressHelper->getDefaultHomeQuoteAddressByCustomerId($customer->getId());
                }
            }
           $quoteShippingAddress = $this->quoteAddressFactory->create();
           $quoteShippingAddress = $quoteShippingAddress->importCustomerAddressData($shippingAddress);

           $quoteBillingAddress = $this->quoteAddressFactory->create();
           $quoteBillingAddress = $quoteBillingAddress->importCustomerAddressData($billingAddress);
           /* Assign address to cart*/
           $quote->assignCustomerWithAddressChange($customer, $quoteBillingAddress, $quoteShippingAddress);
            
           goto setFlag;
        }

        setFlag: {
           $quote->setData(Delitype::DELIVERY_TYPE_FLAG, true);
        }

        /* Add product to cart*/
        /** @var ProductInfoInterface $productItem */
        foreach ($products as $productItem) {
            $product = $this->_productRepository->get($productItem->getSku());
            $caseDisplay = $product->getCustomAttribute('case_display')? $product->getCustomAttribute('case_display')->getValue():null;
            $unitQty = $product->getCustomAttribute('unit_qty') ? $product->getCustomAttribute('unit_qty')->getValue() : 0;
            $qty = $qtyStock = $productItem->getQty();
            if ($caseDisplay == 2) {/*product is case*/
                $qtyStock = $qty * $unitQty;
            }
            $this->_registry->unregister(StockRegistryProvider::UNREGISTER_STOCK_ITEM);
            $this->_registry->register(StockRegistryProvider::UNREGISTER_STOCK_ITEM, $product->getId());

            if ($isSimulator
                || ($this->checkProductInStock($quote, $product, $qtyStock, null, null))
            ) {
                /*product is disabled*/
                if ($product->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                    $this->gilletteLogger->error('Product ' . $product->getName(). ' is no longer available.');
                    throw new LocalizedException(__('The product %s is disabled', $product->getName()));
                }
                try {
                    if ($product->getId()) {
                        $product->setData('is_subscription_product', 1);
                        $quoteItem = $quote->addProduct(
                            $product,
                            (int)($qty)
                        );
                        if (is_string($quoteItem)) {
                            throw new LocalizedException(__('Cannot add the product %s into cart', $product->getName()));
                        }
                        if (!$haveBladeSku and $product->getSku() == $bladeSku) {
                            $haveBladeSku = true;
                        }
                        if ($deliveryDate) {
                            $quoteItem->setData('delivery_date', $deliveryDate);
                        }
                        if ($deliveryTime) {
                            $quoteItem->setData('delivery_time', $deliveryTime->getData('slot_name'));
                            $quoteItem->setData('delivery_timeslot_id', $deliveryTime->getId());
                        }
                        if ($productItem->getIsMachine()) {
                            $quoteItem->setData('is_riki_machine', true);
                        }
                        if ($productItem->getGiftWrapId()) {
                            $quoteItem->setData('gw_id', $productItem->getGiftWrapId());
                            $this->updateWrappingItem($productItem->getGiftWrapId(), $quoteItem);
                        }
                    }
                } catch (\Exception $e) {
                    $this->gilletteLogger->critical($e);
                    throw  $e;
                }
            }
            else {
                $this->gilletteLogger->error("Product " . $product->getName() . " out of stock");
                throw new LocalizedException(__('The product %s is out of stock', $product->getName()));
            }
        }
        if (!$quote->getAllItems()) {
            $message = __('All of products are not available to ship');
            throw new LocalizedException($message);
        }
        $this->deliveryTypeHelper->setDeliveryTypeForQuote($quote);
        $quote->unsetData(Delitype::DELIVERY_TYPE_FLAG);

        /*Shipping method*/
        $shippingAddress=$quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->setShippingMethod($shippingMethod); //shipping method
        $quote->setShippingAddress($shippingAddress);
        $quote->save();
        /*Apply coupon code*/
        if ($cartEstimation->getCouponCode()) {
            $validCoupon = $this->couponHelper->getValidCouponCode($cartEstimation->getCouponCode());
            if ($validCoupon) {
                $quote->setCouponCode($validCoupon);
            }
        }
        /* get reward point and setting */
        if ($cartEstimation->getRewardPoint()) {
            $this->addRewardPoint($quote, $cartEstimation->getRewardPoint(), $customer);
        }
        $quote->setData('allowed_earned_point', 1);
        $quote->setData('have_blade_sku', $haveBladeSku);
        $quote->setIsAutoAddFirstItem(true);
        $quote->collectTotals();

        /*In case order have bladeSKU and customAvailableDate*/
        if (!$isSimulator and !$deliveryDate and $customAvailableDate and $haveBladeSku) {
            $deliveryDate = $this->calculateDeliveryDateForGillette($quote, $customAvailableDate);
            foreach ($quote->getAllItems() as $quoteItem) {
                $quoteItem->setData('delivery_date', $deliveryDate);
            }
        } else {
            if (!$isSimulator and $quote->getData('riki_course_id')
                and $quote->getData('riki_frequency_id') and !$cartEstimation->getDeliveryDate()) {
                $deliveryDate = $this->calculateDeliveryDateForGillette($quote);
                foreach ($quote->getAllItems() as $quoteItem) {
                    $quoteItem->setData('delivery_date', $deliveryDate);
                }

            }
        }
        $freePaymentForSimulator = false;
        // check grand total = 0 and paygent , set payment back to free method (in case use all point)
        if ($quote->getGrandTotal() == 0) {
            $paymentData['method'] = \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE;
            $freePaymentForSimulator = $isSimulator ? true : false;
        } else {
            /*Payment method*/
            $paymentData['method'] = \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_PAYGENT;
        }
        if (!$freePaymentForSimulator and $paymentMethod and $paymentMethod->getMethod()) {
            $paymentData = $paymentMethod->getData();
        }
        $paymentData['checks'] = [
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_INTERNAL,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
            \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
        ];
        $quote->setTotalsCollectedFlag(false);
        $quote->getPayment()->importData($paymentData);
        $earnPoint = $quote->getBonusPointAmount();

        // Create Order From Quote
        $this->_eventManager->dispatch('checkout_submit_before', ['quote' => $quote]);

        // Set remote ip before submit quote, if remote_ip of quote
        if (empty($quote->getRemoteIp())) {
            $quote->setRemoteIp($this->checkoutSession->getQuote()->getRemoteIp());
        }

        try {
            $order = $this->quoteManagement->submit($quote);
            if (isset($connection)) {
                $connection->commit();
            }
        } catch (\Exception $e) {
            if (isset($connection)) {
                $connection->rollBack();
            }
            $this->gilletteLogger->critical($e);
            throw $e;
        }
        if ($order) {
            if (!$isSimulator) {
                $this->_eventManager->dispatch(
                    'checkout_submit_all_after',
                    ['order' => $order, 'quote' => $quote]
                );
            }
            if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
                $order->setBonusPointAmount($earnPoint);
                $order->setData('quote', $quote);
                $order->setData('riki_frequency_id', $frequencyId);
                if ($quote->getData('new_shipping_address')) {
                    $order->setData('new_shipping_address', $cartEstimation->getAddress());
                }
                if ($cartEstimation->getRewardPoint()) {
                    $rewardPoint = [];
                    $rewardPoint['reward_user_setting'] = $quote->getData('reward_user_setting');
                    $rewardPoint['reward_user_redeem'] = $quote->getData('reward_user_redeem');
                    $order->setData('reward_point', $rewardPoint);
                }
            }
            return $order;
        }
        throw new LocalizedException(__(
            'Something went wrong while processing your request. Please try again later.'
        ));
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param null $customerAvailableStartDate
     */
    public function calculateDeliveryDateForGillette($quote, $customAvailableDate = null) {

        $shippingAddress = $quote->getShippingAddress();
        $regionCode = $shippingAddress->getRegionCode();
        $deliveryType = [];
        foreach ($quote->getAllItems() as $quoteItem) {
            $deliveryType[] = $quoteItem->getData('delivery_type');
            break;
        }
        /*
         * Gillette product always have stock in HITACHI warehouse
         * Always use HITACHI WH to get assignation
         * Improve performance for [NED-5478]
         */
        $listWh = [\Riki\StockPoint\Helper\ValidateStockPointProduct::WH_HITACHI];
        $leadTimeCollection = $this->deliveryDate->caculateDate($listWh, $deliveryType, $regionCode);
        $numberNextDate = 0;
        $posCode = $listWh[0];
        if ($leadTimeCollection) {
            $numberNextDate = $leadTimeCollection['shipping_lead_time'];
            $posCode = $leadTimeCollection['warehouse_id'];
        }
        $finalDelivery = $this->deliveryDate->caculateFinalDay($numberNextDate, $posCode, [], $customAvailableDate);
        $finalDelivery = end($finalDelivery);
        return $this->_tzHelper->date(strtotime($finalDelivery . '+1 day'))->format("Y-m-d");

    }
}