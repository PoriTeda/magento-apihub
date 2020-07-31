<?php
namespace Riki\Subscription\Model\Profile;

use Magento\Sales\Model\Order;
use Riki\CvsPayment\Model\CvsPayment;
use Bluecom\Paygent\Model\Paygent;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\Payment\Model\Method\Free;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay as CaseDisplay;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;
use Riki\PaymentBip\Model\InvoicedBasedPayment;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Subscription\Api\Data\ProfileInterface;
use Bluecom\PaymentFee\Model\PaymentFeeFactory;
use Riki\Subscription\Helper\Data as SubscriptionHelperData;
use Riki\Subscription\Helper\Order\Data as SubscriptionOrderHelper;
use Riki\Subscription\Logger\LoggerStateProfile;
use Riki\Subscription\Model\ProductCart\ProductCart;
use Riki\Subscription\Model\Version\VersionFactory;
use Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile as ProfileIndexer;

/**
 * Subscription Profile data model
 */
class Profile extends \Magento\Framework\Model\AbstractModel implements
    IdentityInterface,
    ProfileInterface
{
    const TABLE = 'subscription_profile';
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    const SHIPPING_ADDRESS_ID = 'shipping_address_id';
    const BILLING_ADDRESS_ID = 'billing_address_id';
    const PARENT_ITEM_ID = 'parent_item_id';
    const UNIT_WEEK = '1';
    const UNIT_MONTH = '2';
    const CACHE_TAG = 'riki_subscription_profile';

    protected $_eventPrefix = 'subscription_profile';

    protected $_eventObject = 'subscription_profile';

    protected $arrIdToPaymentCode = [
        1 => 'CREDIT_CARD',
        2 => 'COD',
        3 => 'CSV'
    ];

    const LOCKER = 1;
    const PICKUP = 2;
    const DROPOFF = 3;
    const SUBCARRIER = 4;

    const SUBSCRIPTION_TYPE_MAIN = 'main';
    const SUBSCRIPTION_TYPE_TMP = 'tmp';
    const SKIP_NEXT_DELIVERY = 1;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;

    /**
     * @var \Riki\Subscription\Helper\Profile\Email
     */
    protected $profileEmail;

    /**
     * @var \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee
     */
    protected $paymentFeeModel;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\CollectionFactory
     */
    protected $productCartCollection;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionProduct;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var array
     */
    protected $cartOrder = [];

    /**
     * @var \Riki\SubscriptionFrequency\Model\FrequencyFactory
     */
    protected $frequencyFactory;

    /**
     * @var \Riki\TimeSlots\Model\TimeSlotsFactory
     */
    protected $timeSlotsFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $timezoneHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Bluecom\PaymentCustomer\Helper\Data
     */
    protected $paymentCustomerHelper;

    /**
     * @var PaymentFeeFactory
     */
    protected $paymentFeeFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var array
     */
    protected $simpleStorage = [];

    /**
     * @var int
     */
    protected $totalAmount = 0;

    /**
     * @var int
     */
    protected $discount = 0;

    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     */
    protected $wrappingCollectionFactory;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $helperWrapping;

    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @var \Magento\Framework\Pricing\Adjustment\AdjustmentInterface
     */
    protected $adjustmentCalculator;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    protected $customerCollection;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /** @var \Riki\Subscription\Helper\Data */
    protected $subHelperData;

    /**
     * @var \Riki\Subscription\Api\Data\ApiProfileInterfaceFactory
     */
    protected $profileDataFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Tax\Model\Calculation
     */
    protected $calculationTool;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @var
     */
    protected $currentSubscriptionCourse;

    /**
     * @var
     */
    protected $currentCustomer;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Model\Email\DisengagementSender
     */
    protected $disengagementEmailSender;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var
     */
    protected $orders;

    /** @var \Riki\Subscription\Model\Version\VersionFactory */
    protected $versionFactory;

    /**
     * @var \Riki\CreateProductAttributes\Model\Product\CaseDisplay
     */
    protected $case;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    protected $cartFactory;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var ProfileIndexer
     */
    protected $profileIndexer;

    /**
     * @var ProfileRepository
     */
    protected $profileRepository;

    /**
     * @var LoggerStateProfile
     */
    protected $loggerStateProfile;

    /** @var \Riki\Subscription\Model\Indexer\ProfileSimulator\Processor */
    protected $profileSimulatorProcessor;

    /** @var \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile */
    protected $resourceModelIndexer;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock\CollectionFactory
     */
    private $outOfStockCollectionFactory;
    /**
     * @var \Riki\Rma\Model\ResourceModel\Rma\CollectionFactory
     */
    private $rmaCollectionFactory;

    /**
         * Profile constructor.
         * @param VersionFactory $versionFactory
         * @param SubscriptionHelperData $subHelperData
         * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
         * @param \Magento\Framework\Locale\FormatInterface $localeFormat
         * @param \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator
         * @param \Magento\Framework\Model\Context $context
         * @param \Magento\Framework\Registry $registry
         * @param \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $paymentFee
         * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
         * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
         * @param \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\CollectionFactory $productCartCollection
         * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProductFactory
         * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
         * @param \Magento\Customer\Model\Session $customerSession
         * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
         * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
         * @param \Magento\Catalog\Model\ProductFactory $productFactory
         * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
         * @param \Riki\CreateProductAttributes\Model\Product\UnitEc $unitEc
         * @param \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory
         * @param \Riki\TimeSlots\Model\TimeSlotsFactory $timeSlotsFactory
         * @param \Riki\Subscription\Model\ProductCart\ProductCartFactory $cartFactory
         * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezoneHelper
         * @param \Bluecom\PaymentCustomer\Helper\Data $paymentCustomerHelper
         * @param \Magento\Customer\Model\CustomerFactory $customerFactory
         * @param PaymentFeeFactory $paymentFeeFactory
         * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
         * @param \Riki\Subscription\Helper\Profile\Email $profileEmail
         * @param \Magento\Customer\Model\AddressFactory $addressFactory
         * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory
         * @param \Magento\GiftWrapping\Helper\Data $helperWrapping
         * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
         * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection
         * @param \Riki\Loyalty\Model\RewardManagement $rewardManagement
         * @param \Riki\Subscription\Api\Data\ApiProfileInterfaceFactory $profileDataFactory
         * @param \Magento\Framework\Api\DataObjectHelper $dataObject
         * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
         * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
         * @param \Magento\Tax\Model\Calculation $calculationTool
         * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
         * @param \Magento\Backend\Model\Auth\Session $authSession
         * @param \Riki\SubscriptionProfileDisengagement\Model\Email\DisengagementSender $disengagementSender
         * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
         * @param \Riki\CreateProductAttributes\Model\Product\CaseDisplay $case
         * @param ProfileIndexer $profileIndexer
         * @param ProfileRepository $profileRepository
         * @param LoggerStateProfile $loggerStateProfile
         * @param ProfileIndexer $resourceModelIndexerProfile
         * @param \Riki\Subscription\Model\Indexer\ProfileSimulator\Processor $profileSimulatorProcessor
         * @param \Magento\Framework\App\ResourceConnection $resourceConnection,
         * @param \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock\CollectionFactory $outOfStockCollectionFactory,
         * @param \Riki\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory,
         * @param array $data
         */
    public function __construct(
        \Riki\Subscription\Model\Version\VersionFactory $versionFactory,
        SubscriptionHelperData $subHelperData,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $paymentFee,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\CollectionFactory $productCartCollection,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProductFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Riki\CreateProductAttributes\Model\Product\UnitEc $unitEc,
        \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory,
        \Riki\TimeSlots\Model\TimeSlotsFactory $timeSlotsFactory,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $cartFactory,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezoneHelper,
        \Bluecom\PaymentCustomer\Helper\Data $paymentCustomerHelper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        PaymentFeeFactory $paymentFeeFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Riki\Subscription\Helper\Profile\Email $profileEmail,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        \Magento\GiftWrapping\Helper\Data $helperWrapping,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\Subscription\Api\Data\ApiProfileInterfaceFactory $profileDataFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObject,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Tax\Model\Calculation $calculationTool,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\SubscriptionProfileDisengagement\Model\Email\DisengagementSender $disengagementSender,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Riki\CreateProductAttributes\Model\Product\CaseDisplay $case,
        ProfileIndexer $profileIndexer,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        LoggerStateProfile $loggerStateProfile,
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $resourceModelIndexerProfile,
        \Riki\Subscription\Model\Indexer\ProfileSimulator\Processor $profileSimulatorProcessor,
        \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock\CollectionFactory $outOfStockCollectionFactory,
        \Riki\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory,
        array $data = []
    ) {
        $this->profileRepository = $profileRepository;
        $this->cache = $context->getCacheManager();
        $this->case = $case;
        $this->versionFactory = $versionFactory;
        $this->calculationTool = $calculationTool;
        $this->subHelperData = $subHelperData;
        $this->priceCurrency = $priceCurrency;
        $this->localeFormat = $localeFormat;
        $this->adjustmentCalculator = $adjustmentCalculator;
        $this->customerFactory = $customerFactory;
        $this->paymentFeeFactory = $paymentFeeFactory;
        $this->paymentCustomerHelper = $paymentCustomerHelper;
        $this->customerSession = $customerSession;
        $this->timezoneHelper = $timezoneHelper;
        $this->paymentFeeModel = $paymentFee;
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManagerInterface;
        $this->productCartCollection = $productCartCollection;
        $this->collectionProduct = $collectionProductFactory;
        $this->customerRepository = $customerRepository;
        $this->_session = $customerSession;
        $this->helperProfile = $helperProfile;
        $this->courseFactory = $courseFactory;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->_unitEc = $unitEc;
        $this->frequencyFactory = $frequencyFactory;
        $this->timeSlotsFactory = $timeSlotsFactory;
        $this->cartFactory = $cartFactory;
        $this->_priceHelper = $priceHelper;
        $this->profileEmail = $profileEmail;
        $this->addressFactory = $addressFactory;
        $this->wrappingCollectionFactory = $wrappingCollectionFactory;
        $this->helperWrapping = $helperWrapping;
        $this->taxCalculation = $taxCalculation;
        $this->customerCollection = $customerCollection;
        $this->rewardManagement = $rewardManagement;
        $this->profileDataFactory = $profileDataFactory;
        $this->dataObjectHelper = $dataObject;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->date = $date;
        $this->authSession = $authSession;
        $this->disengagementEmailSender = $disengagementSender;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->profileIndexer = $profileIndexer;
        $this->loggerStateProfile = $loggerStateProfile;
        $this->resourceModelIndexer = $resourceModelIndexerProfile;
        $this->profileSimulatorProcessor = $profileSimulatorProcessor;
        $this->outOfStockCollectionFactory = $outOfStockCollectionFactory;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
        parent::__construct($context, $registry, null, null, $data);
    }

    protected function _construct()
    {
        $this->_init(\Riki\Subscription\Model\Profile\ResourceModel\Profile::class);
    }

    public static function getListStatuses()
    {
        return [
            self::STATUS_ENABLED    => __('Enabled')
            , self::STATUS_DISABLED => __('Disabled')
        ];
    }

    public static function getListFrequencyUnit()
    {
        return [
            self::UNIT_WEEK     => __('Week')
            , self::UNIT_MONTH  => __('Month')
        ];
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function setProfileId($profileId)
    {
        $this->setData('profileId', $profileId);
        $this->getInfoSubscriptionProfile();
    }

    /**
     * Get customer subscription profile by customer id
     *
     * @param int $customerId
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerSubscriptionProfile($customerId)
    {
        $profileCollection = $this->getResourceCollection();
        $profileCollection->addFieldToFilter('customer_id', $customerId);
        $profileCollection->addFieldToFilter('type', [
            ['neq' => self::SUBSCRIPTION_TYPE_TMP],
            ['null' => true]
        ]);
        $profileCollection->addFieldToFilter('status', 1);

        $profileCollection->getSelect()->joinLeft(
            ['subscription_course' => 'subscription_course'],
            "main_table.course_id = subscription_course.course_id",
            ['subscription_course.course_name', 'subscription_course.allow_skip_next_delivery']
        );
        $profileCollection->addOrder('profile_id', 'DESC');
        return $profileCollection;
    }

    /**
     * Get customer subscription profile ids by customer id
     *
     * @param int $customerId
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerSubscriptionProfileIds($customerId)
    {
        $profileCollection = $this->getResourceCollection();
        $profileCollection->addFieldToSelect('profile_id');
        $profileCollection->addFieldToSelect('created_date'); //use in \Riki\Customer\Block\Account\Info->checkDateSubscription()
        $profileCollection->addFieldToFilter('customer_id', $customerId);
        $profileCollection->addFieldToFilter('type', [
            ['neq' => self::SUBSCRIPTION_TYPE_TMP],
            ['null' => true]
        ]);
        $profileCollection->addFieldToFilter('status', 1);

        $profileCollection->getSelect()->joinLeft(
            ['subscription_course' => 'subscription_course'],
            "main_table.course_id = subscription_course.course_id",
            []
        );

        return $profileCollection;
    }

    /**
     * Get customer subscription profile by customer id
     *
     * @param int $customerId
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerSubscriptionProfileExcludeHanpukai($customerId)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $profileCollection = $this->getResourceCollection();
        $profileCollection->addFieldToFilter('customer_id', $customerId);
        $profileCollection->addFieldToFilter('type', [
            ['neq' => self::SUBSCRIPTION_TYPE_TMP],
            ['null' => true]
        ]);
        $profileCollection->addFieldToFilter('store_id', $storeId);

        $profileCollection->getSelect()->joinLeft(
            ['subscription_course' => 'subscription_course'],
            "main_table.course_id = subscription_course.course_id",
            [
                'subscription_course.course_name',
                'subscription_course.allow_skip_next_delivery',
                'subscription_course.allow_change_product',
                'subscription_course.is_allow_cancel_from_frontend',
                'subscription_course.minimum_order_times',
                'subscription_course.subscription_type',
            ]
        )->where(
            'subscription_course.subscription_type != ?',
            \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI
        );
        //should not display disengaged profiles
        $profileCollection->addFieldToFilter('status', self::STATUS_ENABLED);
        $profileCollection->addFieldToFilter('disengagement_user', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_date', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_reason', ['null' => true]);

        $profileCollection->addOrder('profile_id', 'DESC');
        return $profileCollection;
    }

    /**
     * Get customer subscription profile ids exclude hanpukai by customer id
     *
     * @param int $customerId
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerSubscriptionProfileExcludeHanpukaiIds($customerId)
    {
        $profileCollection = $this->getResourceCollection();
        $profileCollection->addFieldToSelect('profile_id');
        $profileCollection->addFieldToFilter('customer_id', $customerId);
        $profileCollection->addFieldToFilter('type', [
            ['neq' => self::SUBSCRIPTION_TYPE_TMP],
            ['null' => true]
        ]);

        $profileCollection->getSelect()->joinLeft(
            ['subscription_course' => 'subscription_course'],
            "main_table.course_id = subscription_course.course_id",
            []
        )->where(
            'subscription_course.subscription_type != ?',
            \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI
        );
        //should not display disengaged profiles
        $profileCollection->addFieldToFilter('status', self::STATUS_ENABLED);
        $profileCollection->addFieldToFilter('disengagement_user', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_date', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_reason', ['null' => true]);
        $profileCollection->getSelect()->limit(1);

        return $profileCollection;
    }

    public function getCollectionProfileByCustomerIdAndCourseId($customerId, $courseId)
    {
        $profileCollection = $this->getResourceCollection();
        $profileCollection->addFieldToFilter('customer_id', $customerId);
        $profileCollection->addFieldToFilter('course_id', $courseId);
        $profileCollection->addFieldToFilter('type', [
            ['neq' => self::SUBSCRIPTION_TYPE_TMP],
            ['null' => true]
        ]);
        $profileCollection->addFieldToFilter('status', 1);
        $profileCollection->addOrder('profile_id', 'DESC');
        return $profileCollection;
    }

    /**
     * Get customer subscription profile hanpukai
     *
     * @param $customerId
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getCustomerSubscriptionProfileHanpukai($customerId)
    {
        $profileCollection = $this->getResourceCollection();
        $profileCollection->addFieldToFilter('customer_id', $customerId);
        $profileCollection->addFieldToFilter('type', [
            ['neq' => self::SUBSCRIPTION_TYPE_TMP],
            ['null' => true]
        ]);
        $profileCollection->addFieldToFilter('status', 1);

        $profileCollection->getSelect()->joinLeft(
            ['subscription_course' => 'subscription_course'],
            "main_table.course_id = subscription_course.course_id",
            ['subscription_course.course_name', 'subscription_course.allow_skip_next_delivery', ]
        )->where(
            'subscription_course.subscription_type = ?',
            \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI
        );
        //should not display disengaged profiles
        $profileCollection->addFieldToFilter('status', self::STATUS_ENABLED);
        $profileCollection->addFieldToFilter('disengagement_user', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_date', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_reason', ['null' => true]);

        $profileCollection->addOrder('profile_id', 'DESC');
        return $profileCollection;
    }

    /**
     * Get customer subscription profile hanpukai ids
     *
     * @param $customerId
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     * @throws LocalizedException
     */
    public function getCustomerSubscriptionProfileHanpukaiIds($customerId)
    {
        $profileCollection = $this->getResourceCollection();
        $profileCollection->addFieldToSelect('profile_id');
        $profileCollection->addFieldToFilter('customer_id', $customerId);
        $profileCollection->addFieldToFilter('type', [
            ['neq' => self::SUBSCRIPTION_TYPE_TMP],
            ['null' => true]
        ]);
        $profileCollection->addFieldToFilter('status', 1);

        $profileCollection->getSelect()->joinLeft(
            ['subscription_course' => 'subscription_course'],
            "main_table.course_id = subscription_course.course_id",
            []
        )->where(
            'subscription_course.subscription_type = ?',
            \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI
        );
        //should not display disengaged profiles
        $profileCollection->addFieldToFilter('status', self::STATUS_ENABLED);
        $profileCollection->addFieldToFilter('disengagement_user', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_date', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_reason', ['null' => true]);
        $profileCollection->getSelect()->limit(1);

        return $profileCollection;
    }

    /**
     * get list product of current profile
     *
     * @return mixed
     * @throws \Exception
     */
    public function getProductCart($original = false)
    {
        if (! $this->getData("profile_id")) {
            throw new LocalizedException(__("Subscription Profile is not loaded yet!"));
        }

        if ($this->hasData("product_cart")) {
            return $this->getData("product_cart");
        }

        $profileId = $this->getData("profile_id");

        $productCatCollection = $this->cartFactory->create()->getCollection();
        $productCatCollection->addFieldToFilter("profile_id", $profileId, $original);

        $this->setData("product_cart", $productCatCollection->getItems());

        return $this->getData("product_cart");
    }

    /**
     * @return mixed
     * @throws \Exception
     *
     */
    public function getProductCartData()
    {
        if (! $this->getData("profile_id")) {
            throw new LocalizedException(__("Subscription Profile is not loaded yet!"));
        }

        if ($this->hasData("product_cart")) {
            return $this->getData("product_cart");
        }

        $profileId = $this->getData("profile_id");

        $productCatCollection = $this->cartFactory->create()->getCollection();
        $productCatCollection->addFieldToFilter("profile_id", $profileId);

        $data = [];

        $productsId = array_map(function ($profileItem) {
            return $profileItem->getProductId();
        }, $productCatCollection->getItems());

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $this->collectionProduct->create();
        $productCollection->addAttributeToFilter('status', 1)
            ->addIdFilter($productsId);

        foreach ($productCatCollection->getItems() as $item) {
            if (array_key_exists($item->getProductId(), $productCollection->getItems())) {
                $obj = new DataObject();
                $obj->setData($item->getData());
                $data[$obj->getData("cart_id")] = $obj;
            }
        }

        $this->setData("product_cart", $data);

        return $data;
    }

    public function getCourseData()
    {
        if (! $this->getData("profile_id")) {
            throw new LocalizedException(__("Subscription Profile is not loaded yet!"));
        }

        if ($this->hasData("course_data")) {
            return $this->getData("course_data");
        }

        $courseId = $this->getData("course_id");

        $objCourse = $this->courseFactory->create()->load($courseId);

        $this->setData("course_data", $objCourse->getData());

        return $this->getData("course_data");
    }

    public function getProductCartCollection($profile_id = 0)
    {
        $productCartCollection = $this->productCartCollection->create();
        $productCartCollection->addFieldToFilter('profile_id', $profile_id);
        return $productCartCollection;
    }

    public function getAttributesProduct($arr_products)
    {
        if (is_array($arr_products) && !empty($arr_products)) {
            $collection = $this->collectionProduct->create();
            $collection->addAttributeToSelect('gift_wrapping_price');
            $collection->addAttributeToFilter('entity_id', $arr_products);
            return $collection;
        }
    }

    /**
     * Get user point amount setting
     *
     * @return float
     */
    public function getPointMyAccount()
    {
        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $this->customerRepository->getById($this->getData('customer_id'));
        if (!$customerCode = $customer->getCustomAttribute('consumer_db_id')) {
            return 0.00;
        }
        $pointSetting = $this->rewardManagement->getRewardUserSetting($customerCode->getValue());
        return $pointSetting['use_point_amount'];
    }

    public function getListProduct($profile_id, $setProfileID = true)
    {
        if ($setProfileID) {
            $this->setProfileId($profile_id);
        }
        $arrProducts = $this->helperProfile->getArrProductCart($profile_id);
        $this->setData('ProductsCart', $arrProducts);
        return $arrProducts;
    }

    public function getTotalProductsPrice()
    {
        if ($this->hasData("TotalPrice")) {
            return $this->getData("TotalPrice");
        }
        $total = 0;
        $arrGroup = [];

        $arrProductCart = $this->getProductCartData();
        foreach ($arrProductCart as $pcartId => $arrData) {
            $arrGroup[$pcartId]['profile'] = $arrData;
            $arrGroup[$pcartId]['details'] = $this->productFactory->create()->load($arrData['product_id']);
        }

        foreach ($arrGroup as $pcartId => $arr) {
            if ($arr['profile']->getData('parent_item_id') == '0') {
                $qty = $arr['profile']['qty'];
                $finalPrice = $arr['details']->getFinalPrice($qty);
                if ($arr['profile']->getData('product_type') != 'bundle') {
                    $amount = $this->adjustmentCalculator->getAmount($finalPrice, $arr['details'])->getValue();
                } else {
                    $amount = $this->subHelperData->getBundleMaximumPrice($arr['details']);
                }
                $amount = $this->priceCurrency->format(
                    $amount,
                    false,
                    \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION
                );
                $amount = $this->localeFormat->getNumber($amount);
                $total += $amount * $qty;
            }
        }

        $this->setData('TotalPrice', $total);
        return $total;
    }

    public function getInfoSubscriptionProfile()
    {
        $profileInfo = [];
        if ($this->hasData('profileId')) {
            $profileInfo = $this->load($this->getData('profileId'));
            $this->setData('SubProfileInfo', $profileInfo);
        }
        return $profileInfo;
    }

    /*
     * @Descriptions : Get Gift Wrapping Fee for Subscription Profile
     *
     * @params $profile_id
     * @return int
     * */
    public function getGiftWrappingFee()
    {
        $profile_id = $this->getData("profile_id");
        $productCartCollection = $this->getProductCartCollection($profile_id);
        $products = $productCartCollection->addFieldToFilter('gw_used', 0);
        $wrappingTax = $this->helperWrapping->getWrappingTaxClass($this->_storeManager->getStore());
        $wrappingRate = $this->taxCalculation->getCalculatedRate($wrappingTax);

        $wrapping_fee = 0;
        if (!empty($products)) {
            foreach ($products as $productcart) {
                if ($productcart->getGwId() > 0 && $productcart->getGwId() != null) {
                    $giftpriceCollection = $this->wrappingCollectionFactory->create()
                        ->addFieldToFilter('wrapping_id', $productcart->getGwId())
                        ->setPageSize(1)
                        ->addWebsitesToResult()->load();
                    if ($giftpriceCollection) {
                        $wrapping_fee += ($giftpriceCollection->setPageSize(1)->getFirstItem()->getData('base_price') * $productcart->getQty());
                    }
                }
            }
        }

        if ($wrapping_fee > 0) {
            $taxRate = $wrappingRate / 100;
            $wrapping_fee = $wrapping_fee + ($taxRate * $wrapping_fee);
        }

        $this->setData('WrappingFee', $wrapping_fee);
        return $wrapping_fee;
    }

    /**
     * @Descriptions : Get Shipping Fee for Subscription Profile
     *
     * @params
     * @return int
     * */
    public function getFinalShippingFee()
    {
        $shipping_fee = 0;
        if ($this->hasData('SubProfileInfo')) {
            $shipping_fee = $this->getData('SubProfileInfo')->getShippingFee();
            $this->setData('ShippingFee', $shipping_fee);
        }
        return $shipping_fee;
    }

    /**
     * Get Payment Fee for Subscription Profile
     *
     * @params $payment_code
     * @return array
     * Example array('entity_id'=> 11,
     *              'payment_code'=>'cashondelivery',
     *              'payment_name'=>'Cash On Delivery',
     *              'fixed_amount' => 324.00, 'active'=>1
     *          )
     * */
    public function getPaymentFee()
    {
        $payment_code = $this->getData("payment_method");
        $model = $this->paymentFeeModel->loadPaymentCode($payment_code);
        $this->setData('PaymentFee', $model['fixed_amount']);
        return $model;
    }

    /*
     * @Descriptions : Get Points Used for Subscription Profile
     *
     * @params: $profile_id
     * @return int
     * */
    public function getCurrencyPointsUsed()
    {
        $point = $this->getPointMyAccount();
        $pointUsed = round($point, 3);
        $this->setData('pointused', $pointUsed);
        return $pointUsed;
    }

    /**
     * @Descriptions : Get Total Payment Fee for Subscription Profile
     * Total Payment Fee = Total Products Price + Gift Wrapping Fee
     *                      + Shipment Fee + Payment Fee + Cash on Delivery Fee - Points Used
     *
     * @params
     * @return int
     * */
    public function getTotalPaymentFee()
    {
        $total = ($this->getData('TotalPrice') + $this->getData('WrappingFee') + $this->getData('ShippingFee') + $this->getData('PaymentFee')) - $this->getData('pointused');
        return $total;
    }

    /**
     * get TentativePointEarned
     *
     * @return int
     */
    public function getTentativePointEarned()
    {
        $tentativePoint = 0.00;
        $products = $this->getListProduct($this->getId(), false);
        if (empty($products)) {
            return $tentativePoint;
        }
        foreach ($products as $product) {
            /** @var \Riki\Subscription\Model\ProductCart\ProductCart $productCart */
            $productCart = $product['profile'];
            /** @var \Magento\Catalog\Model\Product\Interceptor $productDetail */
            if (isset($product['details'])) {
                $productDetail = $product['details'];
                $percent = $productDetail->getData('point_currency') / 100;
                if (!$percent) {
                    continue;
                }
                $tentativePoint += ($percent * $productDetail->getFinalPrice() * (int)$productCart->getData('qty'));
            }
        }
        return floor($tentativePoint);
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->_getData(self::CUSTOMER_ID);
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setCustomerId($id)
    {
        return $this->setData(self::CUSTOMER_ID, $id);
    }

    /**
     * @return string
     */
    public function getCourseName()
    {
        return $this->_getData(self::COURSE_NAME);
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setCourseName($string)
    {
        return $this->setData(self::COURSE_NAME, $string);
    }

    /**
     * @return string
     */
    public function getUpdatedDate()
    {
        return $this->_getData(self::UPDATED_DATE);
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setUpdatedDate($string)
    {
        return $this->setData(self::UPDATED_DATE, $string);
    }

    /**
     * @return string
     */
    public function getFrequencyUnit()
    {
        return $this->_getData(self::FREQUENCY_UNIT);
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setFrequencyUnit($string)
    {
        return $this->setData(self::FREQUENCY_UNIT, $string);
    }

    /**
     * @return string
     */
    public function getFrequencyInterval()
    {
        return $this->_getData(self::FREQUENCY_INTERVAL);
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setFrequencyInterval($string)
    {
        return $this->setData(self::FREQUENCY_INTERVAL, $string);
    }

    /**
     * {@inheritdoc}
     */
    public function getDurationUnit()
    {
        return $this->_getData(self::DURATION_UNIT);
    }

    /**
     * {@inheritdoc}
     */
    public function setDurationUnit($string)
    {
        return $this->setData(self::DURATION_UNIT, $string);
    }

    /**
     * {@inheritdoc}
     */
    public function getDurationInterval()
    {
        return $this->_getData(self::DURATION_INTERVAL);
    }

    /**
     * {@inheritdoc}
     */
    public function setDurationInterval($string)
    {
        return $this->setData(self::DURATION_INTERVAL, $string);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethod()
    {
        return $this->_getData(self::PAYMENT_METHOD);
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentMethod($string)
    {
        return $this->setData(self::PAYMENT_METHOD, $string);
    }

    public function getCourseId()
    {
        return $this->getData('course_id');
    }

    /**
     *
     * @return array
     */
    public function getAvailableCategory()
    {
        $category = [];
        $categoryIds = $this->courseFactory->create()->getResource()->getCategoryIds($this->getCourseId());
        $categories = $this->categoryFactory->create()->getCollection()
            ->addFieldToSelect('name')
            ->addFieldToFilter('entity_id', ['in' => $categoryIds]);
        foreach ($categories as $cate) {
            $category[] = [
                'category_id' => (int)$cate->getId(),
                'category_name' => $cate->getName()
            ];
        }
        return ['available_category' => $category];
    }

    /**
     * Get list course setting for WATSON API
     *
     * @return string[]
     */
    public function getCourseSetting()
    {
        $course = $this->courseFactory->create()->load($this->getCourseId());
        $setting = array_map(function ($value) {
            return $value ? true : false;
        }, $course->getSettings());
        return $setting;
    }

    /**
     * @param array $array
     * @return $this
     */
    public function setCourseSetting($array)
    {
        return $this->setData(self::COURSE_SETTING, $array);
    }

    public function getProductInProfile($item)
    {
        $result = [];
        $currentTime = $this->timezoneHelper->date()->format(
            \Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT
        );
        $productId = $item['product_id'];
        $search = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productId, 'eq')
            ->addFilter('status', 1, 'eq')
            ->create();
        $productRepository = $this->productRepository->getList($search)->getItems();

        $profileVersionId = $this->profileRepository->getProfileIdVersion($item['profile_id']);
        $spotIds = $this->cartFactory->create()->getSpotItemIds($profileVersionId);

        foreach ($productRepository as $product) {
            $unitQty = $item['unit_qty'];
            $unitDisplay = $item['unit_case'];

            if (in_array($item['product_id'], $spotIds)) {
                $spot = true;
            } else {
                $spot = false;
            }

            if ($this->isStockPointProfile()) {
                $this->setStockPointFieldsToProduct($product, $item);
            }
            $qty = $this->case->getQtyPieceCaseForDisplay($unitQty, $item['qty'], $unitDisplay);

            //Using unit qty to calculate tier price
            $finalPrice = $this->getRenderPrice($product, $unitQty * $qty);
            $regularPrice = $this->getRegularPrice($product, $unitQty * $qty);
            $this->discount = $regularPrice > $finalPrice ? ceil($regularPrice - $finalPrice) : 0;
            if ($product->getAttributeText('product_group')) {
                $productGroup = $product->getAttributeText('product_group');
            } else {
                $productGroup = '';
            }

            $result = [
                'ProductID' => (int)$product->getId(),
                'ProductName' => $product->getName(),
                'ProductType' => $product->getTypeId(),
                'ProductQty' => (int)$qty,
                'Unit' => __($unitDisplay),
                'ProductPrice' => $finalPrice * $unitQty,
                'Discount' => $this->calculationTool->round($this->discount * $unitQty * $qty),
                'Amount' => $finalPrice * $unitQty * $qty,
                'UpdatedAt' => $item['updated_at'] ? $item['updated_at'] : $currentTime,
                'SPOTflag' => $spot,
                'ProductGroup' => $productGroup
            ];
            $this->totalAmount = $finalPrice * $item['qty'];
        }
        return $result;
    }

    /**
     * Set stock point fields for product to calculation discount stock point
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $item
     */
    protected function setStockPointFieldsToProduct(
        \Magento\Catalog\Model\Product $product,
        $item
    ) {
        $product->setData(
            SubscriptionOrderHelper::SUBSCRIPTION_PROFILE_ID_FIELD_NAME,
            $item['profile_id']
        );

        $product->setData(SubscriptionOrderHelper::IS_STOCK_POINT_PROFILE, true);
        $product->setData(ProductCart::IS_SIMULATOR_PROFILE_ITEM_KEY, true);
        $product->setData(
            ProductCart::PROFILE_STOCK_POINT_DISCOUNT_RATE_KEY,
            $item['stock_point_discount_rate']
        );
    }

    /**
     * Get product include tax
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return float|int
     */
    public function getProductInclTax($product)
    {
        if ($taxAttribute = $product->getCustomAttribute('tax_class_id')) {
            $productRateId = $taxAttribute->getValue();
            $rate = $this->taxCalculation->getCalculatedRate($productRateId);
            if ((int) $this->scopeConfig->getValue(
                'tax/calculation/price_include_tax',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) === 1
            ) {
                $priceExclTax = $product->getPrice() / (1 + ($rate / 100));
            } else {
                $priceExclTax = $product->getPrice();
            }
            $priceInclTax = $priceExclTax + ($priceExclTax * ($rate / 100));

            return $priceInclTax;
        }
        return 0;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int $qty
     * @return float
     */
    public function getRenderPrice(\Magento\Catalog\Api\Data\ProductInterface $product, $qty)
    {
        $amount = $this->subHelperData->getProductPriceInProfileEditPage($product, $qty);
        return $this->calculationTool->round($amount);
    }

    /**
     * Get price before discount, If eligible with tierPrice will return tierPrice
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int $qty
     * @return float
     */
    public function getRegularPrice(\Magento\Catalog\Api\Data\ProductInterface $product, $qty)
    {
        if ($product->getTierPrice($qty) != $product->getTierPrice(1)) {
            $amount = $this->subHelperData->getProductPriceInProfileEditPage($product, $qty);
            return $amount;
        }

        $regularPrice = $this->calculationTool->round(
            $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue()
        );
        return $regularPrice;
    }

    /**
     * SubProfileCartOrder
     *
     * @param bool $reload
     * @param bool $nextDeliveryDate
     *
     * @return array
     */
    public function getProfileProductCart($reload = true, $nextDeliveryDate = null)
    {
        $result = [];
        if ($reload) {
            /** $productCatCollection = $this->cartFactory->create()->getCollection();
            $productCatCollection->addFieldToFilter("profile_id", $versionId);*/

            // get list by latest profile version
            $productCatSearch = $this->profileRepository->getListProductCart($this->getId());
            $productCatCollection = $productCatSearch->getItems();
        } else {
            $productCatCollection = $this->getData('product_cart');
        }

        $simulateData = $this->getData('simulateData');
        foreach ($productCatCollection as $i => $item) {
            if ($item['parent_item_id'] != 0) {
                continue; //product is child bundle
            }            $product = $this->getProductInProfile($item);
            if (!$product) {
                continue; //product is offline
            }
            if ($this->isStockPointProfile()) {
                $nextDeliveryDate = $item['original_delivery_date'];
                $deliverySlotName = $this->getDeliverySlotName($item['original_delivery_time_slot']);
                $deliverySlotId = (int)($item['original_delivery_time_slot'] ? $item['original_delivery_time_slot'] : -1);
            } else {
                $nextDeliveryDate = $item['delivery_date'];
                $deliverySlotName = $this->getDeliverySlotName($item['delivery_time_slot']);
                $deliverySlotId = (int)($item['delivery_time_slot'] ? $item['delivery_time_slot'] : -1);
            }

            $result[] = [
                'CartID' => (int)$item['cart_id'],
                'NextDeliveryDate' => $nextDeliveryDate,
                'NextDeliverySlotName' => $deliverySlotName,
                'NextDeliverySlotID' => $deliverySlotId,
                'CurrentSelectedShippingAddress' => $this->getAddressData($item['shipping_address_id']),
                'CurrentSelectedBillingAddress' => $this->getAddressData($item['billing_address_id']),
                'DeliveryTypeName' => $this->_getDeliveryTypeName($item['product_id']),
                'SubProfileCartProduct' => $product,
                'Discount' => $this->calculationTool->round($simulateData['Discount']),
                'ShippingFee' => $this->calculationTool->round($simulateData['ShippingFee']),
                'PaymentMethodFee' => $this->calculationTool->round($simulateData['PaymentMethodFee']),
                'WrappingFee' => $this->calculationTool->round($simulateData['WrappingFee']),
                'TotalAmount' => $this->calculationTool->round($simulateData['TotalAmount'])
            ];
        }

        return $result;
    }

    protected function _getDeliveryTypeName($productId)
    {
        $productObject = $this->collectionProduct->create()
            ->addAttributeToSelect('delivery_type')
            ->addAttributeToFilter('entity_id', ['eq' => $productId ])
            ->setPageSize(1)
            ->getFirstItem();
        return $productObject->getDeliveryType();
    }

    protected function _getNextDeliverySlotID($cartId)
    {
        $itemModel = $this->cartFactory->create()->load($cartId);
        return $itemModel->getNextDeliverySlotID();
    }

    /**
     * Get customer address data by id
     *
     * @param int $addressId
     *
     * @return mixed
     */
    public function getAddressData($addressId)
    {
        /** @var \Magento\Customer\Model\Address $address */
        $address = $this->addressFactory->create()->load($addressId);
        if (!$address->getId()) {
            return new \stdClass();
        }

        $nickname = '';
        if ($address->getCustomAttribute('riki_nickname')) {
            $nickname = $address->getCustomAttribute('riki_nickname')->getValue();
        }

        $firstnameKatakana = '';
        if ($address->getCustomAttribute('firstnamekana')) {
            $firstnameKatakana = $address->getCustomAttribute('firstnamekana')->getValue();
        }

        $lastnameKatakana = '';
        if ($address->getCustomAttribute('lastnamekana')) {
            $lastnameKatakana = $address->getCustomAttribute('lastnamekana')->getValue();
        }

        $type = '';
        if ($address->getCustomAttribute('riki_type_address')) {
            $type = __($address->getCustomAttribute('riki_type_address')->getValue());
        }

        $data = [
            'AddressID' => (int)$address->getId(),
            'NickName' => $nickname,
            'AddressType' => $type,
            'CountryID' => $address->getCountryId(),
            'RegionID' => $address->getRegionId(),
            'RegionCode' => $address->getRegionCode(),
            'Region' => $address->getRegion(),
            'City' => $address->getCity(),
            'Street' => $address->getStreet(),
            'Firstname' => $address->getFirstname(),
            'Lastname' => $address->getLastname(),
            'FirstNameKana' => $firstnameKatakana,
            'LastNameKana' => $lastnameKatakana,
            'Telephone' => $address->getTelephone(),
            'Postcode' => $address->getPostcode()
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function setProfileProductCart($string)
    {
        return $this->setData(self::PROFILE_PRODUCT_CART, $string);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->_getData(self::STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProfileCartOrder()
    {
        return $this->cartOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubProfileCartOrder(array $subProfileCartOrder = null)
    {
        $this->cartOrder = $subProfileCartOrder;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProfileID()
    {
        return $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setSubProfileID($id)
    {
        return $this->setId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getChangeType()
    {
        return $this->getData('change_type');
    }

    /**
     * {@inheritdoc}
     */
    public function setChangeType($type)
    {
        return $this->setData('change_type', $type);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastUpdate()
    {
        return $this->getData('updated_date');
    }

    /**
     * @return int
     */
    public function getProfileId()
    {
        return $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setLastUpdate($date)
    {
        return $this->setData('updated_date', $date);
    }

    /**
     * {@inheritdoc}
     */
    public function setConsumerCustomerID($id)
    {
        return $this->setData('consumer_db_id', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumerCustomerID()
    {
        return $this->getData('consumer_db_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProfileFrequencyID()
    {
        $frequencyId = $this->frequencyFactory->create()->getResource()->getIdByData(
            $this->getFrequencyUnit(),
            $this->getFrequencyInterval()
        );

        return $frequencyId;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubProfileFrequencyID($id)
    {
        $frequency = $this->frequencyFactory->create()->load($id);
        $this->setData('frequency_unit', $frequency->getFrequencyUnit());
        $this->setData('frequency_interval', $frequency->getFrequencyInterval());
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubscriptionCourse()
    {
        if (empty($this->currentSubscriptionCourse)) {
            $this->currentSubscriptionCourse = $this->courseFactory->create()->load($this->getCourseId());
        }
        return $this->currentSubscriptionCourse;
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer()
    {
        if (empty($this->currentCustomer)) {
            try {
                $this->currentCustomer = $this->customerRepository->getById($this->getCustomerId());
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        }

        return $this->currentCustomer;
    }

    /**
     * This profile in time sent order
     */
    public function isInStage()
    {
        $nextOrderDateTs = strtotime($this->getData('next_order_date')); // In locale time
        $nextDeliveryDateTs = strtotime($this->getData('next_delivery_date')); // In locale time

        $currentDateWithTimeZone = $this->timezoneHelper->date()->format("Y-m-d H:i:s");
        $currentDateWithTimeZoneTs = strtotime($currentDateWithTimeZone);

        if ($currentDateWithTimeZoneTs >= $nextOrderDateTs && $currentDateWithTimeZoneTs <= $nextDeliveryDateTs) {
            return true;
        }
        if ($nextDeliveryDateTs <= $currentDateWithTimeZoneTs) {
            return true;
        }

        return false;
    }

    /**
     * @return array|mixed
     */
    public function getListPaymentMethodAvailable()
    {
        if (isset($this->simpleStorage['listPaymentMethod'])) {
            return $this->simpleStorage['listPaymentMethod'];
        }

        $customerData = $this->getCustomer();
        $customerGroupOfProfile = $customerData->getGroupId();

        $courseModel = $this->getSubscriptionCourse();

        if (empty($courseModel->getData('course_id'))) {
            return [];
        }
        $arrAllPaymentMethod = $courseModel->getAllowPaymentMethod();
        $shoshaBusinessCode = $customerData->getCustomAttribute('shosha_business_code');

        if (in_array(InvoicedBasedPayment::PAYMENT_CODE, $arrAllPaymentMethod) &&
            ($shoshaBusinessCode == null ||
                (is_object($shoshaBusinessCode) && $shoshaBusinessCode->getValue() == null)
            )
        ) {
            $arrAllPaymentMethod = array_diff($arrAllPaymentMethod, [InvoicedBasedPayment::PAYMENT_CODE]);
        }

        //check payment for delay payment
        $isProfileDelay = false;
        $profileOrderTimes = $this->getData('order_times');
        $lastOrderTimeIsDelayPayment = intval($courseModel->getData('last_order_time_is_delay_payment'));
        if ($courseModel->isDelayPayment()) {
            if ($lastOrderTimeIsDelayPayment == 0) {
                $isProfileDelay = true;
            } else if (intval($profileOrderTimes) < $lastOrderTimeIsDelayPayment){
                $isProfileDelay = true;
            }
        }

        if ($isProfileDelay) {
            if (in_array(\Bluecom\Paygent\Model\Paygent::CODE, $arrAllPaymentMethod)) {
                $arrAllPaymentMethod = array_intersect($arrAllPaymentMethod, [\Bluecom\Paygent\Model\Paygent::CODE]);
            }
        }
        $paymentFeeModel = $this->paymentFeeFactory->create()->getCollection();
        $paymentFeeModel->addFieldToFilter('payment_code', $arrAllPaymentMethod);

        $arrAllPaymentMethodEdited = [];
        $allowsPaymentMethod = $this->getAllowedSelectMethods();

        foreach ($paymentFeeModel->getData() as $paymentFeeInfo) {
            $paymentMethod = $paymentFeeInfo['payment_code'];

            if ($this->paymentCustomerHelper->getStatus($paymentMethod) == 0) {
                continue;
            }

            $dataCustomerGroups = $this->paymentCustomerHelper->toArrayCustomerGroup(
                $this->paymentCustomerHelper->getCustomerGroup($paymentMethod)
            );

            if (! in_array($customerGroupOfProfile, $dataCustomerGroups)) {
                continue;
            }

            if ((!empty($allowsPaymentMethod) && in_array($paymentFeeInfo['payment_code'], $allowsPaymentMethod))
             || $paymentFeeInfo['payment_code'] == $this->getPaymentMethod()) {
                $paymentMethodInfo['disabled'] = false;
            } else {
                $paymentMethodInfo['disabled'] = true;
            }

            $paymentMethodInfo['value'] = $paymentFeeInfo['payment_code'];
            $paymentMethodInfo['label'] = $paymentFeeInfo['payment_name'];
            $paymentMethodInfo['params'] = [
                'price' => $paymentFeeInfo['fixed_amount']
            ];

            $arrAllPaymentMethodEdited[] = $paymentMethodInfo;
        }

        $this->simpleStorage['listPaymentMethod'] = $arrAllPaymentMethodEdited;

        return $this->simpleStorage['listPaymentMethod'];
    }

    /**
     * @return array|mixed
     */
    protected function getAllowedSelectMethods()
    {
        $currentPaymentMethod = $this->getPaymentMethod();

        if(null === $currentPaymentMethod) {
            return [
                Paygent::CODE,
                Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE,
                NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE,
                CvsPayment::PAYMENT_METHOD_CVS_CODE,
                InvoicedBasedPayment::PAYMENT_CODE
            ];
        }

        $allowMethods = [
            Paygent::CODE => [
                Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE,
                NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
            ],
            Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE => [
                NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE,
                Paygent::CODE
            ],
            NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE => [
                Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE,
                Paygent::CODE
            ],
            CvsPayment::PAYMENT_METHOD_CVS_CODE => [],
            InvoicedBasedPayment::PAYMENT_CODE => []
        ];
        if (isset($allowMethods[$currentPaymentMethod])) {
            return $allowMethods[$currentPaymentMethod];
        } else {
            return [];
        }
    }

    /**
     * When when:
     * a. Frequency change
     * b. Payment change
     * c. address change
     * d. delivery change
     * f. slot name change
     * g. qty change
     * h. plan change
     * i. skip next deliver change
     * k. Number of item of product cart change
     */
    public function isRealChange()
    {
        return true;
    }

    public function replaceProduct($oldId, $newId)
    {
        return $this->getResource()->replaceProduct($oldId, $newId);
    }

    public function deleteProfileProduct($productId)
    {
        return $this->getResource()->deleteProfileProduct($productId);
    }

    public function sendNotificationEmailReplaceProduct($oldId, $oldName, $newName)
    {
        // get the customers have profiles are updated
        $profileCustomers = $this->getResource()->getCustomersHaveProduct($oldId);

        if (!$profileCustomers) {
            return [];//nobody need to send email
        }
        // group email by customer
        $customerProfiles = [];
        $customerIds = [];
        foreach ($profileCustomers as $profileCustomer) {
            $customerIds[] = $profileCustomer['customer_id'];
            $customerProfiles[$profileCustomer['customer_id']][] = $profileCustomer['profile_id'];
        }

        $customers = $this->customerCollection->addFieldToFilter('entity_id', ['in' => $customerIds]);
        $customerEmails = [];

        /* Email : Subscription discontinue product */
        $applyMonth = $this->timezoneHelper->date()->format("m");
        $applyDay = $this->timezoneHelper->date()->format("d");
        $applyDate = $this->timezoneHelper->date()->format("Y/m/d");
        foreach ($customers as $customer) {
            $profiles = $customerProfiles[$customer->getId()];
            $varEmailTemplate = [];
            $varEmailTemplate['customer_name'] = $customer->getName();
            $varEmailTemplate['customer_first_name'] = $customer->getFirstname();
            $varEmailTemplate['customer_last_name'] = $customer->getLastname();
            $varEmailTemplate['apply_month'] = $applyMonth;
            $varEmailTemplate['apply_day'] = $applyDay;
            $varEmailTemplate['apply_date'] = $applyDate;
            $varEmailTemplate['customer_last_name'] = $customer->getLastname();
            $varEmailTemplate['subscription_profile_ids'] = $profiles;
            $varEmailTemplate['discontinued_product'] = $oldName;
            $varEmailTemplate['replacement_product'] = $newName;
            $varEmailTemplate['emailReceiver'] = $customer->getEmail();
            $this->profileEmail->sendEmailNotificationDiscontinuedProduct($varEmailTemplate);

            $customerEmails[] = $customer->getEmail();
        }

        return $customerEmails;
    }

    public function sendNotificationEmailDeleteProduct($profileList, $productName)
    {
        // get the customers have profiles are updated
        $profileCustomers = $this->getResource()->getCustomerByProfileList($profileList);

        if (!$profileCustomers) {
            return [];//nobody need to send email
        }
        // group email by customer
        $customerProfiles = [];
        $customerIds = [];

        foreach ($profileCustomers as $profileCustomer) {
            $customerIds[] = $profileCustomer['customer_id'];
            $customerProfiles[$profileCustomer['customer_id']][] = $profileCustomer['profile_id'];
        }

        $customers = $this->customerCollection->addFieldToFilter(
            'entity_id',
            ['in' => $customerIds]
        );

        $customerEmails = [];

        /* Email : Subscription discontinue product */
        $applyMonth  = $this->timezoneHelper->date()->format("m");
        $applyDay    = $this->timezoneHelper->date()->format("d");
        $currentDate = $this->timezoneHelper->date()->format("Y/m/d");

        foreach ($customers as $customer) {
            $profiles = $customerProfiles[$customer->getId()];
            $varEmailTemplate = [];
            $varEmailTemplate['customer_first_name'] = $customer->getFirstname();
            $varEmailTemplate['customer_last_name'] = $customer->getLastname();
            $varEmailTemplate['apply_month'] = $applyMonth;
            $varEmailTemplate['apply_day'] = $applyDay;
            $varEmailTemplate['current_date'] = $currentDate;
            $varEmailTemplate['discontinued_product'] = $productName;
            $varEmailTemplate['emailReceiver'] = $customer->getEmail();
            $this->profileEmail->sendEmailNotificationDiscontinuedProduct($varEmailTemplate, false);

            $customerEmails[] = $customer->getEmail();
        }

        return $customerEmails;
    }

    public function getDataModel()
    {
        $profileData = $this->getData();
        $profileDataObject = $this->profileDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $profileDataObject,
            $profileData,
            \Riki\Subscription\Api\Data\ApiProfileInterface::class
        );
        return $profileDataObject;
    }

    /**
     * Get delivery slot name
     *
     * @param int $id
     *
     * @return string
     */
    public function getDeliverySlotName($id)
    {
        $slot = $this->timeSlotsFactory->create()->load($id);
        if ($slot->getId()) {
            return $slot->getSlotName();
        }

        return '';
    }

    public function load($modelId, $field = null, $isOriginal = false)
    {
        $mainProfileId = $modelId;
        if (!$isOriginal) {
            $versionModel = $this->versionFactory->create()->getCollection();
            $versionModel->addFieldToFilter('rollback_id', $mainProfileId);
            $versionModel->addFieldToFilter('status', true);
            $versionModel->setOrder('id', 'DESC');
            if ($versionModel->getSize() > 0) {
                $modelId = $versionModel->setPageSize(1)->getFirstItem()->getData('moved_to');
            }
        }
        $object = parent::load($modelId, $field);
        if (!$isOriginal and $object->getId()) {
            $object->setData('profile_id', $mainProfileId);
        }
        return $object;
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function canDisengage()
    {
        return true;
    }

    /**
     * @param string|int $reasonId
     * @param null $areaCode
     * @param null $customerName
     * @return $this
     * @throws LocalizedException
     */
    public function disengage($reasonId, $areaCode = null, $customerName = null)
    {
        if ($this->canDisengage()) {
            try {
                $this->helperProfile->expiredVersion($this->getId());
            } catch (\Exception $e) {
                $this->_logger->error(__('Expire profile #%1 version get error: %2', $this->getId(), $e->getMessage()));
            }

            /*disengaged Date*/
            $disengagedDate = $this->date->gmtDate('Y-m-d H:i:s');

            /*disengaged user*/
            if (!$areaCode) {
                $disengagedUser = $this->authSession->getUser()->getUserName();
            } else {
                $disengagedUser = $customerName;
                $this->setStatus(self::STATUS_DISABLED);
            }
            $this->setDisengagementDate($disengagedDate)
                ->setDisengagementReason($reasonId)
                ->setDisengagementUser($disengagedUser)
                ->setCreateOrderFlag(0)
                ->setStockPointProfileBucketId(null)
                ->setStockPointDeliveryType(null)
                ->setStockPointDeliveryInformation(null)
                ->setSpecifiedWarehouseId(null)
                ->save();
            /*disengage linked profile*/
            if ($this->getType() == self::SUBSCRIPTION_TYPE_TMP) {
                $linkedProfile = $this->helperProfile->getProfileMainByProfileTmpId($this->getId());
            } else {
                 $linkedProfile = $this->helperProfile->getTmpProfileModel($this->getProfileId());
            }
            if ($linkedProfile) {
                $linkedProfile->setDisengagementDate($disengagedDate);
                $linkedProfile->setDisengagementReason($reasonId);
                $linkedProfile->setDisengagementUser($disengagedUser);
                $linkedProfile->setCreateOrderFlag(0);
                $linkedProfile->setStockPointProfileBucketId(null);
                $linkedProfile->setStockPointDeliveryType(null);
                $linkedProfile->setStockPointDeliveryInformation(null);
                $linkedProfile->setSpecifiedWarehouseId(null);
                $linkedProfile->setStatus(self::STATUS_DISABLED);

                try {
                    $linkedProfile->save();
                } catch (\Exception $e) {
                    $this->_logger->error('Disengagement main profile error: %1', $e->getMessage());
                }
                $mainProfileId = $linkedProfile->getId();
                if ($versionProfileId = $this->helperProfile->checkProfileHaveVersion($mainProfileId)) {
                    $this->helperProfile->expiredVersion($mainProfileId);
                }
            }

            try {
                $this->disengagementEmailSender->setProfile($this)->send();
            } catch (\Exception $e) {
                $this->_logger->error('Disengagement profile send mail error: %1', $e->getMessage());
            }

            $this->_eventManager->dispatch('subscription_profile_requested_disengage_after', [
                'profile' => $this
            ]);
        }

        return $this;
    }

    /**
     * check profile is waiting to disengaged?
     *
     * @return bool
     */
    public function isWaitingToDisengaged()
    {
        return $this->getDisengagementDate()
        && $this->getDisengagementReason()
        && $this->getDisengagementUser()
        && $this->getStatus() == 1;
    }

    /**
     * @return \Magento\Framework\DataObject[]
     */
    public function getOrders()
    {
        if (empty($this->orders)) {
            /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection */
            $collection = $this->orderCollectionFactory->create();
            $collection->addFieldToFilter('subscription_profile_id', $this->getId());
            $collection->addAttributeToSort('created_at', 'desc');

            $this->orders = $collection->getItems();
        }

        return $this->orders;
    }

    /**
     * @param int $profileId
     * @return array
     */
    public function getAdditionalInfoOfSubscription($profileId)
    {
        $orderCollection = $this->getOrdersOfProfile($profileId);
        $canceledOrders = [];
        $activeOrder = [];
        $allOfOrders = [];
        $waitingOOS = [];
        /*get list order*/
        foreach ($orderCollection as $order)
        {
            if ($order->getState() == Order::STATE_CANCELED && ($order->getStatus() == OrderStatus::STATUS_ORDER_CANCELED || $order->getStatus() == OrderStatus::STATUS_ORDER_PROCESSING_CANCELED))
            {
                $canceledOrders[] = $order->getEntityId();
            } else {
                $activeOrder[$order->getEntityId()] = $order->getEntityId();
            }
            $allOfOrders[$order->getEntityId()] = $order->getIncrementId();
        }
        /*get OOS orders and exclude them*/
        $outOfStockProducts = $this->getOOSOrders($profileId);
        $orderWaitsOOS = [];
        if ($outOfStockProducts)
        {
            if (isset($outOfStockProducts['generated_order_id']))
            {
                $activeOrder = array_diff($activeOrder, (array)$outOfStockProducts['generated_order_id']);
                $canceledOrders = array_diff($canceledOrders, (array)$outOfStockProducts['generated_order_id']);
            }
            if (isset($outOfStockProducts['waiting_for_oos']))
            {
                $orderWaitsOOS = $outOfStockProducts['waiting_for_oos'];
            }
        }

        /*get full RMAs and exclude them*/
        $fullReturnOrders = $this->getFullReturnOrders(array_keys($activeOrder));
        $orderHasFullRMA = [];
        if ($fullReturnOrders)
        {
            foreach ($fullReturnOrders as $rma)
            {
                if (in_array($rma->getData('order_id'),$activeOrder)) {
                    unset($activeOrder[$rma->getData('order_id')]);
                }
                $orderHasFullRMA[] = $rma->getData('order_id');
            }
        }
        $data = [];
        /*field delivery time of subscription*/
        $data['delivery_time'] = count($activeOrder);
        /*field waiting for oos*/
        foreach ($orderWaitsOOS as $orderId => $value) {
            $waitingOOS[$value] = $allOfOrders[$value];
        }
        $data['waiting_for_oos'] =  $waitingOOS;
         /*field time of canceled*/
        $timeOfCanceled = array_unique(array_merge($canceledOrders, $orderHasFullRMA));
        $data['time_of_canceled'] =  count($timeOfCanceled);
        return $data;
    }

    /**
     * @return DataObject
     */
    public function getTheLastGeneratedOrder()
    {
        $orders = $this->getOrders();

        if (!empty($orders)) {
            return array_shift($orders);
        }

        return new DataObject();
    }

    /**
     * Get simulate data from database
     *
     * @return array
     */
    public function getSimulateDataFromCache()
    {
        $simulateData = [];

        $data = $this->profileIndexer->loadSimulateDataByProfileId($this->getId());
        if ($data && $data['data_serialized']) {
            $data = \Zend\Serializer\Serializer::unserialize($data['data_serialized']);
            $simulateData = [
                'Discount' => $data['discount'],
                'ShippingFee' => $data['shipping_fee'],
                'PaymentMethodFee' => $data['payment_method_fee'],
                'WrappingFee' => $data['wrapping_fee'],
                'TotalAmount' => $data['total_amount']
            ];
        }

        return $simulateData;
    }

    /**
     * @return $this|\Magento\Framework\Model\AbstractModel
     * @throws \Exception
     */
    public function afterSave()
    {
        parent::afterSave();

        if ($this->dataHasChangedFor('next_order_date')
            && ($this->getData('next_order_date') < $this->getOrigData('next_order_date')
                || $this->getData('next_order_date') < date('Y-m-d')
            )
        ) {

            /** @var \Riki\Framework\Helper\Logger\Monolog $logger */
            $logger = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Riki\Framework\Helper\Logger\LoggerBuilder::class)
                ->setName('Riki_Ned327')
                ->setFileName('ned327.log')
                ->pushHandlerByAlias(\Riki\Framework\Helper\Logger\LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();

            $logger->critical(new LocalizedException(__(
                'Profile #%1 next delivery date has been changed from %2 to %3',
                $this->getId(),
                $this->getOrigData('next_order_date'),
                $this->getData('next_order_date')
            )));
        }

        $this->loggerStateProfile->infoProfile($this);

        if ($this->getData('reindex_cache_profile')) {
            // invalid cache and remove data in table subscription profile simulate cache
            $this->resourceModelIndexer->removeCacheInvalid($this->getId());
            // need to remove for case update on schedule
            $this->profileSimulatorProcessor->reindexRow($this->getId());
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();

        if ($this->isHolidayProfile()) {
            $this->loggerStateProfile->infoHolidayProfile($this);
            //Recalculate Next Order Date in case holiday
            $this->reCalNextOrderDate();
        }

        /*push message main profile for reindex in here*/
        if ((!$this->getId() || !$this->getReindexFlag()) &&
            !$this->getType() && !$this->_registry->registry('reindex_cache_profile')) {
            $this->setReindexFlag(true);
            $this->setData('reindex_cache_profile', true);
        }

        return $this;
    }

    /**
     * Profile will be used stock point address for shipping address
     *
     * @return bool
     */
    public function isUsedStockPointAddress()
    {
        if (!$this->getData("stock_point_profile_bucket_id")) {
            return false;
        }

        if (empty($this->getData("stock_point_delivery_type")) ||
            ($this->getData("stock_point_delivery_type") != self::PICKUP) &&
                $this->getData("stock_point_delivery_type") != self::LOCKER) {
            return false;
        }

        return true;
    }

    /**
     * profile is used stock point method
     *
     * @return bool
     */
    public function isStockPointProfile()
    {
        if (!$this->getData("stock_point_profile_bucket_id")) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isHolidayProfile()
    {
        return $this->getNextOrderDate()
            && $this->loggerStateProfile->isHoliday($this->getNextOrderDate())
            && strtotime($this->getOrigData('created_date')) > strtotime(LoggerStateProfile::SUBSCRIPTION_CREATED_DATE);
    }

    /**
     * Recalculate Next Order Date in case holiday
     */
    public function reCalNextOrderDate()
    {
        $profileId = $this->getProfileId();
        if ($profileId) {
            $nextDeliveryDate = $this->getData('next_delivery_date');
            $region = $this->helperProfile->getAddressArrOfProfile($profileId, 'region_id');
            $productIds = $this->helperProfile->getProductSubscriptionProfile($profileId);
            if ($nextDeliveryDate && $region && $productIds) {
                $nextOrderDate = $this->helperProfile->calNextOrderDate($nextDeliveryDate, $region, $productIds, $this->helperProfile->getExcludeBufferDays($profileId));
                $this->setData('next_order_date', $nextOrderDate);
            }
        }
    }

    /**
     * Get customer subscription profile by customer id exclude Hanpukai and course code
     *
     * @param int $customerId
     * @param array $courseIds
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getCustomerSubscriptionProfileExcludeCourseCode($customerId, $courseIds)
    {
        $profileCollection = $this->getCustomerSubscriptionProfileExcludeHanpukai($customerId);

        if (!empty($courseIds)) {
            $profileCollection->addFieldToFilter('main_table.course_id', ['nin' => $courseIds]);
        }

        return $profileCollection;
    }

    /**
     * reset order property
     */
    public function resetOrders()
    {
        $this->orders = null;
    }

    /**
     * @param int $profileId
     * @return array|\Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getOrdersOfProfile($profileId)
    {
        if (!$profileId)
        {
            return [];
        }
        $orders = $this->orderCollectionFactory->create()
            ->addFieldToFilter('subscription_profile_id', $profileId)
            ->addFieldToSelect(['entity_id','increment_id','status','state'])
            ->load();
        return $orders->getItems();
    }

    /**
     * @param int $profileId
     * @return DataObject[]
     */
    public function getOOSOrders($profileId)
    {
        if (!$profileId)
        {
            return [];
        }
        $mappingOrders = $this->outOfStockCollectionFactory->create()
            ->addFieldToFilter('subscription_profile_id', $profileId)
            ->getItems();
        $oosProducts = [];
        foreach ($mappingOrders as $order)
        {
            $oosProducts['original_order_id'][] = $order->getData('original_order_id');
            $oosProducts['generated_order_id'][] = $order->getData('generated_order_id');
            if (!$order->getData('generated_order_id')) {
                $oosProducts['waiting_for_oos'][] = $order->getData('original_order_id');
            }
        }
        return $oosProducts;
    }

    /**
     * @param array $orderIds
     * @return DataObject[]
     */
    public function getFullReturnOrders($orderIds)
    {
        if (!$orderIds)
        {
            return [];
        }
        $collection = $this->rmaCollectionFactory->create()
            ->addFieldToSelect('order_id')
            ->addFieldToFilter('order_id', ['in', $orderIds])
            ->addFieldToFilter('full_partial', '1')
            ->load();
        return $collection->getItems();
    }
}
