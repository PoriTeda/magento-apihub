<?php

namespace Riki\SubscriptionCourse\Test\Unit\Model\ResourceModel;

use Bluecom\Paygent\Model\ConfigProvider;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Setup\Exception;
use Riki\Subscription\Api\Data\ProfileInterfaceFactory as ProfileDataFactory;
use Riki\Subscription\Api\Data\ProfileSearchResultsInterfaceFactory;
use Riki\Subscription\Api\WebApi\ProfileRepositoryInterface;
use Riki\Subscription\Model\Profile\Profile as ProfileModel;
use Riki\Subscription\Model\Profile\ProfileFactory as ProfileFactory;
use Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection as ProfileCollection;
use Riki\Subscription\Model\Profile\ResourceModel\Profile as ProfileResource;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Riki\SubscriptionFrequency\Model\FrequencyFactory;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory as AddressCollectionFactory;
use Riki\TimeSlots\Model\TimeSlotsFactory;
use Magento\Catalog\Model\ProductFactory;
use Riki\Subscription\Model\ProductCart\ProductCartFactory;
use Riki\Subscription\Helper\CalculateDeliveryDate;
use Magento\Catalog\Model\CategoryFactory;
use Riki\SubscriptionCourse\Helper\Data as SubscriptionCourseHelper;
use Magento\Framework\DataObject;
use Riki\Subscription\Helper\Profile\Data as HelperProfile;
use \Riki\Subscription\Model\Profile\Profile;
use Riki\Subscription\Model\Constant;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Backend\Model\Auth\Session as Session;
use Magento\Customer\Model\CustomerFactory as CustomerFactory;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;
use Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\CollectionFactory as ProfileLinkCollectionFactory;
use Magento\Customer\Model\Address as AddressModel;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Magento\Framework\App\CacheInterface;
use Riki\Subscription\Helper\Data as SubscriptionHelperData;

class ProfileRepositoryTest extends \PHPUnit\Framework\TestCase
{
    const EDIT_REGISTRATION_SHIPPING_ADDRESS_ID = 'edit_registration_shipping_address_id';
    /*CONST for setting email dsiabled or removed */
    const CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_ENABLE = 'subcreateorder/disabledorremoved/enable';
    const CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_TEMPLATE = 'subcreateorder/disabledorremoved/email_template';
    const CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_FROM = 'subcreateorder/disabledorremoved/sender';
    const CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_TO = 'subcreateorder/disabledorremoved/receiver';
    const CONFIG_CHANGE_PROFILE_EMAIL_ENABLE = 'subscriptioncourse/subscriptionprofileedit/enable';
    const MAX_SALE_QTY = 10000;

    /**
     * @var $originProductCartData array
     */
    private $originProductCartData = [];

    /**
     * @var ProfileSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var ProfileDataFactory
     */
    protected $profileDataFactory;

    /**
     * @var ProfileResource
     */
    protected $resource;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var TimezoneInterface
     */
    protected $date;

    protected $courseFactory;

    /**
     * @var FrequencyFactory
     */
    protected $frequencyFactory;

    /**
     * @var AddressCollectionFactory
     */
    protected $addressCollectionFactory;

    /**
     * @var TimeSlotsFactory
     */
    protected $timeSlotsFactory;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ProductCartFactory
     */
    protected $productCartFactory;

    /**
     * @var $helperProfile
     */
    protected $helperProfile;

    /**
     * @var \Riki\Subscription\Model\Version\VersionFactory
     */
    protected $versionFactory;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\CollectionFactory
     */
    protected $profileLinkCollectionFactory;

    /**
     * @var \Riki\Subscription\Helper\Order\Email
     */
    protected $emailOrderBuilder;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course\Source\Payment
     */
    protected $paymentModel;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    /**
     * Contains list error validation message errors
     *
     * @var array
     */
    protected $returnMessages = [];

    /**
     * Contains list error validation message errors
     *
     * @var array
     */
    protected $invalidProducts = [];

    /**
     * @var AddressModel
     */
    protected $addressModel;

    /**
     * @var \Riki\Subscription\Helper\Profile\Email
     */
    protected $emailProfileHelper;

    /**
     * @var CaseDisplay
     */
    protected $case;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @var SubscriptionCourseHelper
     */
    protected $subCourseHelper;

    protected $calculateDeliveryDate;

    protected $cache;

    /**
     * @var SubscriptionHelperData
     */
    protected $subscriptionHelperData;

    /**
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface
     */
    protected $adjustmentCalculator;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $design;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     * @var \Riki\Sales\Helper\Email
     */
    protected $emailSaleHelper;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Riki\ThirdPartyImportExport\Model\ExportNextDelivery\ProfileItemFactory
     */
    protected $profileItemFactory;

    /**
     * @var \Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemsInterface
     */
    protected $profileItemBuilder;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;

    protected $profileRepository;

    /**
     * @var \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper
     */
    protected $deliveryDateGenerateHelper;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $objectFactory;

    protected $maxSaleQty;

    /**
     * @var \Riki\AdvancedInventory\Helper\Inventory
     */
    protected $helperInventory;

    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    protected $stockPointHelper;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var TimezoneInterface
     */
    protected $stdTimezone ;

    /**
     * @var \Riki\StockPoint\Logger\StockPointLogger
     */
    protected $stockPointLogger;
    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subscriptionHelperOrder;

    /**
     * @var \Riki\Subscription\Model\Multiple\Category\CampaignFactory
     */
    protected $campaignFactory;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * @var \Riki\Subscription\Model\ProfileCacheRepository
     */
    protected $profileCacheRepository;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * @var \Riki\Subscription\Logger\LoggerStateProfile
     */
    protected $loggerStateProfile;

    /**
     * @var \Riki\Subscription\Model\Profile\WebApi\ProfileRepository
     */
    protected $object;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    public function setUp()
    {
        $this->resource = $this->getMockBuilder('Riki\Subscription\Model\Profile\ResourceModel\Profile')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->stockPointLogger = $this->getMockBuilder('Riki\StockPoint\Logger\StockPointLogger')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->messageManager = $this->getMockBuilder('Magento\Framework\Message\ManagerInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->stockPointHelper = $this->getMockBuilder('Riki\Subscription\Helper\StockPoint\Data')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->validateStockPointProduct = $this->getMockBuilder('Riki\StockPoint\Helper\ValidateStockPointProduct')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->profileRepository = $this->getMockBuilder('Riki\Subscription\Model\Profile\ProfileRepository')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->eventManager = $this->getMockBuilder('Magento\Framework\Event\Manager')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->registry = $this->getMockBuilder('Magento\Framework\Registry')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->design = $this->getMockBuilder('Magento\Framework\View\DesignInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->storeManager = $this->getMockBuilder('Magento\Store\Model\StoreManagerInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->localeFormat = $this->getMockBuilder('Magento\Framework\Locale\FormatInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->priceCurrency = $this->getMockBuilder('Magento\Framework\Pricing\PriceCurrencyInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->adjustmentCalculator = $this->getMockBuilder('Magento\Framework\Pricing\Adjustment\CalculatorInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->subscriptionHelperData = $this->getMockBuilder('Riki\Subscription\Helper\Data')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->cache = $this->getMockBuilder('Magento\Framework\App\CacheInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->case = $this->getMockBuilder('Riki\CreateProductAttributes\Model\Product\CaseDisplay')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->emailProfileHelper = $this->getMockBuilder('Riki\Subscription\Helper\Profile\Email')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->addressModel = $this->getMockBuilder('Magento\Customer\Model\Address')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->simulator = $this->getMockBuilder('Riki\Subscription\Helper\Order\Simulator')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->paymentModel = $this->getMockBuilder('Riki\SubscriptionCourse\Model\Course\Source\Payment')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->profileLinkCollectionFactory = $this->getMockBuilder('Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\CollectionFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->searchResultsFactory = $this->getMockBuilder('Riki\Subscription\Api\Data\ProfileSearchResultsInterfaceFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->profileFactory = $this->getMockBuilder('Riki\Subscription\Model\Profile\ProfileFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->profileDataFactory = $this->getMockBuilder('Riki\Subscription\Api\Data\ProfileInterfaceFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->resource = $this->getMockBuilder('Riki\Subscription\Model\Profile\ResourceModel\Profile')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->filterBuilder = $this->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->searchCriteriaBuilder = $this->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->date = $this->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;;
        $this->courseFactory = $this->getMockBuilder('Riki\SubscriptionCourse\Model\CourseFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->frequencyFactory = $this->getMockBuilder('Riki\SubscriptionFrequency\Model\FrequencyFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->addressCollectionFactory = $this->getMockBuilder('Magento\Customer\Model\ResourceModel\Address\CollectionFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->timeSlotsFactory = $this->getMockBuilder('Riki\TimeSlots\Model\TimeSlotsFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->productFactory = $this->getMockBuilder('Magento\Catalog\Model\ProductFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->productCartFactory = $this->getMockBuilder('Riki\Subscription\Model\ProductCart\ProductCartFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->calculateDeliveryDate = $this->getMockBuilder('Riki\Subscription\Helper\CalculateDeliveryDate')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->categoryFactory = $this->getMockBuilder('Magento\Catalog\Model\CategoryFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->subCourseHelper = $this->getMockBuilder('Riki\SubscriptionCourse\Helper\Data')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->helperProfile = $this->getMockBuilder('Riki\Subscription\Helper\Profile\Data')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->versionFactory = $this->getMockBuilder('Riki\Subscription\Model\Version\VersionFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->customerSession = $this->getMockBuilder('Magento\Customer\Model\Session')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->session = $this->getMockBuilder('Magento\Backend\Model\Auth\Session')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->customerFactory = $this->getMockBuilder('Magento\Customer\Model\CustomerFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->productRepository = $this->getMockBuilder('Magento\Catalog\Api\ProductRepositoryInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->scopeConfig = $this->getMockBuilder('Magento\Framework\App\Config\ScopeConfigInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->emailOrderBuilder = $this->getMockBuilder('Riki\Subscription\Model\Profile\Order\ProfileEmailOrderFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->emailSaleHelper = $this->getMockBuilder('Riki\Sales\Helper\Email')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->orderRepository = $this->getMockBuilder('Magento\Sales\Model\OrderRepository')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->deliveryDateGenerateHelper = $this->getMockBuilder('Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->profileItemBuilder = $this->getMockBuilder('Riki\ThirdPartyImportExport\Api\ExportNextDelivery\ProfileItemsInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->profileItemFactory = $this->getMockBuilder('Riki\ThirdPartyImportExport\Model\ExportNextDelivery\ProfileItemFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->publisher = $this->getMockBuilder('Magento\Framework\MessageQueue\PublisherInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->objectFactory = $this->getMockBuilder('Magento\Framework\DataObjectFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->helperInventory = $this->getMockBuilder('Magento\Framework\App\ResourceConnection')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->stdTimezone = $this->getMockBuilder('Magento\Framework\Stdlib\DateTime\TimezoneInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->profileCacheRepository = $this->getMockBuilder('Riki\Subscription\Model\ProfileCacheRepository')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->subscriptionHelperOrder = $this->getMockBuilder('Riki\Subscription\Helper\Order')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->campaignFactory = $this->getMockBuilder('Riki\Subscription\Model\Multiple\Category\CampaignFactory')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->subscriptionValidator = $this->getMockBuilder('Riki\Subscription\Api\Data\ValidatorInterface')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;
        $this->loggerStateProfile = $this->getMockBuilder('Riki\Subscription\Logger\LoggerStateProfile')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();;

        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->object = $this->objectManager->getObject(\Riki\Subscription\Model\Profile\WebApi\ProfileRepository::class);
    }

    public function testInfoStateProfileWhenProfileIdIsEmpty()
    {
        $profileIdMock = null;
        $messageMock = "mock message";
        $data = "test";

        $result = $this->object->infoStateProfile($profileIdMock, $messageMock, $data);
        $this->assertEquals($this->object, $result);
    }

    public function testInfoStateProfileWhenProfileIdIsNotEmpty()
    {
        $profileIdMock = 123456789;
        $messageMock = "mock message";
        $data = "test";

        $result = $this->object->infoStateProfile($profileIdMock, $messageMock, $data);
        $this->assertEquals($this->object, $result);
    }

    public function testInfoStateProfileWhenProfileIdIsNotEmptyAndMessageIsNotString()
    {
        $profileIdMock = 123456789;
        $messageMock = 123345679;
        $data = "test";

        $result = $this->object->infoStateProfile($profileIdMock, $messageMock, $data);
        $this->assertEquals($this->object, $result);
    }
}