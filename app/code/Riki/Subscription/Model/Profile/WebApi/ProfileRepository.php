<?php
namespace Riki\Subscription\Model\Profile\WebApi;

use Bluecom\Paygent\Model\ConfigProvider;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
use Riki\SubscriptionCourse\Model\CourseFactory;
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
use Riki\Subscription\Model\Landing\PageFactory as LandingPageFactory;

class ProfileRepository implements ProfileRepositoryInterface
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

    /**
     * @var CourseFactory
     */
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
     * @var LandingPageFactory
     */
    protected $landingPageFactory;

    /**
     * ProfileRepository constructor.
     * @param LandingPageFactory $landingPageFactory
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\DesignInterface $designInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Locale\FormatInterface $formatInterface
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrencyInterface
     * @param \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculatorInterface
     * @param SubscriptionHelperData $subscriptionHelperData
     * @param ProfileLinkCollectionFactory $profileLinkCollectionFactory
     * @param ProfileSearchResultsInterfaceFactory $searchResultsFactory
     * @param ProfileFactory $profileFactory
     * @param ProfileDataFactory $profileDataFactory
     * @param ProfileResource $resource
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TimezoneInterface $date
     * @param CourseFactory $courseFactory
     * @param FrequencyFactory $frequencyFactory
     * @param AddressCollectionFactory $addressCollectionFactory
     * @param TimeSlotsFactory $timeSlotsFactory
     * @param ProductFactory $productFactory
     * @param ProductCartFactory $productCartFactory
     * @param CalculateDeliveryDate $calculateDeliveryDate
     * @param CategoryFactory $categoryFactory
     * @param SubscriptionCourseHelper $subCourseHelper
     * @param HelperProfile $helperProfile
     * @param \Riki\Subscription\Model\Version\VersionFactory $versionFactory
     * @param CustomerSession $customerSession
     * @param Session $session
     * @param CustomerFactory $customerFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Riki\Subscription\Helper\Order\Email $subEmail
     * @param \Riki\SubscriptionCourse\Model\Course\Source\Payment $paymentModel
     * @param AddressModel $addressModel
     * @param \Riki\Subscription\Helper\Profile\Email $emailProfileHelper
     * @param CaseDisplay $case
     * @param Session $authSession
     * @param CacheInterface $cache
     * @param \Riki\Sales\Helper\Email $emailSaleHelper
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Riki\Subscription\Api\GenerateOrder\ProfileEmailBuilderInterface $profileItemBuilder
     * @param \Riki\Subscription\Model\Profile\Order\ProfileEmailOrderFactory $profileItemFactory
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper
     * @param \Magento\Framework\DataObjectFactory $objectFactory
     * @param \Riki\AdvancedInventory\Helper\Inventory $helperInventory
     * @param \Riki\Subscription\Helper\StockPoint\Data $stockPointHelperData
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param TimezoneInterface $stdTimezone
     * @param \Riki\StockPoint\Logger\StockPointLogger $stockPointLogger
     * @param \Riki\Subscription\Helper\Order $subscriptionHelperOrder
     * @param \Riki\Subscription\Model\Multiple\Category\CampaignFactory $campaignFactory
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
     */
    public function __construct(
        LandingPageFactory $landingPageFactory,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\DesignInterface $designInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Locale\FormatInterface $formatInterface,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrencyInterface,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $calculatorInterface,
        SubscriptionHelperData $subscriptionHelperData,
        ProfileLinkCollectionFactory $profileLinkCollectionFactory,
        ProfileSearchResultsInterfaceFactory $searchResultsFactory,
        ProfileFactory $profileFactory,
        ProfileDataFactory $profileDataFactory,
        ProfileResource $resource,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TimezoneInterface $date,
        CourseFactory $courseFactory,
        FrequencyFactory $frequencyFactory,
        AddressCollectionFactory $addressCollectionFactory,
        TimeSlotsFactory $timeSlotsFactory,
        ProductFactory $productFactory,
        ProductCartFactory $productCartFactory,
        CalculateDeliveryDate $calculateDeliveryDate,
        CategoryFactory $categoryFactory,
        SubscriptionCourseHelper $subCourseHelper,
        HelperProfile $helperProfile,
        \Riki\Subscription\Model\Version\VersionFactory $versionFactory,
        CustomerSession $customerSession,
        Session $session,
        CustomerFactory $customerFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\Subscription\Helper\Order\Email $subEmail,
        \Riki\SubscriptionCourse\Model\Course\Source\Payment $paymentModel,
        AddressModel $addressModel,
        \Riki\Subscription\Helper\Profile\Email $emailProfileHelper,
        CaseDisplay $case,
        \Magento\Backend\Model\Auth\Session $authSession,
        CacheInterface $cache,
        \Riki\Sales\Helper\Email $emailSaleHelper,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Riki\Subscription\Api\GenerateOrder\ProfileEmailBuilderInterface $profileItemBuilder,
        \Riki\Subscription\Model\Profile\Order\ProfileEmailOrderFactory $profileItemFactory,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper,
        \Magento\Framework\DataObjectFactory $objectFactory,
        \Riki\AdvancedInventory\Helper\Inventory $helperInventory,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointHelperData,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $stdTimezone,
        \Riki\StockPoint\Logger\StockPointLogger $stockPointLogger,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\Subscription\Helper\Order $subscriptionHelperOrder,
        \Riki\Subscription\Model\Multiple\Category\CampaignFactory $campaignFactory,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository,
        \Riki\Subscription\Logger\LoggerStateProfile $loggerStateProfile
    ) {
        $this->stockPointLogger = $stockPointLogger;
        $this->messageManager = $messageManager;
        $this->stockPointHelper = $stockPointHelperData;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->profileRepository = $profileRepository;
        $this->eventManager = $eventManager;
        $this->registry = $registry;
        $this->design = $designInterface;
        $this->storeManager = $storeManagerInterface;
        $this->localeFormat = $formatInterface;
        $this->priceCurrency = $priceCurrencyInterface;
        $this->adjustmentCalculator = $calculatorInterface;
        $this->subscriptionHelperData = $subscriptionHelperData;
        $this->cache = $cache;
        $this->case = $case;
        $this->emailProfileHelper = $emailProfileHelper;
        $this->addressModel = $addressModel;
        $this->simulator = $simulator;
        $this->paymentModel = $paymentModel;
        $this->profileLinkCollectionFactory = $profileLinkCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->profileFactory = $profileFactory;
        $this->profileDataFactory = $profileDataFactory;
        $this->resource = $resource;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->date = $date;
        $this->courseFactory = $courseFactory;
        $this->frequencyFactory = $frequencyFactory;
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->timeSlotsFactory = $timeSlotsFactory;
        $this->productFactory = $productFactory;
        $this->productCartFactory = $productCartFactory;
        $this->calculateDeliveryDate = $calculateDeliveryDate;
        $this->categoryFactory = $categoryFactory;
        $this->subCourseHelper = $subCourseHelper;
        $this->helperProfile = $helperProfile;
        $this->versionFactory = $versionFactory;
        $this->customerSession = $customerSession;
        $this->session = $session;
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->emailOrderBuilder = $subEmail;
        $this->emailSaleHelper = $emailSaleHelper;
        $this->orderRepository = $orderRepository;
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        $this->authSession = $authSession;
        $this->profileItemBuilder = $profileItemBuilder;
        $this->profileItemFactory = $profileItemFactory;
        $this->publisher = $publisher;
        $this->objectFactory = $objectFactory;
        $this->helperInventory = $helperInventory;
        $this->stdTimezone = $stdTimezone;
        $this->profileCacheRepository = $profileCacheRepository;
        $this->subscriptionHelperOrder = $subscriptionHelperOrder;
        $this->campaignFactory = $campaignFactory;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->loggerStateProfile = $loggerStateProfile;
        $this->landingPageFactory = $landingPageFactory;
    }

    /**
     * Get max sale qty
     *
     * @return int|mixed
     */
    public function getMaxSaleQty()
    {
        if (empty($this->maxSaleQty)) {
            $this->maxSaleQty = $this->scopeConfig->getValue(
                'cataloginventory/item_options/max_sale_qty',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            if (empty($this->maxSaleQty)) {
                $this->maxSaleQty = self::MAX_SALE_QTY;
            }
        }
        return $this->maxSaleQty;
    }

    /**
     * {@inheritdoc}
     */
    public function getNextDate($profileId)
    {
        $errorCode = '00';
        $errorMsg = __('Valid');
        $returnData = null;

        if (!$profileId) {
            $errorCode = '40';
            $errorMsg = __('Requested profile doesn\'t exist');
        } else {
            $profile = $this->profileFactory->create()->load($profileId);
            if ($profile->getId()) {
                $from = $this->date->date()->createFromFormat('Y-m-d', $profile->getNextDeliveryDate());
                $returnData = $from->format('Y-m-d');
            } else {
                $errorCode = '01';
                $errorMsg = __('Invalid');
            }
        }
        return [
            'ReturnMessage' => [
                [
                    'ReturnCode' => $errorCode,
                    'ReturnText' => $errorMsg
                ]
            ],
            'NextDate' => $returnData
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDateRange($profileId)
    {
        $errorCode = '00';
        $errorMsg = __('Valid');
        $fromDate = null;
        $toDate = null;

        $slots = [];
        $timeSlots = $this->timeSlotsFactory->create()->getCollection()
            ->setOrder('position', 'ASC');
        foreach ($timeSlots as $slot) {
            $slots[] = [
                'TimeSlotID' => (int)$slot->getId(),
                'TimeSlotName' => $slot->getSlotName()
            ];
        }

        if (!$profileId) {
            $errorCode = '40';
            $errorMsg = __('Requested profile doesn\'t exist');
        } else {
            $profile = $this->profileFactory->create()->load($profileId);
            if ($profile->getId()) {
                $fromDate = $this->getAvailableStartDate($profile);
                $toDate = $this->getAvailableEndDate($profile);

                if ($toDate->getTimestamp() < $fromDate->getTimestamp()) {
                    $calendarPeriodForEdit = $this->calculateDeliveryDate->getEditProfileCalendarPeriod() ?: 0;
                    $toDate = clone $fromDate;
                    $toDate->add(new \DateInterval(sprintf('P%sD', $calendarPeriodForEdit)));
                }
            } else {
                $errorCode = '01';
                $errorMsg = __('Invalid');
            }
        }
        return [
            'ReturnMessage' => [
                [
                    'ReturnCode' => $errorCode,
                    'ReturnText' => $errorMsg
                ]
            ],
            'FromDate' => $fromDate->format('Y-m-d'),
            'ToDate' => $toDate->format('Y-m-d'),
            'TimeSlot' => $slots
        ];
    }

    public function getDateAvailableFrom($profileId)
    {
        $maxDay = $this->countCalendarChecking($profileId);
        if ($maxDay) {
            $result = $this->date->date()->modify('+'.$maxDay.' days');
        } else {
            $result = $this->date->date();
        }
        return $result->format('Y-m-d');
    }

    public function getDateAvailableTo($profileId)
    {
        $_checkCalendar = $this->countCalendarChecking($profileId);
        $calendarPeriod = $this->calculateDeliveryDate->getCalendarPeriod();
        if (!$calendarPeriod) {
            //set default 30days
            $calendarPeriod = 29;
        } else {
            $calendarPeriod = (int)$calendarPeriod + $_checkCalendar - 1;
        }
        if ($calendarPeriod) {
            $result = $this->date->date()->modify('+' . $calendarPeriod . ' days');
        } else {
            $result = $this->date->date();
        }
        return $result->format('Y-m-d');
    }

    /**
     * @param $profileId
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param $fromDate
     * @return string
     */
    public function getDateAvailableToNew($profileId, $profile, $fromDate)
    {
        if ($profile->getFrequencyInterval() && $profile->getFrequencyUnit()) {
            $ddate = $this->date->date($profile->getData('next_delivery_date'));
            $fromDateObj = $this->date->date($fromDate);
            if ($ddate < $fromDateObj) {
                // something was wrong
                return $this->getDateAvailableTo($profileId);
            }

            $result = $ddate->modify(
                '+' . $profile->getFrequencyInterval() . ' ' . $profile->getFrequencyUnit()
            );

            if ($result) {
                $result = $result->modify('-1 days');
                return $result->format('Y-m-d');
            }
        }

        return $this->getDateAvailableTo($profileId);
    }

    /**
     * @param $profile
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param $fromDate
     * @return \DateTime
     */
    public function getAvailableStartDate($profile)
    {
        $checkCalendar = [];
        $listProducts = $this->productCartIdGroupByAddress($profile->getId());
        $subsciptionCourse = $this->getCourse($profile);
        $excludeBufferDays = $subsciptionCourse->getData('exclude_buffer_days');
        $bufferDay = null;
        if ($excludeBufferDays) {
            $bufferDay = 0;
        }
        foreach ($listProducts as $key => $value) {
            $checkCalendar = $this->calculateDeliveryDate->getCalendar($key, $value, null, $bufferDay);
            break;
        }

        $fromDate = $this->calculateAvailableStartDate($checkCalendar, $profile);

        return $fromDate;
    }

    /**
     * @param $profile
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param $toDate
     * @return \DateTime
     */
    public function getAvailableEndDate($profile)
    {
        $currentDate = $this->date->date()->format('Y-m-d');
        $lastDeliveryDate = $this->getLastDeliveryDate($profile);
        $calendarPeriod = $this->calculateDeliveryDate->getEditProfileCalendarPeriod() ?: 0;
        $maxCalendarPeriod = $this->calculateDeliveryDate->getMaximumEditProfileCalendarPeriod();
        $maxAvailableDate = strtotime($maxCalendarPeriod . " month", strtotime($lastDeliveryDate));
        $maxDateTimestamp = strtotime($currentDate);
        if (strtotime($currentDate) < $maxAvailableDate) {
            $maxDateTimestamp = strtotime($calendarPeriod . " day", $maxDateTimestamp);
            if ($maxDateTimestamp > $maxAvailableDate) {
                $maxDateTimestamp = $maxAvailableDate;
            }
        }
        $courseSettings = $this->getCourseSetting($profile);

        $hanpukaiAllowChangeDeliveryDate = $courseSettings['hanpukai_delivery_date_allowed'];
        $hanpukaiDeliveryDateTo = $courseSettings['hanpukai_delivery_date_to'];
        if ($hanpukaiAllowChangeDeliveryDate) {
            $maxDateTimestamp = strtotime($hanpukaiDeliveryDateTo);
        }

        $toDate = $this->date->scopeDate(null, date('Y-m-d', $maxDateTimestamp));
        return $toDate;
    }

    /**
     * get Available start date for calendar edit profile
     *
     * @param $checkCalendar
     * @return \DateTime
     */
    public function calculateAvailableStartDate($checkCalendar, $profile)
    {
        $startDate = time();
        foreach ($checkCalendar as $date) {
            if (strtotime($date) > $startDate) {
                $startDate = strtotime($date);
            }
        }
        $fromDate = $this->stdTimezone->scopeDate(null, date('Y-m-d', $startDate + 86400));

        $courseSettings = $this->getCourseSetting($profile);
        $hanpukaiAllowChangeDeliveryDate = isset($courseSettings['hanpukai_delivery_date_allowed']) ?
            $courseSettings['hanpukai_delivery_date_allowed']: false;
        $hanpukaiDeliveryDateFrom = isset($courseSettings['hanpukai_delivery_date_from']) ?
            $courseSettings['hanpukai_delivery_date_from']: false;
        if ($hanpukaiAllowChangeDeliveryDate && strtotime($hanpukaiDeliveryDateFrom) > $startDate) {
            $fromDate = $this->stdTimezone->scopeDate(null, date('Y-m-d', strtotime($hanpukaiDeliveryDateFrom)));
        }

        return $fromDate;
    }

    /**
     * @return array
     */
    public function getCourseSetting($profile)
    {
        $courseId = $profile->getCourseId();

        $objCourse = $this->subCourseHelper->loadCourse($courseId);

        if (empty($objCourse) || empty($objCourse->getId())) {
            return [];
        }

        return $objCourse->getSettings();
    }

    /**
     * @return array
     */
    public function getCourse($profile)
    {
        $courseId = $profile->getCourseId();

        $objCourse = $this->subCourseHelper->loadCourse($courseId);

        if (empty($objCourse) || empty($objCourse->getId())) {
            return [];
        }

        return $objCourse;
    }

    /**
     * @param $profile
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param $lastDeliveryDate
     * @return \DateTime
     */
    public function getLastDeliveryDate($profile)
    {
        $orderTimes = $profile->getData('order_times');

        if ($profile->getData('type') == 'tmp') {
            $orderTimes--;
        }

        $lastDeliveryDate = $this->helperProfile->getLastOrderDeliveryDateOfProfile(
            $profile->getProfileId(),
            $orderTimes
        );
        if ($lastDeliveryDate === null) {
            if ($profile->getData('type') == 'tmp') {
                $lastDeliveryDate = $profile->getData('next_delivery_date');
            } else {
                $lastDeliveryDate = date(
                    'Y-m-d',
                    strtotime(
                        '-' . $profile->getData('frequency_interval')
                        . " "
                        . $profile->getData('frequency_unit'),
                        $this->date->scopeTimeStamp()
                    )
                );
            }
        } elseif ($profile->getData('type') == 'tmp') {
            $lastDeliveryDate = date(
                'Y-m-d',
                strtotime(
                    $profile->getData('frequency_interval')
                    . " "
                    . $profile->getData('frequency_unit'),
                    strtotime($lastDeliveryDate)
                )
            );
        }

        return $lastDeliveryDate;
    }

    /**
     * return list of days:
     * ex: ["2016-08-02","2016-08-03","2016-08-04","2016-08-05"]
     * FromDate = now + count($_checkCalendar)
     * find largest day of the address book can be choose
     */
    protected function countCalendarChecking($profileId)
    {
        $_checkCalendar = [];
        $listProducts = $this->productCartIdGroupByAddress($profileId);
        foreach ($listProducts as $key => $value) {
            $_checkCalendar[] = $this->calculateDeliveryDate->getCalendar($key, $value);
        }

        $maxDay = 0;
        if ($_checkCalendar) {
            $maxDay = max(array_map(function ($calendar) {
                return count($calendar);
            }, $_checkCalendar));
        }

        return $maxDay;
    }

    /**
     * {@inheritdoc}
     */
    public function load($id)
    {
        $profile = $this->profileFactory->create();
        $this->resource->load($profile, $id);
        if (!$profile->getId()) {
            throw new NoSuchEntityException(__('Requested profile doesn\'t exist'));
        }
        return $profile;
    }

    /**
     * {@inheritdoc}
     */
    public function get($consumerId)
    {
        $errorCode = '00';
        $errorMsg = __('Valid');
        $returnData = null;
        $count = 0;

        if (!$consumerId) {
            $errorCode = '10';
            $errorMsg = __('The requested customer doesn\'t exist');
        } else {
            // get customer by consumer ID
            $customer = $this->customerFactory->create()->getCollection()
                ->addAttributeToFilter('consumer_db_id', $consumerId)
                ->setPageSize(1)
                ->getFirstItem();

            if (!$customer->getId()) {
                $errorCode = '10';
                $errorMsg = __('The requested customer doesn\'t exist');
                return [
                    'ReturnMessage' => [
                        [
                            'ReturnCode' => $errorCode,
                            'ReturnText' => $errorMsg
                        ]
                    ],
                    'SubProfileList' => $returnData
                ];
            } else {
                $dateTimeNow = $this->stdTimezone->date()->format('Y-m-d');
                $profileSearch = $this->searchCriteriaBuilder
                    ->addFilter('customer_id', $customer->getId(), 'eq')
                    ->addFilter('status', 1, 'eq')
                    ->addFilter('type', new \Zend_Db_Expr('NULL'), 'is')
                    ->addFilter('next_order_date', $dateTimeNow, 'gt')
                    ->create();

                $profiles = $this->getList($profileSearch);

                if ($profiles->getTotalCount()) {
                    $returnData = [];
                    $profiles = $profiles->getItems();
                    foreach ($profiles as $profile) {
                        $course = $this->getCourseById($profile->getCourseId());

                        // API doesn't use Hanpukai
                        if ($course->getSubscriptionType() == CourseType::TYPE_HANPUKAI) {
                            continue;
                        }

                        $count++;
                        // get the newest version profile
                        $profile = $this->getProfileById($profile->getId());

                        $profile->setConsumerCustomerID($consumerId);
                        $returnData[] = $this->convertSearchResultToDataItemsArray($profile);
                    }
                } else {
                    $errorCode = '01';
                    $errorMsg = __('Invalid');
                }
            }
        }

        if ($count == 0) {
            $errorCode = '01';
            $errorMsg = __('Invalid');
            $returnData = null;
        }

        return [
            'ReturnMessage' => [
                [
                    'ReturnCode' => $errorCode,
                    'ReturnText' => $errorMsg
                ]
            ],
            'SubProfileList' => $returnData
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var ProfileCollection $collection */
        $collection = $this->profileFactory->create()->getCollection();

        $collection->join(
            ['c' => 'subscription_course'],
            'main_table.course_id = c.course_id',
            ['course_name']
        );
        $collection->getSelect()->joinLeft(
            ['profile_link' => 'subscription_profile_link'],
            'main_table.profile_id  = profile_link.profile_id',
            []
        );
        $collection->getSelect()->where('profile_link.profile_id IS NULL');

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        $this->applySearchCriteriaToCollection($searchCriteria, $collection);

        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    protected function convertCollectionToDataItemsArray(ProfileCollection $collection)
    {
        $profiles = array_map(function (ProfileModel $profile) {
            /** @var ExampleInterface $dataObject */
            $dataObject = $this->profileDataFactory->create();
            $dataObject->setId($profile->getId());
            $dataObject->setCustomerId($profile->getCustomerId());
            $dataObject->setSubProfileID('abc');
            return $dataObject;
        }, $collection->getItems());

        return $profiles;
    }

    /**
     * API Profile Object Returned
     *
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param bool $reload
     * @param array $cartData
     * @param bool $skipNextDelivery
     *
     * @return array
     */
    protected function convertSearchResultToDataItemsArray(
        $profile,
        $reload = true,
        $cartData = null,
        $skipNextDelivery = false
    ) {
        // register for catalog-rule
        $this->registry->unregister(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID);
        $this->registry->register(
            \Riki\Subscription\Model\Constant::RIKI_COURSE_ID,
            $profile->getCourseId()
        );
        $this->registry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);
        $this->registry->register(
            \Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID,
            $profile->getSubProfileFrequencyID()
        );
        $this->registry->unregister('subscription_profile_obj');
        $this->registry->register('subscription_profile_obj', $profile);

        $simulateData = [];
        $course = $profile->getSubscriptionCourse();

        $nextDeliveryDate =  $profile->getData('next_delivery_date');

        // get profile from DB - get simulate from cache first
        if (!$cartData) {
            $simulateData = $profile->getSimulateDataFromCache();
        }
        // if have not cache - simulate data
        if ($cartData || !$simulateData) {
            $simulatorOrder = null;

            try {
                $simulatorOrder = $this->simulator->createMageOrderForAPI(
                    $profile->getId(),
                    $cartData,
                    $profile->getCustomer()
                );
            } catch (LocalizedException $e) {
                $this->logger->info($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }

            if ($simulatorOrder) {
                $simulateData = [
                    'Discount' => $simulatorOrder->getDiscountAmount(),
                    'ShippingFee' => $simulatorOrder->getShippingInclTax(),
                    'PaymentMethodFee' => $simulatorOrder->getFee(),
                    'WrappingFee' => $simulatorOrder->getData('gw_items_base_price_incl_tax'),
                    'TotalAmount' => $simulatorOrder->getGrandTotal()
                ];
            } else {
                $simulateData = [
                    'Discount' => 0,
                    'ShippingFee' => 0,
                    'PaymentMethodFee' => 0,
                    'WrappingFee' => 0,
                    'TotalAmount' => 0
                ];
            }

            if (isset($cartData[0]['delivery_date'])
                && $cartData[0]['delivery_date'] != $profile->getData('next_delivery_date')
            ) {
                $nextDeliveryDate =  $cartData[0]['delivery_date'];
            }
        }

        if ($skipNextDelivery) {
            $intCurrentDate = strtotime($nextDeliveryDate);
            $nextDeliveryDate = $this->calNextDeliveryDate(
                $intCurrentDate,
                $profile->getFrequencyInterval(),
                $profile->getFrequencyUnit()
            );
        }

        $profile->setData('simulateData', $simulateData);

        $subProfileCartOrder = $this->_mergeShipmentByDeliveryType(
            $profile->getProfileProductCart(
                $reload,
                $nextDeliveryDate
            )
        );

        $returnData = [
            'SubProfileID' => (int)$profile->getId(),
            'ConsumerCustomerID' => $profile->getConsumercustomerID(),
            'SubCourseName' => $course->getCourseName(),
            'LastUpdate' => $profile->getUpdatedDate(),
            'SubProfileFrequencyUnit' => $profile->getFrequencyUnit(),
            'SubProfileFrequencyID' => (int)$profile->getSubProfileFrequencyID(),
            'SubProfileFrequencyInterval' => (int)$profile->getFrequencyInterval(),
            'SubProfileDurationUnit' => $course->getDurationUnit(),
            'SubProfileDurationInterval' => (int)$course->getDurationInterval(),
            'PaymentMethodName' => __($this->paymentModel->getOptionName($profile->getPaymentMethod())),
            'SubCourseSetting' => array_merge($profile->getCourseSetting(), $profile->getAvailableCategory()),
            'SubProfileCartOrder' => $subProfileCartOrder
        ];
        return $returnData;
    }

    /**
     * Merge Cart/Shipment by Delivery Type
     *
     * @param array $cartItems
     * @return array
     */
    protected function _mergeShipment($cartItems)
    {
        $mergeItems = [];
        $deliveryTypes = [];
        $key = 0;
        foreach ($cartItems as $cart) {
            $delivery = $cart['DeliveryTypeName'];
            $products = $cart['SubProfileCartProduct'];

            if (!in_array($delivery, $deliveryTypes)) {
                $mergeItems[] = $cart;
                $deliveryTypes[] = $delivery;
                $key++;
            } else {
                $mergeItems[$key - 1]['SubProfileCartProduct'][] = $products[0];
                $mergeItems[$key - 1]['SubProfileCartProduct'] = $this->_uniqueMultipleProduct(
                    $mergeItems[$key - 1]['SubProfileCartProduct'],
                    'ProductID',
                    'ProductQty'
                );
            }
        }

        return $mergeItems;
    }

    /**
     * Merge Cart/Shipment by Delivery Type
     * Group by rule: CoolNormalDm, chilled, cosmetic, cold
     *
     * @param array $cartItems
     * @return array
     */
    protected function _mergeShipmentByDeliveryType($cartItems)
    {
        $mergeItems = [];
        $cartItemCold = null;
        $cartItemChilled = null;
        $cartItemCosmetic = null;
        $cartItemCoolNormalDm = null;
        $productCold = [];
        $productChilled = [];
        $productCosmetic = [];
        $productCoolNormalDm = [];
        $count = 0;
        foreach ($cartItems as $cart) {
            $delivery = $cart['DeliveryTypeName'];
            $product = $cart['SubProfileCartProduct'];

            switch ($delivery) {
                case 'cold':
                    $cartItemCold = $cart;
                    $productCold[] = $product;
                    break;
                case 'chilled':
                    $cartItemChilled = $cart;
                    $productChilled[] = $product;
                    break;
                case 'cosmetic':
                    $cartItemCosmetic = $cart;
                    $productCosmetic[] = $product;
                    break;
                default:
                    $cartItemCoolNormalDm = $cart;
                    $productCoolNormalDm[] = $product;
            }
        }

        if ($cartItemCoolNormalDm) {
            $cartItemCoolNormalDm['SubProfileCartProduct'] = $this->_uniqueMultipleProduct(
                $productCoolNormalDm,
                'ProductID',
                'ProductQty'
            );
            $cartItemCoolNormalDm['DeliveryTypeName'] = __('CoolNormalDm');
            $cartItemCoolNormalDm['CartID'] = $count;
            $count++;
            $mergeItems[] = $cartItemCoolNormalDm;
        }
        if ($cartItemChilled) {
            $cartItemChilled['SubProfileCartProduct'] = $this->_uniqueMultipleProduct(
                $productChilled,
                'ProductID',
                'ProductQty'
            );
            $cartItemChilled['DeliveryTypeName'] = __('chilled');
            $cartItemChilled['CartID'] = $count;
            $count++;
            $mergeItems[] = $cartItemChilled;
        }
        if ($cartItemCosmetic) {
            $cartItemCosmetic['SubProfileCartProduct'] = $this->_uniqueMultipleProduct(
                $productCosmetic,
                'ProductID',
                'ProductQty'
            );
            $cartItemCosmetic['DeliveryTypeName'] = __('cosmetic');
            $cartItemCosmetic['CartID'] = $count;
            $count++;
            $mergeItems[] = $cartItemCosmetic;
        }
        if ($cartItemCold) {
            $cartItemCold['SubProfileCartProduct'] = $this->_uniqueMultipleProduct(
                $productCold,
                'ProductID',
                'ProductQty'
            );
            $cartItemCold['DeliveryTypeName'] = __('cold');
            $cartItemCold['CartID'] = $count;
            $mergeItems[] = $cartItemCold;
        }

        return $mergeItems;
    }

    protected function applySearchCriteriaToCollection(
        SearchCriteriaInterface $searchCriteria,
        ProfileCollection $collection
    ) {
        $this->applySearchCriteriaFiltersToCollection($searchCriteria, $collection);
        $this->applySearchCriteriaSortOrdersToCollection($searchCriteria, $collection);
        $this->applySearchCriteriaPagingToCollection($searchCriteria, $collection);
    }

    protected function applySearchCriteriaFiltersToCollection(
        SearchCriteriaInterface $searchCriteria,
        ProfileCollection $collection
    ) {
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }
    }

    protected function applySearchCriteriaSortOrdersToCollection(
        SearchCriteriaInterface $searchCriteria,
        ProfileCollection $collection
    ) {
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            $isAscending = $sortOrders->getDirection() == SearchCriteriaInterface::SORT_ASC;
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder($sortOrder->getField(), $isAscending ? 'ASC' : 'DESC');
            }
        }
    }

    protected function applySearchCriteriaPagingToCollection(
        SearchCriteriaInterface $searchCriteria,
        ProfileCollection $collection
    ) {
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
    }

    protected function addFilterGroupToCollection(
        FilterGroup $filterGroup,
        ProfileCollection $collection
    ) {
        $fields = [];
        $conditions = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $fields[] = $filter->getField();
            $conditions[] = [$condition => $filter->getValue()];
        }
        if ($fields) {
            $collection->addFieldToFilter($fields, $conditions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFrequency($profileId)
    {
        $errorCode = '00';
        $errorMsg = __('Valid');
        $returnData = null;
        $list = [];

        if (!$profileId) {
            $errorCode = '40';
            $errorMsg = __('Wrong input format');
        } else {
            $profile = $this->profileFactory->create()->load($profileId);
            if ($profile->getId()) {
                $course = $this->courseFactory->create()->load($profile->getCourseId());
                if ($course->getId()) {
                    $frequencies = $course->getFrequencyEntities();
                    foreach ($frequencies as $frequency) {
                        if (isset($frequency['frequency_id'])) {
                            $list[] = [
                                'frequencyID' => (int)$frequency['frequency_id'],
                                'frequencyInterval' => (int)$frequency['frequency_interval'],
                                'frequencyUnit' => $frequency['frequency_unit']
                            ];
                        }
                    }
                }
            } else {
                $errorCode = '01';
                $errorMsg = __('Invalid');
            }
        }

        return [
            'ReturnMessage' => [
                [
                    'ReturnCode' => $errorCode,
                    'ReturnText' => $errorMsg
                ]
            ],
            'frequency' => $list
        ];
    }

    /**
     * Set Return Message
     *
     * @param string $code
     * @param string $text
     * @param string[] $params
     */
    public function setReturnMessage($code, $text, $params = null)
    {
        if ($code == 0) {
            $this->returnMessages[] = [
                'ReturnCode' => '00',
                'ReturnText' => $text
            ];
        } else {
            $this->returnMessages[] = [
                'ReturnCode' => (string)$code,
                'ReturnText' => __($text),
                'ReturnParams' => $params
            ];
        }
    }

    /**
     * Group invalid products to show 1 mess only
     */
    public function _setInvalidProductsMessage()
    {
        if (!empty($this->invalidProducts)) {
            $msg = __('The product quantity is not valid');
            $uniqueProducts = $this->_uniqueMultidimArray($this->invalidProducts, 'id');
            $this->returnMessages[] = [
                'ReturnCode' => '10',
                'ReturnText' => $msg,
                'ReturnParams' => ['invalidProducts' => $uniqueProducts]
            ];
        }
    }

    protected function _uniqueMultidimArray($array, $key)
    {
        $temArray = [];
        $i = 0;
        $keyArray = [];
        foreach ($array as $val) {
            if (!in_array($val[$key], $keyArray)) {
                $keyArray[$i] = $val[$key];
                $temArray[$i] = $val;
            }
            $i++;
        }
        return $temArray;
    }

    protected function _uniqueMultipleProduct($array, $key, $qty)
    {
        $temArray = [];
        $i = 0;
        $keyArray = [];
        foreach ($array as $val) {
            if (isset($val[$key]) && !in_array($val[$key], $keyArray)) {
                $keyArray[$i] = $val[$key];
                $temArray[$i] = $val;
            } else {
                foreach ($temArray as $j => $existItem) {
                    if (isset($val[$key]) && $existItem[$key] == $val[$key]) {
                        $temArray[$j][$qty] += $val[$qty];
                    }
                }
            }
            $i++;
        }
        return $temArray;
    }

    /**
     * @return bool
     */
    public function isValidProfileUpdate()
    {
        if (!empty($this->returnMessages)) {
            return false;
        }

        return true;
    }

    /**
     * Process return message
     *
     * @param $changeType
     */
    protected function processMessageByType($changeType)
    {
        // with change type = 3 - we do not validate anymore
        if ($changeType == 3) {
            $this->cleanReturnMessage();
        }
    }

    public function cleanReturnMessage()
    {
        $this->returnMessages = [];
    }

    protected function _getOriginAddressId($profileId)
    {
        $productCartModel = $this->productCartFactory->create()->getCollection();
        $productCartModel->addFieldToFilter('profile_id', $profileId);
        foreach ($productCartModel as $product) {
            $addressId = $product->getCurrentSelectedShippingAddress();
            return $addressId;
        }
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($profile, $profileId, $isUpdate = false)
    {
        $profileData = $this->createObjectByData([]);
        $productCartData = [];
        $arrayAddress = [];
        $totalQty = 0;
        $productIds = [];
        $returnData = null;

        /** @var \Riki\Subscription\Model\Profile\Profile $profileOrigin */
        $profileOrigin = $this->profileFactory->create()->load($profileId);
        $course = $profileOrigin->getSubscriptionCourse();

        if (!$profileOrigin->getCourseId()) {
            $this->setReturnMessage(10, 'The requested profile doesn\'t exist');
            return ['ReturnMessage' => $this->returnMessages, 'ReturnProfileObject' => $returnData];
        }

        // Get product ids of profile origin.
        $productIdsOrigin = $this->getProductIdsFromProfileOrigin($profileOrigin, $profileId);

        $this->validateProfileFrequencyInterval($profile);

        if ($addressId = $this->_getOriginAddressId($profileId)) {
            $arrayAddress[] = $addressId;
        }

        $changeType = $profile->getChangeType();

        foreach ($profile->getSubProfileCartOrder() as $cart) {
            $this->_validateCartData($profileId, $profileOrigin, $cart);
            $ddate = $cart->getNextDeliveryDate();
            $this->validateNextDeliveryDateChanging($ddate, $course, $profileOrigin);
            $this->validateShippingAddressChanging(
                $addressId,
                $cart->getData('shipping_address_id'),
                $course,
                $profileOrigin
            );
            if ($cart->getSubProfileCartProducts()) {
                // address now get from origin only
                foreach ($cart->getSubProfileCartProducts() as $product) {
                    $id = $product->getProductID();
                    $qty = $product->getProductQty();

                    // Check qty of product
                    $this->_validateSingleProduct($id, $qty, $productIdsOrigin);
                    $this->validateProductChanging($id, $productIdsOrigin, $course);
                    $cartProduct = $this->_getProductCartData(
                        $id,
                        $cart,
                        $profileOrigin->getData('profile_id'),
                        $qty,
                        $addressId,
                        $ddate,
                        $cart->getData('shipping_address_id')
                    );
                    if ($cartProduct != false) {
                        $this->validateProductQtyChanging($profileId, $id, $cartProduct['qty'], $course);
                        $productCartData[] = $cartProduct;
                        $totalQty += $qty;
                        $productIds[$id] = $qty;
                    }
                }
            }
        }

        $this->_setInvalidProductsMessage();
        $this->_validateCartProduct($course, $totalQty, $productIds, $profileId, $productIdsOrigin);
        $this->_validateProfileData($profile, $profileOrigin, $course);
        $this->validateAmountRestriction($course, $profile, $productCartData, $profileOrigin);
        $this->validateMaximumQtyRestriction($productCartData, $profileId);
        // valid
        if ($this->isValidProfileUpdate()) {
            $this->setReturnMessage(0, 'Valid');
            $customer = $profileOrigin->getCustomer();

            $consumerId = $customer->getCustomAttribute('consumer_db_id')
                ? $customer->getCustomAttribute('consumer_db_id')->getValue()
                : null;

            $profileOrigin->setConsumerCustomerID($consumerId);

            $skipNextDelivery = false;

            if ($changeType != 3) {
                $profileOrigin->setSubProfileFrequencyID($profile->getSubProfileFrequencyID());
                $productCartData = $this->_uniqueMultipleProduct($productCartData, 'product_id', 'qty');
                $profileOrigin->setData('product_cart', $productCartData);
                $reload = false;
            } else {
                $reload = true;
                $skipNextDelivery = true;
            }

            if ($isUpdate) {
                $salesConnection = $profileOrigin->getResource()->getConnection();
                $salesConnection->beginTransaction();

                try {
                    $changeType = preg_replace('/\s+/', '', $changeType);
                    $resultCallApi = $this->removeStockPoint($profileOrigin);
                    if (!$resultCallApi) {
                        return ['ReturnMessage' => $this->returnMessages, 'ReturnProfileObject' => $returnData];
                    }
                    $profileData->setData($profileOrigin->getData());
                    // NED-638: Add course data to profile data to check attribute 'next_delivery_date_calculation_option'
                    // when save profile
                    $profileData->setData('course_data', $course->getData());
                    $this->save($profileData, 'type_' . $changeType, $arrayAddress, 'API');
                    // get new version profile
                    $profile = $this->profileFactory->create()->load($profileId);
                    $profile->setConsumerCustomerID($consumerId);
                    $returnData = $this->convertSearchResultToDataItemsArray($profile);
                    $salesConnection->commit();
                } catch (\Exception $e) {
                    $salesConnection->rollBack();
                    $this->stockPointLogger->info(
                        'Profile Id : ' . $profileId . ' ' . $e->getMessage() . '\n' . $e->getTraceAsString(),
                        ['type' => \Riki\StockPoint\Logger\StockPointLogger::LOG_TYPE_REMOVE_FROM_BUCKET]
                    );
                    $this->setReturnMessage(99, 'There are something wrong in the system. Please re-try again');
                    return ['ReturnMessage' => $this->returnMessages, 'ReturnProfileObject' => $returnData];
                }
            } else {
                $returnData = $this->convertSearchResultToDataItemsArray(
                    $profileOrigin,
                    $reload,
                    $productCartData,
                    $skipNextDelivery
                );
            }
            $returnData['SubCourseName'] = $course->getName();
        }

        return [
            'ReturnMessage' => $this->returnMessages,
            'ReturnProfileObject' => $returnData
        ];
    }

    /**
     * @param $profileOrigin
     * @return bool|void
     * @throws \Zend_Json_Exception
     */
    protected function removeStockPoint($profileOrigin)
    {
        if ($profileOrigin->isStockPointProfile()) {
            $mainProfileId = $profileOrigin->getProfileId();
            /** call api when remove stock point  */
            $resultApi = $this->stockPointHelper->removeFromBucket($mainProfileId);
            if (isset($resultApi['success']) && !$resultApi['success']) {
                $this->cleanReturnMessage();
                $message = isset($resultApi['message']) ? $resultApi['message'] : 'System error';
                $this->setReturnMessage(99, $message);
                return false;
            }
            $this->removeStockPointOfProfile($profileOrigin);
            $this->stockPointLogger->info(
                "Profile Id : " . $mainProfileId . " removed stock point by Watson API",
                ['type' => \Riki\StockPoint\Logger\StockPointLogger::LOG_TYPE_REMOVE_FROM_BUCKET]
            );
        }
        return true;
    }

    /**
     * Set data stock point is null for profile, tmp, and all product carts
     *
     * @param Profile $profile
     * @throws \Exception
     */
    public function removeStockPointOfProfile(\Riki\Subscription\Model\Profile\Profile $profile)
    {
        $this->resetDataStockPoint($profile);

        //update for temp
        $profileTmp = $this->helperProfile->getTmpProfileModel($profile->getProfileId());
        if ($profileTmp) {
            $this->resetDataStockPoint($profileTmp);
            $profileTmp->save();
            // update for cart
            $profileTmpCarts = $profileTmp->getProductCart();
            foreach ($profileTmpCarts as $profileCart) {
                $this->resetDiscountProductCart($profileCart);
            }
        }
    }

    /**
     * Set data stock point is null
     *
     * @param $profile
     */
    protected function resetDataStockPoint($profile)
    {
        $profile->setData('stock_point_profile_bucket_id', null);
        $profile->setData('stock_point_delivery_type', null);
        $profile->setData('stock_point_delivery_information', null);
        $profile->setData('original_delivery_date', null);
        $profile->setData('original_delivery_time_slot_id', null);
        $profile->setData('auto_stock_point_assign_status', 0);
    }

    /**
     * Set stock_point_discount_rate = 0  and save productCart
     *
     * @param \Riki\Subscription\Model\ProductCart\ProductCart $productCart
     * @throws \Exception
     */
    protected function resetDiscountProductCart(\Riki\Subscription\Model\ProductCart\ProductCart $productCart)
    {
        $productCart->setData('stock_point_discount_rate', 0);
        $productCart->save();
    }

    /**
     * validate Profile Frequency Interval
     *
     * @param $profile
     */
    protected function validateProfileFrequencyInterval($profile)
    {
        if (!$profile->getFrequencyInterval()) {
            $this->setReturnMessage(10, 'The selected frequency doesn\'t exist');
        }
    }

    /**
     * Check settings in course management
     *
     * @param \Riki\Subscription\Api\Data\ProfileInterface $profile   Data POST
     * @param \Riki\Subscription\Model\Profile\Profile $profileOrigin Data in Database
     * @param \Riki\SubscriptionCourse\Model\Course $course
     */
    protected function _validateProfileData($profile, $profileOrigin, $course)
    {
        /**
         * 1 = apply change just the next delivery
         * 2 = apply change to all future deliveries
         * 3 = skip next delivery
         */
        $changeType = $profile->getChangeType();
        if ($changeType) {
            if (!in_array($changeType, [1, 2, 3])) {
                $this->setReturnMessage(10, 'The change type is not valid');
            }
        }

        $inputDate = \DateTime::createFromFormat('Y-m-d H:i:s', $profile->getLastUpdate());
        if ($inputDate == false || array_sum($inputDate->getLastErrors())) {
            $this->setReturnMessage(10, 'The last date is invalid format (YYYY-MM-DD)');
        }

        $currentDate = \DateTime::createFromFormat('Y-m-d H:i:s', $profileOrigin->getLastUpdate());
        if ($inputDate < $currentDate) {
            $this->setReturnMessage(50, 'Exclusive access control error');
        }

        // settings
        if ($this->isValidProfileUpdate()) {
            if (!$course->getData("allow_skip_next_delivery") && $profile->getChangeType() == 3) {
                $this->setReturnMessage(10, 'The course setting does not allow skip next delivery');
            }
        }
    }

    /**
     * Validate Cart Data
     *
     * @param int $profileId
     * @param \Riki\Subscription\Model\Profile\Profile $obj
     * @param \Riki\Subscription\Api\WebApi\SubProfileCartOrderInterface $cart
     */
    protected function _validateCartData($profileId, $obj, $cart)
    {
        // next delivery date
        $ddate = $cart->getNextDeliveryDate();
        if ($ddate) {
            $validDate = \DateTime::createFromFormat('Y-m-d', $ddate);
            if ($validDate == false || array_sum($validDate->getLastErrors())) {
                $this->setReturnMessage(10, 'The next delivery date is invalid format (YYYY-MM-DD)');
            }

            $dateRange = $this->getDateRange($profileId);
            $fromDate = $dateRange['FromDate'];
            $toDate = $dateRange['ToDate'];

            if (!$this->checkInRange($fromDate, $toDate, $ddate)) {
                $this->setReturnMessage(10, 'Delivery date is out of the range.', [
                    'startDate' => $fromDate,
                    'toDate' => $toDate
                ]);
            }
        } else {
            $this->setReturnMessage(10, 'The next delivery date is invalid format (YYYY-MM-DD)');
        }
        $slotId = $cart->getNextDeliverySlotID();
        if ($slotId !== null && $slotId !== -1) {
            $slot = $this->timeSlotsFactory->create()->load($slotId);
            if (!$slot->getId()) {
                $this->setReturnMessage(10, 'The selected time slot doesn\'t exist');
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
     * @param string $profileId
     */
    protected function _validateCartProduct($course, $totalQty, $productIds, $profileId, $productIdsOrigin)
    {
        $mustSelectCartData = $course->getMustSelectSku();
        if ($mustSelectCartData) {
            $mustSelectCarts = explode(':', $mustSelectCartData);
            if (count($mustSelectCarts) == 2) {
                $mustSelectCartId = $mustSelectCarts[0];
                $mustSelectProductQty = $mustSelectCarts[1];
                $cate = $this->categoryFactory->create()->load($mustSelectCartId);
                if (!$this->subCourseHelper->isValidMustHaveQtyInCategory($productIds, $mustSelectCartData)) {
                    $this->setReturnMessage(10, 'You must select enough product from specified category', [
                        'category' => $cate->getName(),
                        'category_id' => (int)$cate->getId(),
                        'qty' => (int)$mustSelectProductQty
                    ]);
                }
            }
        }

        if ($course->getMinimumOrderQty() && $totalQty < $course->getMinimumOrderQty()) {
            $this->setReturnMessage(10, 'You need to add more products', [
                'minimumQty' => (int)$course->getMinimumOrderQty()
            ]);
        }

        // if only SPOT exist in profile, next delivery the profile will invalid
        $spotIds = $this->productCartFactory->create()->getSpotItemIds($profileId);
        $productIds = array_keys($productIds);
        $notSpot = array_diff($productIds, $spotIds);
        if (!$notSpot) {
            $this->setReturnMessage(10, 'Profile has only SPOT item. You must add at least one subscription item');
        }
        if (!$course->getData('allow_change_product')) {
            $deletedProductIds = array_diff($productIdsOrigin, $productIds);
            if (!empty($deletedProductIds)) {
                $this->setReturnMessage(10, 'This supscription course do not allow change product');
            }
        }
    }

    protected function _validateSingleProduct($id, $qty, $productIdsOrigin)
    {
        try {
            $product = $this->productRepository->getById($id);
            $stock = $product->getExtensionAttributes()->getStockItem();

            $min = (int) $stock->getData('min_sale_qty') ? $stock->getData('min_sale_qty') : 1;
            $max = (int) $stock->getData('max_sale_qty') ? $stock->getData('max_sale_qty') : $this->getMaxSaleQty();

            // Validate min & max sale qty
            if ($qty < $min || $qty > $max) {
                // group invalid products to show 1 mess only
                $this->invalidProducts[] = [
                    'id' => $id,
                    'min' => (int)$min,
                    'max' => (int)$max
                ];
            } else {
                // Only validate stock of product not in profile origin.
                if (!in_array($id, $productIdsOrigin)) {
                    // Validate bundle product
                    if ($product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
                        $bundleStock = $this->helperInventory->checkWarehouseBundle($product, $qty, 0);
                        if (!$bundleStock) {
                            $this->invalidProducts[] = [
                                'id' => $id,
                                'min' => (int)$min,
                                'max' => (int)$max
                            ];
                        }
                    } else {
                        // Validate simple product
                        if (($stock->getData('qty') < $qty || !$stock->getData('is_in_stock'))
                            && $stock->getData('backorders') == 0
                        ) {
                            $this->invalidProducts[] = [
                                'id' => $id,
                                'min' => (int)$min,
                                'max' => (int)$max
                            ];
                        }
                    }
                }
            }
        } catch (NoSuchEntityException $e) {
            $this->setReturnMessage(10, 'The product doesn\'t exist');
        }
    }

    protected function _getProductCartData($productId, $cart, $mainProfileId, $qty, $addressId, $ddate, $shippingAddressId)
    {
        if ($productId) {
            $productModel = $this->productFactory->create()->load($productId);
            if ($productModel->getId()) {
                $productIds[] = $productId;

                $unitQty = $productModel->getData('unit_qty');
                $unitDisplay = $productModel->getData('case_display');

                $billingId = $productModel->getBillingAddressId() != 0
                    ? $productModel->getBillingAddressId()
                    : $addressId;

                $productCartData = $this->createObjectByData([
                    'cart_id' => $cart->getCartID(),
                    'profile_id' => $mainProfileId,
                    'qty' => $this->case->getQtyPieceCaseForSaving($unitDisplay, $unitQty, $qty),
                    'product_type' => $productModel->getTypeId(),
                    'product_id' => $productId,
                    'shipping_address_id' => $shippingAddressId,
                    'billing_address_id' => $billingId,
                    'delivery_date' => $ddate,
                    'delivery_time_slot' => $cart->getDeliveryTimeSlot(),
                    'unit_case' => $this->case->getCaseDisplayKey($unitDisplay),
                    'unit_qty' => $this->case->validateQtyPieceCase($unitDisplay, $unitQty)
                ]);
                return $productCartData;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function update($profile, $profileId)
    {
        return $this->validate($profile, $profileId, true);
    }

    protected function checkInRange($start, $end, $current)
    {
        // convert to timestamp
        $start = strtotime($start);
        $end = strtotime($end);
        $current = strtotime($current);
        // check
        return (($current >= $start) && ($current <= $end));
    }

    public function productCartIdGroupByAddress($profileId)
    {
        $objProfile = $this->helperProfile->load($profileId);
        $arrProductCat = $objProfile->getProductCartData();
        $arrReturn = [];
        foreach ($arrProductCat as $key => $objData) {
            $addressId = $objData->getData(Profile::SHIPPING_ADDRESS_ID);
            if (!$addressId) {
                continue;
            }

            if (!isset($arrReturn[$objData[Profile::SHIPPING_ADDRESS_ID]])) {
                $objAddress = $this->getAddressById($addressId);
                $arrAddr = [
                    $objAddress->getStreetLine(1),
                    $objAddress->getCity(),
                    $objAddress->getPostcode(),
                    $objAddress->getRegion()
                ];

                $arrReturn[$objData->getData(Profile::SHIPPING_ADDRESS_ID)]['name'] = implode(",", $arrAddr);
                $arrReturn[$objData->getData(Profile::SHIPPING_ADDRESS_ID)]['info'] = [
                    $objAddress->getStreetLine(1),
                    $objAddress->getCity(),
                    $objAddress->getPostcode(),
                    $objAddress->getRegion()
                ];
                $arrReturn[$objData->getData(Profile::SHIPPING_ADDRESS_ID)]['delivery_date'] = [
                    'next_delivery_date' => $objData->getData('delivery_date'),
                    'time_slot' => $objData->getData('delivery_time_slot'),
                ];
            }

            // Query current product
            $productId = $objData->getData('product_id');
            $product = $this->getProductById($productId);

            $arrReturn[$objData->getData(Profile::SHIPPING_ADDRESS_ID)]['product'][] = [
                'name'=> $product->getData("name"),
                'price' => $product->getData("price"),
                'qty' => $objData->getData('qty'),
                'instance' => $product,
                'productcat_id' => $objData->getData('cart_id')
            ];
        }

        return $arrReturn;
    }
    /**
     * Save Subscription Profile from BO,FO and Watson API
     *
     * @param $profileData
     * @param $method
     * @param $arrAddress
     * @param $type
     * @throws \Exception
     */
    public function save($profileData, $method, $arrAddress, $type)
    {
        $baseProfileId = $profileData->getData('profile_id');
        $region = [];
        $productIds = [];
        $isCancelPaygent = false;
        $currentProfile = $this->profileFactory->create()->load($baseProfileId);
        if ($currentProfile->getId()) {
            $currentPayment = $currentProfile->getData('payment_method');
            if ($currentPayment == ConfigProvider::PAYGENT_CODE and $profileData->getData('payment_method') != ConfigProvider::PAYGENT_CODE) {
                $isCancelPaygent = true;
            }
        }
        if ($arrAddress) {
            $objAddress = $this->addressCollectionFactory->create();
            $objAddress->addFieldToFilter('entity_id', $arrAddress);

            foreach ($objAddress as $address) {
                $region[] = $address->getRegionId();
            }

            $productIds = $this->helperProfile->getProductSubscriptionProfile($profileData->getData('profile_id'));
        }

        $method = $this->prepareProfileType($profileData, $method);

        switch ($method) {
            case 'type_3':
                $profileModel = $this->saveProfileWithSkipNextDelivery($profileData, $arrAddress, $region, $productIds);
                break;
            default:
                $profileModel = $this->saveProfileOriginal(
                    $profileData,
                    $arrAddress,
                    $region,
                    $productIds,
                    $isCancelPaygent
                );
                break;
        }

        $profileModel->setData("objCompare", clone $profileData);
        $this->eventManager->dispatch('after_save_subscription_profile', [
            'profile_model' => $profileModel
        ]);
        if ($profileModel->isRealChange()) {
            $enableSendEmail = $this->scopeConfig->getValue(
                self::CONFIG_CHANGE_PROFILE_EMAIL_ENABLE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            if ($enableSendEmail) {
                try {
                    $profileDataConvert = clone $profileData;
                    $productCart = $this->getProductJson($profileDataConvert->getProductCart());
                    unset($profileDataConvert['product_cart']);
                    $profileDataConvert['product_cart'] = $productCart;
                    $profileItem = $this->profileItemFactory->create();

                    $profileItem->setProfileId($baseProfileId);
                    $profileItem->setProfileData($profileDataConvert->convertToJson());
                    $profileItemBuilder = $this->profileItemBuilder->setItems([$profileItem]);
                    $this->publisher->publish('profile.edited.order', $profileItemBuilder);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }

    /**
     * @param $profileData
     * @param $method
     * @return string
     * @throws Exception
     */
    public function prepareProfileType($profileData, $method)
    {
        if ($profileData->getData('type') == ProfileModel::SUBSCRIPTION_TYPE_TMP) {
            $method = 'type_2';
            $this->saveChangeTypeOfTmpProfile($profileData);
        }

        return $method;
    }

    public function saveProfileWithSkipNextDelivery($profileData, $address, $region, $productIds)
    {
        $profileId = $profileData->getData('profile_id');
        $profileModel = $this->profileFactory->create()->load($profileId);
        if ($profileModel->getId()) {
            $this->helperProfile->expiredVersion($profileId);
            $nextDeliveryDate =  $profileModel->getData('next_delivery_date');
            $intCurrentDate = strtotime($nextDeliveryDate);
            $nextDeliveryDate = $this->calNextDeliveryDate(
                $intCurrentDate,
                $profileData['frequency_interval'],
                $profileData['frequency_unit']
            );

            if ($nextDeliveryDate == '' || $nextDeliveryDate == null) {
                $message = __('Can not save, may be delivery_date was null or empty');
                throw new \Magento\Framework\Exception\LocalizedException($message);
            }

            // NED-638: Calculation of the day of week, week of month and next delivery date (if it changed)
            // If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
            // AND interval_unit="month"
            // AND not Stock Point
            $courseData = $profileData->getData('course_data');
            if (isset($courseData['next_delivery_date_calculation_option'])
                && $courseData['next_delivery_date_calculation_option']
                == \Riki\SubscriptionCourse\Model\Course::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK
                && $profileData->getData('frequency_unit') == 'month'
                && (!$profileData->getData('stock_point_profile_bucket_id')
                    || !$profileData->getData("riki_stock_point_id")
                )
            ) {
                $nextDeliveryDate = $this->deliveryDateGenerateHelper->getDeliveryDateForSpecialCase(
                    $nextDeliveryDate,
                    $profileData->getData('day_of_week'),
                    $profileData->getData('nth_weekday_of_month')
                );
            }

            if ($address) {
                $this->infoStateProfile($profileModel->getProfileId(), 'profile address', $address);
                $nextOrderDate = $this->helperProfile->calNextOrderDate($nextDeliveryDate, $region, $productIds,
                    $this->helperProfile->getExcludeBufferDays($profileId));
            } else {
                $this->infoStateProfile($profileModel->getProfileId(), 'profile address empty', $address);
                $nextOrderDate = $profileData->getData('next_order_date');
            }
            if($nextOrderDate != $profileData->getData('next_order_date')) {
                $this->infoStateProfile($profileModel->getProfileId(), 'profile next_order_date recalculated: ', $nextOrderDate);
            }
            $profileModel->setData('next_delivery_date', $nextDeliveryDate);
            $profileModel->setData('next_order_date', $nextOrderDate);

            $productCartModel = $this->profileRepository->getListProductCart($profileId);
            foreach ($productCartModel->getItems() as $productCartItem) {
                $deliveryDate = $productCartItem->getData('delivery_date');
                // prevent save DD null to product cart
                if (!strtotime($deliveryDate)
                    || $deliveryDate == '0000-00-00'
                    || $deliveryDate == null
                ) {
                    $message = __('Can not save, may be delivery_date was null or empty');
                    throw new \Magento\Framework\Exception\LocalizedException($message);
                }

                $nextDeliveryDateProductCart = $this->calNextDeliveryDate(
                    strtotime($deliveryDate),
                    $profileData['frequency_interval'],
                    $profileData['frequency_unit']
                );

                // NED-638: Update the next delivery date of product cart
                // If subscription profile has day_of_week is not null and nth_weekday_of_month is not null
                if ($profileData->getData('day_of_week') != null
                    && $profileData->getData('nth_weekday_of_month') != null
                ) {
                    $nextDeliveryDateProductCart = $nextDeliveryDate;
                }

                $productCartItem->setData('delivery_date', $nextDeliveryDateProductCart);
                $productCartItem->save();
            }

            $profileModel->save();
        }

        return $profileModel;
    }

    /**
     * @param $productCart
     * @param $arrAddress
     * @param $region
     * @param $productIds
     * @param $profileData
     * @return string
     */
    protected function getNextOrderDate($productCart, $arrAddress, $region, $productIds, $profileData)
    {
        $nextDeliveryDate = $this->_minDate($productCart);
        if ($arrAddress) {
            $nextOrderDate = $this->helperProfile->calNextOrderDate($nextDeliveryDate, $region, $productIds,
                $this->helperProfile->getExcludeBufferDays($profileData->getData('profile_id')));
        } else {
            $nextOrderDate = $profileData->getData('next_order_date');
        }
        return $nextOrderDate;
    }

    /**
     * @param $profileData
     * @param $arrAddress
     * @param $region
     * @param $productIds
     * @param $isCancelPaygent
     * @return mixed
     * @throws LocalizedException
     */
    public function saveProfileOriginal($profileData, $arrAddress, $region, $productIds, $isCancelPaygent)
    {
        // column type cannot use default timestamp_init
        $currentTime = $this->date->date(null, null, false)
            ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);

        $profileId = $profileData->getData('profile_id');
        $mainProfileId = $this->helperProfile->getMainFromTmpProfile($profileId);
        $profileVersionId = $this->profileRepository->getProfileIdVersion($profileId);
        $productCart = $profileData->getData('product_cart');
        $nextDeliveryDate = $this->_minDate($productCart);

        $nextDeliveryDateDefault = null;
        $dayOfWeek = $nthWeekdayOfMonth = null;
        $deliveryDateTmp = $nextDeliveryDate;
        $isDayOfWeekAndUnitMonthAndNotStockPoint = false;

        if ($nextDeliveryDate == '' || $nextDeliveryDate == null) {
            $message = __('Can not save, may be delivery_date was null or empty');
            throw new \Magento\Framework\Exception\LocalizedException($message);
        }

        // NED-638: Calculation of the day of week, week of month and next delivery date (if it changed)
        // If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
        // AND interval_unit="month"
        // AND not Stock Point
        $courseData = $profileData->getData('course_data');
        if (isset($courseData['next_delivery_date_calculation_option'])
            && $courseData['next_delivery_date_calculation_option']
            == \Riki\SubscriptionCourse\Model\Course::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK
            && $profileData->getData('frequency_unit') == 'month'
            && (!$profileData->getData("stock_point_profile_bucket_id")
                || !$profileData->getData("riki_stock_point_id")
            )
        ) {
            $isDayOfWeekAndUnitMonthAndNotStockPoint = true;
        }

        $isSkip = $profileData['skip_next_delivery'] == 1;

        if ($isSkip) {
            $intCurrentDate = strtotime($nextDeliveryDate);
            $nextDeliveryDate = $this->calNextDeliveryDate(
                $intCurrentDate,
                $profileData['frequency_interval'],
                $profileData['frequency_unit']
            );

            // NED-638: Calculation next delivery date, day of week and week of month
            if ($isDayOfWeekAndUnitMonthAndNotStockPoint) {
                if ($deliveryDateTmp == $profileData->getData('next_delivery_date')
                    && $profileData->getData('day_of_week') != null
                    && $profileData->getData('nth_weekday_of_month') != null
                ) {
                    $dayOfWeek = $profileData->getData('day_of_week');
                    $nthWeekdayOfMonth = $profileData->getData('nth_weekday_of_month');
                } else {
                    $dayOfWeek = date('l', strtotime($deliveryDateTmp));
                    $nthWeekdayOfMonth = $this->deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                        $deliveryDateTmp
                    );
                }

                $nextDeliveryDate = $this->deliveryDateGenerateHelper->getDeliveryDateForSpecialCase(
                    $nextDeliveryDate,
                    $dayOfWeek,
                    $nthWeekdayOfMonth
                );
            }
        } else {
            // NED-638: Calculation day of week and week of month
            if ($isDayOfWeekAndUnitMonthAndNotStockPoint) {
                if ($nextDeliveryDate == $profileData->getData('next_delivery_date')
                    && $profileData->getData('day_of_week') != null
                    && $profileData->getData('nth_weekday_of_month') != null
                ) {
                    $dayOfWeek = $profileData->getData('day_of_week');
                    $nthWeekdayOfMonth = $profileData->getData('nth_weekday_of_month');
                } else {
                    $dayOfWeek = date('l', strtotime($nextDeliveryDate));
                    $nthWeekdayOfMonth = $this->deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                        $nextDeliveryDate
                    );
                }
            }
        }

        // Set day_of_week and nth_weekday_of_month for profileData
        $profileData->setData('day_of_week', $dayOfWeek);
        $profileData->setData('nth_weekday_of_month', $nthWeekdayOfMonth);

        if ($nextDeliveryDate != $profileData['next_delivery_date']) {
            $this->infoStateProfile($profileId, 'profile next delivery date change', $nextDeliveryDate);
        }

        if ($arrAddress) {
            $this->infoStateProfile($profileId, 'profile address', $arrAddress);
            $nextOrderDate = $this->helperProfile->calNextOrderDate($nextDeliveryDate, $region, $productIds,
                $this->helperProfile->getExcludeBufferDays($profileData->getData('profile_id')));
        } else {
            $this->infoStateProfile($profileId, 'profile address empty', $arrAddress);
            $nextOrderDate = $profileData->getData('next_order_date');
        }

        if($nextOrderDate != $profileData->getData('next_order_date')) {
            $this->infoStateProfile($profileId, 'profile next_order_date recalculated', $nextOrderDate);
        } else {
            $this->infoStateProfile($profileId, 'profile next_order_date use previous', $nextOrderDate);
        }

        /** disable calculation next_delivery_date when selected stock point or profile is stock point */
        if ($profileData->getData("stock_point_profile_bucket_id") || $profileData->getData("riki_stock_point_id")) {
            $nextOrderDate = $profileData->getData('next_order_date');
            $this->infoStateProfile($profileId, 'profile next_order_date reverted to previous by stock point logic', $nextOrderDate);
        }

        $nextDeliveryDateDefault = $this->deliveryDateGenerateHelper->checkDeliveryDate(
            $deliveryDateTmp,
            $nextDeliveryDate
        );

        /** @var \Riki\Subscription\Model\Profile\Profile $profileModel */
        $profileModel = $this->profileFactory->create()->load($profileId, null, true);
        $spotIds = $this->productCartFactory->create()->getSpotItemIds($profileVersionId);

        $isTemp = $profileModel->getData('type') == 'tmp' ? true : false;

        if ($profileModel->getId()) {
            $sAdminUpdatedBy = !empty($this->authSession->getUser())
                ? $this->authSession->getUser()->getUserName()
                : '';

            foreach ($profileData->getData() as $field => $value) {
                //check if main profile does not change type
                if($profileModel->getData('profile_id') == $mainProfileId && $field == 'type') {
                    continue;
                }
                if($field == 'clean_stockpoint_data_subcarier_flg') { // no need to copy data for this flag
                    continue;
                }

                $profileModel->setData($field, $value);
            }
            $profileModel->addData([
                'next_delivery_date' => $nextDeliveryDate,
                'next_order_date' => $nextOrderDate,
                'trading_id' => $isCancelPaygent ? null : $profileData['trading_id'],
                'updated_date' => $currentTime,
                'updated_user' => $sAdminUpdatedBy,
                'admin_updated_by' => $sAdminUpdatedBy,
                'data_generate_delivery_date' => $nextDeliveryDateDefault,
                'day_of_week' => $dayOfWeek,
                'nth_weekday_of_month' => $nthWeekdayOfMonth
            ]);
            try {
                /** save data of stock point for profile */
                if ($profileData["riki_stock_point_id"]) {
                    $dataArray = [
                        "stock_point_id" => $profileData["stock_point_data"]["stock_point_id"],
                        "profile_id"=> $mainProfileId,
                        "stock_point_system_data" => $profileData["stock_point_data"]["stock_point_system_data"],
                        "delivery_type"=> $profileData["stock_point_data"]["delivery_type"],
                        "frequency"=>$profileData["stock_point_data"]["frequency_interval"],
                        "next_delivery_date"=>$profileData["stock_point_data"]["next_delivery_date"],
                        "next_order_date"=>$profileData["stock_point_data"]["next_order_date"],
                        "delivery_time"=> $profileData["stock_point_data"]["delivery_time"]
                    ];

                    /**
                     * call api when has stock point data
                     */
                    $externalBucket = $this->stockPointHelper->callAPIRegisterDelivery($dataArray);
                    if ($externalBucket &&
                        isset($externalBucket['bucket_id']) &&
                        isset($profileData["riki_stock_point_id"])
                    ) {
                        $profileBucketModel = $this->stockPointHelper->saveBucket(
                            $profileData["riki_stock_point_id"],
                            $externalBucket["bucket_id"]
                        );

                        if ($profileBucketModel->getId()) {
                            $profileModel->setData('stock_point_profile_bucket_id', $profileBucketModel->getId());
                            $profileData->setData('stock_point_profile_bucket_id', $profileBucketModel->getId());

                            $this->stockPointLogger->info(
                                "Profile Id : " . $mainProfileId . " change to Stock Point Profile",
                                ['type' => \Riki\StockPoint\Logger\StockPointLogger::LOG_TYPE_DEBUG_SHOW_BUTTON]
                            );
                        }
                        /**
                         * Clear session stock point data
                         */
                        $profileModel->setData("stock_point_data", null);
                    } else {
                        /**
                         * Call api fail
                         */

                        $this->stockPointLogger->info(
                            "Profile Id : " . $mainProfileId . " call RegisterDelivery API failed",
                            ['type' => \Riki\StockPoint\Logger\StockPointLogger::LOG_TYPE_DEBUG_SHOW_BUTTON]
                        );
                        $message =  __("There are something wrong in the system. Please re-try again.");
                        throw new LocalizedException($message);
                    }
                } elseif ( $profileModel->hasData('stock_point_profile_bucket_id')
                && $profileData->hasData('stock_point_delivery_type')
                && $profileData->getData('stock_point_delivery_type') == Profile::SUBCARRIER
                && !\Zend_Validate::is($profileData["is_delete_stock_point"],'NotEmpty') // this function will not work in case of calling remove stockpoint
                ) { // in case of subcarier, we will update delivery in stockpoint
                    $requestTimeSlot = reset($productCart)->getData('delivery_time_slot');
                    $dataArray = [
                        "profile_id"=> $mainProfileId,
                        "request_date" =>$nextDeliveryDate,
                        "frequency_interval"=> $profileData['frequency_interval'],
                        "frequency_unit"=> "month",
                        "request_magento_time_slot"=> $requestTimeSlot
                    ];

                    $resultApi = $this->stockPointHelper->callAPIUpdateDelivery($dataArray);

                    if ( !$resultApi ) { // in case of failure
                        /** call api when remove stock point  */
                        $resultApi = $this->stockPointHelper->removeFromBucket($mainProfileId);
                        if (isset($resultApi['success']) && !$resultApi['success']) {
                            throw new LocalizedException(
                                __("There are something wrong in the system. Please re-try again.")
                                );
                        }
                        /**
                         * Delete bucket profile id
                         */
                        $profileModel->setData("stock_point_profile_bucket_id", null);
                        $profileModel->setData("stock_point_delivery_information", null);
                        $profileModel->setData("stock_point_data", null);
                        # Update via profile data
                        $profileData->setData("stock_point_delivery_type",null);
                        $profileData->setData("stock_point_delivery_information",null);
                        $this->stockPointLogger->info(
                            "Profile Id : " . $mainProfileId . " change to Normal Profile",
                            ['type' => \Riki\StockPoint\Logger\StockPointLogger::LOG_TYPE_DEBUG_SHOW_BUTTON]
                        );

                        /**
                         * Update auto_stock_point_assign_status = 0
                         */
                        $profileModel->setData("auto_stock_point_assign_status", 0);
                    } else {
                        // validate data before adding
                        if(!\Zend_Validate::is($resultApi["delivery_date"],'NotEmpty')
                        || !\Zend_Validate::is($resultApi["order_date"],'NotEmpty')
                        ) {
                            throw new LocalizedException(__("Update API return incorrect data."));
                        }
                        # Modify via profile model object
                        $profileModel->setData("next_delivery_date",$resultApi["delivery_date"] );
                        $profileModel->setData("next_order_date",$resultApi["order_date"] );
                        foreach ($productCart as $item) {
                            $item->setData("original_delivery_date", $nextDeliveryDate );
                            $item->setData("original_delivery_time_slot", $requestTimeSlot);
                        }
                        # Modify via profile_data object
                        if(\Zend_Validate::is($resultApi["comment_for_customer"],'NotEmpty')) {
                            $profileData->setData("stock_point_delivery_information",$resultApi["comment_for_customer"]);
                        }
                    }
                } else {
                    $profileModel->setData(
                        "stock_point_profile_bucket_id",
                        $profileData['stock_point_profile_bucket_id']
                    );
                }
                $profileModel->setData("stock_point_delivery_type", $profileData['stock_point_delivery_type']);
                $profileModel->setData(
                    "stock_point_delivery_information",
                    $profileData['stock_point_delivery_information']
                );
                if (isset($profileData["is_delete_stock_point"]) && $profileData["is_delete_stock_point"]) {
                    /**
                     * only call api when profile has bucket id
                     */
                    $hasBucketId = $profileData->getData("delete_profile_has_bucket_id");
                    if (!empty($hasBucketId)) {
                        /** call api when remove stock point  */
                        $resultApi = $this->stockPointHelper->removeFromBucket($mainProfileId);
                        if (isset($resultApi['success']) && !$resultApi['success']) {
                            throw new LocalizedException(
                                __("There are something wrong in the system. Please re-try again.")
                            );
                        }
                        unset($profileData['delete_profile_has_bucket_id']);
                    }

                    /**
                     * Delete bucket profile id
                     */
                    $profileModel->setData("stock_point_profile_bucket_id", null);
                    $profileModel->setData("stock_point_delivery_type", null);
                    $profileModel->setData("stock_point_delivery_information", null);
                    $profileModel->setData("stock_point_data", null);
                    $profileData->setData('stock_point_profile_bucket_id', null);
                    unset($profileData['is_delete_stock_point']);
                    $this->stockPointLogger->info(
                        "Profile Id : " . $mainProfileId . " change to Normal Profile",
                        ['type' => \Riki\StockPoint\Logger\StockPointLogger::LOG_TYPE_DEBUG_SHOW_BUTTON]
                    );
                    if ($profileData->hasData('clean_stockpoint_data_subcarier_flg')) { // in case of clean up from subcarier
                        $profileModel->setData("auto_stock_point_assign_status", 0);
                        $profileData->setData('clean_stockpoint_data_subcarier_flg', null);
                    } else { // default case
                        $profileModel->setData("auto_stock_point_assign_status", 1);
                    }
                }

                if ($profileData['payment_method'] != ConfigProvider::PAYGENT_CODE) {
                    $profileModel->setData('payment_method', $profileData['payment_method']);
                }
                $this->infoStateProfile($profileId,'profile next_order_date before save',$profileModel->getData('next_order_date'));
                $profileModel->save();

                $isVersion = $this->helperProfile->checkProfileHaveVersion($profileId);

                if ($isVersion === false) {
                    $this->processProfileProductCart(
                        $profileId,
                        $productCart,
                        $isSkip,
                        $profileData,
                        $nextDeliveryDate,
                        $currentTime,
                        $spotIds
                    );
                }
                if ($isTemp && $profileModel->getPaymentMethod()) {
                    $this->_updatePaymentForMain($mainProfileId, $profileModel->getPaymentMethod());
                }
                $this->updateVersion($profileId, $isVersion, $productCart);
            } catch (LocalizedException $e) {
                $this->logger->critical($e);
                throw $e;
            } catch (\Exception $e) {
                $this->logger->critical($e);
                throw new LocalizedException(__("There are something wrong in the system. Please re-try again."));
            }
        } else {
            throw new LocalizedException(__("This profile does not existed."));
        }

        return $profileModel;
    }

    /**
     * Check payment of main profile if null then update payment
     *
     * @param $mainProfileId
     * @param $paymentMethod
     */
    private function _updatePaymentForMain($mainProfileId, $paymentMethod)
    {
        try {
            $mainProfileModel = $this->load($mainProfileId);
        } catch (NoSuchEntityException $e) {
            $mainProfileModel = null;
        }

        /**
         * NED - 48
         * Update payment method of main profile.set trading_id = null.
         */
        if ($mainProfileModel && !$mainProfileModel->getData('payment_method')) {
            $mainProfileModel->setPaymentMethod($paymentMethod);
            $mainProfileModel->setTradingId(null);

            try {
                $mainProfileModel->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * After save profile
     *      process for profile product cart data
     *
     * @param $profileId
     * @param $productCart
     * @param $isSkip
     * @param $profileData
     * @param $nextDeliveryDate
     * @param $currentTime
     * @param $spotIds
     * @throws LocalizedException
     */
    public function processProfileProductCart(
        $profileId,
        $productCart,
        $isSkip,
        $profileData,
        $nextDeliveryDate,
        $currentTime,
        $spotIds
    ) {
        $productCartModel = $this->productCartFactory->create()->getCollection();
        $productCartModel->addFieldToFilter('profile_id', $profileId);
        $productCartOldId = [];
        $productDisableWillRemove = [];
        foreach ($productCartModel as $product) {
            $productCartOldId[] = $product->getId();

            /*Case1: delete product cart*/
            if (!in_array($product->getId(), array_keys($productCart))) {
                try {
                    $productRepo = $this->productRepository->getById($product->getProductId());
                    if ($productRepo->getStatus() == 2) {
                        $productDisableWillRemove[] = $productRepo;
                    }
                } catch (NoSuchEntityException $e) {
                    $this->logger->error(__(
                        'The profile #%1  can not load the product ID #%2',
                        $profileId,
                        $product->getProductId()
                    ));
                }

                $this->deleteProfileCartData($product);
                continue;
            }

            foreach ($productCart as $item) {
                /*Case2: Update information of product cart */
                if ($product->getId('cart_id') != $item->getData('cart_id')) {
                    continue;
                }

                if ($isSkip) {
                    $deliveryDate = $item->getData('delivery_date');
                    $nextDeliveryDateProductCart = $this->calNextDeliveryDate(
                        strtotime($deliveryDate),
                        $profileData['frequency_interval'],
                        $profileData['frequency_unit']
                    );

                    // NED-638: Update the next delivery date of product cart
                    // If subscription profile has day_of_week is not null and nth_weekday_of_month is not null
                    if ($profileData->getData('day_of_week') != null
                        && $profileData->getData('nth_weekday_of_month') != null
                    ) {
                        $nextDeliveryDateProductCart = $nextDeliveryDate;
                    }

                    $item->setData('delivery_date', $nextDeliveryDateProductCart);
                }

                /** set origin null for case profile not stock point */
                if ($profileData->getData('stock_point_profile_bucket_id') == null) {
                    $item->setData('original_delivery_date', null);
                    $item->setData('original_delivery_time_slot', null);
                }
                $product->setData($item->getData());

                if (!strtotime($product->getData('delivery_date'))
                    || $product->getData('delivery_date') == '0000-00-00'
                ) {
                    $product->setData('delivery_date', $nextDeliveryDate);
                }

                $product->setData('updated_at', $currentTime);

                $product->save();
            }
        }

        if (!empty($productDisableWillRemove)) {
            $this->sendEmailProductDisabledOrRemoved($productDisableWillRemove);
        }

        $this->addProfileProductCart(
            $productCart,
            $productCartOldId,
            $isSkip,
            $profileData,
            $profileId,
            $currentTime,
            $spotIds,
            $nextDeliveryDate
        );
    }

    /**
     * After save profile
     *      add new profile product cart
     *
     * @param $productCart
     * @param $productCartOldId
     * @param $isSkip
     * @param $profileData
     * @param $profileId
     * @param $currentTime
     * @param $spotIds
     * @param $nextDeliveryDate
     * @throws LocalizedException
     */
    private function addProfileProductCart(
        $productCart,
        $productCartOldId,
        $isSkip,
        $profileData,
        $profileId,
        $currentTime,
        $spotIds,
        $nextDeliveryDate
    ) {
        foreach ($productCart as $item) {
            /*Case3: Add new product cart */
            if (!in_array($item->getData('cart_id'), $productCartOldId)) {
                if ($isSkip) {
                    $deliveryDate = $item->getData('delivery_date');

                    $nextDeliveryDateProductCart = $this->calNextDeliveryDate(
                        strtotime($deliveryDate),
                        $profileData['frequency_interval'],
                        $profileData['frequency_unit']
                    );

                    // NED-638: Update the next delivery date of product cart
                    // If subscription profile has day_of_week is not null and nth_weekday_of_month is not null
                    if ($profileData->getData('day_of_week') != null
                        && $profileData->getData('nth_weekday_of_month') != null
                    ) {
                        $nextDeliveryDateProductCart = $nextDeliveryDate;
                    }

                    $item->setData('delivery_date', $nextDeliveryDateProductCart);
                }

                $data = $item->getData();
                unset($data['cart_id']);
                $data['profile_id'] = $profileId;
                $data['updated_at'] = $currentTime;
                $data['created_at'] = $currentTime;
                if (in_array($item['product_id'], $spotIds)) {
                    $data['is_spot'] = 1;
                }
                // prevent save DD null to product cart
                if (!strtotime($data['delivery_date'])
                    || $data['delivery_date'] == '0000-00-00'
                    || $data['delivery_date'] == null
                ) {
                    $message = __('Can not save, may be delivery_date was null or empty');
                    throw new \Magento\Framework\Exception\LocalizedException($message);
                }
                $this->createProfileCartData($data);
            }
        }
    }

    /**
     * @param $profileId
     * @param $isVersion
     * @param $productCart
     */
    protected function updateVersion($profileId, $isVersion, $productCart)
    {
        /*Update information into primary profile and delete all version related to that profile*/
        $versionModel = $this->versionFactory->create()->getCollection();
        $versionProfile = $versionModel->addFieldToFilter('rollback_id', $profileId)
                                       ->addFieldToFilter('status', true);

        foreach ($versionProfile as $version) {
            $this->deActiveVersion($version);
        }

        if ($isVersion !== false) {
            $this->updateProductCartForMainProfile($profileId, $productCart);
        }
    }

    /**
     * Expired profile version not used
     *
     * @param $profileId
     * @throws \Exception
     */
    public function expiredVersion($profileId)
    {
        /*Update information into primary profile and delete all version related to that profile*/
        $versionModel = $this->versionFactory->create()->getCollection();
        $versionProfile = $versionModel->addFieldToFilter('rollback_id', $profileId);

        foreach ($versionProfile as $version) {
            $this->deActiveVersion($version);
        }
    }

    /**
     * Update product cart for main profile. Note profile id always main profile id, $productCart come from session
     *
     * @param $profileId
     * @param $productCart
     *
     * @return void
     */
    public function updateProductCartForMainProfile($profileId, $productCart)
    {
        $productCartOfCurrentProfile = $this->productCartFactory->create()->getCollection();
        $productCartOfCurrentProfile->addFieldToFilter('profile_id', $profileId);

        if ($productCartOfCurrentProfile->getSize()) {
            foreach ($productCartOfCurrentProfile as $productCartModelItem) {
                $this->deleteProfileCartData($productCartModelItem);
            }
        }

        foreach ($productCart as $item) {
            $data = $item->getData();
            unset($data['cart_id']);
            $data['profile_id'] = $profileId;
            $this->createProfileCartData($data);
        }
    }

    /**
     * @param $profileData
     * @param $arrAddress
     * @param $region
     * @param $productIds
     * @return mixed
     * @throws LocalizedException
     */
    public function saveProfileVersion($profileData, $arrAddress, $region, $productIds)
    {
        // column type cannot use default timestamp_init
        $currentTime = $this->date->date(null, null, false)
            ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);

        $primaryProfileId = $profileData->getData('profile_id');
        $profileVersionId = $this->profileRepository->getProfileIdVersion($primaryProfileId);
        $productCart = $profileData->getData('product_cart');

        /*Create new version profile*/
        $nextDeliveryDate = $this->_minDate($productCart);

        if ($nextDeliveryDate == '' || $nextDeliveryDate == null) {
            $message = __('Can not save, may be delivery_date was null or empty');
            throw new \Magento\Framework\Exception\LocalizedException($message);
        }

        $isSkip = $profileData['skip_next_delivery'] == 1;

        if ($isSkip) {
            $intCurrentDate = strtotime($nextDeliveryDate);
            $nextDeliveryDate = $this->calNextDeliveryDate(
                $intCurrentDate,
                $profileData['frequency_interval'],
                $profileData['frequency_unit']
            );
        }

        $nextOrderDate = $profileData->getData('next_order_date');

        if ($arrAddress) {
            $nextOrderDate = $this->helperProfile->calNextOrderDate($nextDeliveryDate, $region, $productIds,
                $this->helperProfile->getExcludeBufferDays($profileData->getData('profile_id')));
        }

        $status = Profile::STATUS_ENABLED;
        $profileModel = $this->profileFactory->create();

        $sAdminUpdatedBy = !empty($this->authSession->getUser())
            ? $this->authSession->getUser()->getUserName()
            : '';

        $sAdminCreatedBy = !empty($this->authSession->getUser())
            ? $this->authSession->getUser()->getUserName()
            : '';

        $profileModel->setData([
            'customer_id' => $profileData['customer_id'],
            'store_id' => $profileData['store_id'],
            'hanpukai_qty' => $profileData['hanpukai_qty'],
            'course_id' => $profileData['course_id'],
            'course_name' => $profileData['course_name'],
            'frequency_unit' => $profileData['frequency_unit'],
            'frequency_interval' => $profileData['frequency_interval'],
            'shipping_fee' => $profileData['shipping_fee'],
            'payment_method' => $profileData['payment_method'],
            'shipping_condition' => $profileData['shipping_condition'],
            'order_channel' => $profileData['order_channel'],
            'earn_point_on_order' => $profileData['earn_point_on_order'],
            'coupon_code' => $profileData['coupon_code'],
            'penalty_amount' => $profileData['penalty_amount'],
            'next_delivery_date' => $nextDeliveryDate,
            'next_order_date' => $nextOrderDate,
            'status' => $status,
            'type' => 'version',
            'order_times' => $profileData['order_times'],
            'sales_count' => $profileData['sales_count'],
            'sales_value_count' => $profileData['sales_value_count'],
            'trading_id' =>$profileData['trading_id'],
            'create_order_flag' => 0,
            'reindex_flag' => $profileData['reindex_flag'],
            'created_date' => $currentTime,
            'updated_date' => $currentTime,
            'updated_user' => $sAdminUpdatedBy,
            'created_user' => $sAdminCreatedBy,
            'last_authorization_failed_date' => $profileData['last_authorization_failed_date'],
            'authorization_failed_time' => $profileData['authorization_failed_time'],
            'disengagement_date' => $profileData['disengagement_date'],
            'disengagement_reason' => $profileData['disengagement_reason'],
            'disengagement_user' => $profileData['disengagement_user'],
            'old_profile_id' => $profileData['old_profile_id'],
        ]);

        if ($profileData['payment_method'] != ConfigProvider::PAYGENT_CODE ||
            ($profileData['payment_method'] == ConfigProvider::PAYGENT_CODE && $profileModel->getData('payment_method') == ConfigProvider::PAYGENT_CODE)
        ) {
            $profileModel->setData('payment_method', $profileData['payment_method']);
        }

        $profileModel->save();
        $profileId = $profileModel->getId();

        $profileModel = $this->profileFactory->create()->load($profileId);
        $primaryProfileModel = $this->profileFactory->create()->load($primaryProfileId);
        $spotIds = $this->productCartFactory->create()->getSpotItemIds($profileVersionId);
        if ($profileModel->getId() && $primaryProfileModel->getId()) {
            foreach ($productCart as $item) {
                if ($isSkip) {
                    $deliveryDate = $item->getData('delivery_date');

                    $nextDeliveryDateProductCart = $this->calNextDeliveryDate(
                        strtotime($deliveryDate),
                        $profileData['frequency_interval'],
                        $profileData['frequency_unit']
                    );

                    $item->setData('delivery_date', $nextDeliveryDateProductCart);
                }
                $data = $item->getData();
                unset($data['cart_id']);
                $data['profile_id'] = $profileId;
                $data['updated_at'] = $currentTime;
                $data['created_at'] = $currentTime;

                if (in_array($item['product_id'], $spotIds)) {
                    $data['is_spot'] = 1;
                }
                // prevent save DD null to product cart
                if (!strtotime($data['delivery_date'])
                    || $data['delivery_date'] == '0000-00-00'
                    || $data['delivery_date'] == null
                ) {
                    $message = __('Can not save, may be delivery_date was null or empty');
                    throw new \Magento\Framework\Exception\LocalizedException($message);
                }

                $this->createProfileCartData($data);
            }

            /*All old version of this profile will expired*/
            $this->updateExpiredVersion($primaryProfileId);

            /*Add new version*/
            $dataVersion = [];
            $dataVersion['start_time'] = $profileData['next_delivery_date'];
            $dataVersion['rollback_id'] = $primaryProfileId;
            $dataVersion['is_rollback'] = 0;
            $dataVersion['moved_to'] = $profileId;
            $dataVersion['status'] = true;
            $versionModel = $this->versionFactory->create();
            $versionModel->setData($dataVersion);

            $versionModel->save();

            // make sure cron BI export next delivery can export version together main profile
            $primaryProfileModel->setData('updated_date', $profileModel->getData('updated_date'));
            $primaryProfileModel->save();
        } else {
            throw new LocalizedException(__("This profile does not existed."));
        }

        return $profileModel;
    }

    /**
     * Create profile cart data
     *
     * @param $data
     */
    public function createProfileCartData($data)
    {
        /* Add new product cart */
        $productCartNew = $this->productCartFactory->create();
        $productCartNew->setData($data);
        try {
            $productCartNew->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param $data
     */
    private function deleteProfileCartData($data)
    {
        try {
            $data->delete();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * Get the earlier day in list
     *
     * @param array $arrObjProductCat
     * @return mixed|null
     */
    public function _minDate($arrObjProductCat)
    {
        $arrDeliveryDate = [];
        foreach ($arrObjProductCat as $catId => $objProductCat) {
            if ((int)$objProductCat->getData('parent_item_id') == 0
                && (strtotime($objProductCat->getData("delivery_date"))
                && $objProductCat->getData("delivery_date") != '0000-00-00')
            ) {
                $arrDeliveryDate[] = $objProductCat->getData("delivery_date");
            }
        }

        return $this->_getMinDate($arrDeliveryDate);
    }

    public function _getMinDate($arrDate)
    {
        $minDate = isset($arrDate[0])?$arrDate[0]:null;
        $num = count($arrDate);
        for ($i = 0; $i < $num; $i++) {
            if (strtotime($minDate) > strtotime($arrDate[$i])) {
                $minDate = $arrDate[$i];
            }
        }

        return $minDate;
    }

    public function _sendEmailEditProfile($profile)
    {
        $customer = $this->customerFactory->create()->load($profile->getData('customer_id'));
        // Send Email
        $varEmailTemplate['customer_name'] = $customer->getName();
        $varEmailTemplate['subscription_profile_id'] = $profile->getId();
        $varEmailTemplate['emailReceiver'] = $customer->getEmail();
        $this->emailProfileHelper->sendEmailChangeProfile($varEmailTemplate);
    }

    /**
     * Update status of all old version of a profile
     *
     * @param $profileId
     */
    protected function updateExpiredVersion($profileId)
    {
        $versionModel = $this->versionFactory->create()->getCollection();
        $versionModel->addFieldToFilter('rollback_id', $profileId);
        $versionModel->addFieldToFilter('status', true);

        foreach ($versionModel as $version) {
            $this->deActiveVersion($version);
        }
    }

    /**
     * de active version
     *
     * @param $version
     */
    private function deActiveVersion($version)
    {
        $version->setStatus(false);
        try {
            $version->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function saveChangeTypeOfTmpProfile($profileData)
    {
        $profileId = $profileData->getData('profile_id');
        $collectionProfileLink = $this->profileLinkCollectionFactory->create()
                                    ->addFieldToFilter('linked_profile_id', $profileId)
                                    ->setOrder('link_id');
        if ($collectionProfileLink->getSize()) {
            $profileLinkObj = $collectionProfileLink->setPageSize(1)->getFirstItem();

            if ($profileData->getData('profile_type') == 'type_1') {
                $profileLinkObj->setData('change_type', 1);
                $profileLinkObj->save();
            } else {
                $profileLinkObj->setData('change_type', 0);
                $profileLinkObj->save();
            }
        }
    }

    /**
     * Send email when delete disabled product out product cart
     *
     * @param $arrProduct
     * @return bool
     */
    public function sendEmailProductDisabledOrRemoved($arrProduct)
    {
        $enableSendEmail = $this->scopeConfig->getValue(
            self::CONFIG_SEND_EMAIL_DISABLED_OR_REMOVED_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$enableSendEmail) {
            return false;
        }

        $variableEmail = [];
        if (is_array($arrProduct)) {
            foreach ($arrProduct as $product) {
                if (is_array($product)) {
                    $variableEmail['product'][] = [
                        'name' => $product->getName(),
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

    /**
     * calculate next delivery date
     *
     * @param $time
     * @param $frequencyInterval
     * @param $strFrequencyUnit
     * @return string
     */
    private function calNextDeliveryDate($time, $frequencyInterval, $strFrequencyUnit)
    {
        $timestamp = strtotime($frequencyInterval . " " . $strFrequencyUnit, $time);

        $objDate  = $this->date->date();
        $objDate->setTimestamp($timestamp);

        return $objDate->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
    }

    /**
     * Update sub total when customer change product qty
     *
     * @api
     *
     * @param int $courseId
     * @param int $iProfileId
     * @param string $frequencyUnit
     * @param string $frequencyInterval
     * @param int $productId
     * @param int $qtyChange
     * @return mixed
     */
    public function changeProductQty($courseId, $iProfileId, $frequencyUnit, $frequencyInterval, $productId, $qtyChange)
    {
        $response = ['html_price' => ''];
        $this->design->setArea('frontend');
        $this->design->setDefaultDesignTheme();

        $courseFactory = $this->courseFactory->create();
        $frequencyId = $courseFactory->checkFrequencyEntitiesExitOnDb($frequencyUnit, $frequencyInterval);
        $this->registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
        $this->registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);

        if ($iProfileId) {
            $profileModel = $this->profileFactory->create()->load($iProfileId);
            $this->registry->register('subscription_profile_obj', $profileModel);

            $profileItems = $this->getCurrentProfileItemsFromCacheData($profileModel);

            /** @var \Riki\Subscription\Model\ProductCart\ProductCart $profileItem */
            foreach ($profileItems as $profileItem) {
                if ($profileItem->getProductId() == $productId) {
                    /** @var \Magento\Catalog\Model\Product $product */
                    $product = $profileItem->getProduct();

                    if ($product) {
                        $product->setQty($qtyChange);
                        $response['html_price'] = $this->getFinalProductPrice($product);
                        $this->updateQtyAndSaveProfileCache($iProfileId,$productId, $qtyChange);
                    }
                }
            }
        }

        return \Zend_Json::encode($response);
    }

    protected function updateQtyAndSaveProfileCache($profileId, $productId, $qtyChange)
    {
        $profileData = $this->profileCacheRepository->getProfileDataCache($profileId);
        if ($profileData) {
            if (isset($profileData['product_cart']) && !empty($profileData['product_cart'])) {
                foreach ($profileData['product_cart'] as $item) {
                    if ($item->getProductId() == $productId) {
                        $item->setQty($qtyChange);
                    }
                }
            }
            $this->profileCacheRepository->save($profileData);
        }
    }
    /**
     * Function move from Riki/Subscription/Helper/Profile/Data.php
     *
     * @param $profile
     * @return array
     */
    protected function getCurrentProfileItemsFromCacheData($profile)
    {
        /*list item of profile*/
        $items = [];
        $profileId = $profile->getId();
        $profileData = $this->profileCacheRepository->getProfileDataCache($profileId);

        if ($profileData) {
            if (isset($profileData['product_cart']) && !empty($profileData['product_cart'])) {
                foreach ($profileData['product_cart'] as $item) {
                    try {
                        //need load single product to avoid miss some product data
                        $product = $this->productRepository->getById($item->getProductId());
                    } catch (NoSuchEntityException $e) {
                        $this->logger->info(sprintf('Product ID #%s doesn\'t exist.', $item->getProductId()));
                        $product = null;
                    }

                    if ($product && $product->getId()) {
                        $this->eventManager->dispatch('profile_item_load_after', [
                            'profile'   =>  $profile,
                            'profile_item'  =>  $item,
                            'product'   =>  $product
                        ]);

                        $item->setProduct($product);

                        $items[] = $item;
                    }
                }
            }
        }

        return $items;
    }
    /**
     * Return HTML block with product price
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getFinalProductPrice(\Magento\Catalog\Model\Product $product)
    {
        $price = 0;
        $total = 0;

        if ($qty = $product->getQty()) {
            $unitQty = 1;

            if ($product->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                $unitQty = max((int)$product->getUnitQty(), 1);
            }

            $price = $this->subscriptionHelperData->getProductPriceInProfileEditPage($product, $qty);

            $total = $price * $qty;

            $price = $price * $unitQty;
        }

        return
            [
                $total,
                $this->priceCurrency->convertAndFormat(
                    $total,
                    true,
                    \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                    $this->storeManager->getStore()
                ),
                $this->priceCurrency->format($price)
            ];
    }

    /**
     * @param $ProductCat
     * @return array
     */
    public function getProductJson($ProductCat)
    {
        $productArray =[];
        foreach ($ProductCat as $product) {
            $productArray[] =  $product->getData();
        }
        return $productArray;
    }

    /**
     * get product by id
     *
     * @param $productId
     * @return \Magento\Catalog\Model\Product|bool
     */
    public function getProductById($productId)
    {
        $product = $this->productFactory->create()->load($productId);

        if ($product->getId()) {
            return $product;
        }
        return false;
    }

    /**
     * get address by id
     *
     * @param $addressId
     * @return AddressModel
     */
    public function getAddressById($addressId)
    {
        return $this->addressModel->load($addressId);
    }

    /**
     * Create object by data
     *
     * @param array $data
     * @return DataObject
     */
    public function createObjectByData($data)
    {
        $dataObject = $this->objectFactory->create();
        $dataObject->setData($data);
        return $dataObject;
    }

    /**
     * get profile by id
     *
     * @param $profileId
     * @return Profile
     */
    public function getProfileById($profileId)
    {
        return $this->profileFactory->create()->load($profileId);
    }

    /**
     * get course by id
     *
     * @param $courseId
     * @return \Riki\SubscriptionCourse\Model\Course
     */
    public function getCourseById($courseId)
    {
        return $this->courseFactory->create()->load($courseId);
    }

    /**
     * get product ids from profile origin.
     *
     * @param int $profileId
     * @param \Riki\Subscription\Model\Profile\Profile $profileOrigin
     * @return array
     */
    public function getProductIdsFromProfileOrigin($profileOrigin, $profileId)
    {
        $productIds = [];

        $productCarts = $profileOrigin->getProductCartCollection($profileId);
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
     * Validate next_delivery_date by subscription course setting
     * @param $ddate
     * @param $course
     * @param $profileOrigin
     */
    private function validateNextDeliveryDateChanging($ddate, $course, $profileOrigin)
    {
        if ($ddate != $profileOrigin->getData('next_delivery_date')) {
            if (!$course->isAllowChangeNextDeliveryDate()) {
                $this->setReturnMessage(10, 'This supscription course do not allow change next delivery date');
            }
        }
    }

    /**
     * Validate shipping_address_id by subscription course setting
     * @param $orgAddressId
     * @param $inputAddressId
     * @param $course
     * @param $profileOrigin
     */
    private function validateShippingAddressChanging($orgAddressId, $inputAddressId, $course, $profileOrigin)
    {
        /** @var \Riki\Customer\Model\Data\Customer $customer */
        $customer = $profileOrigin->getCustomer();
        $addresses = $customer->getAddresses();
        $validAddressId = false;
        foreach ($addresses as $address) {
            if ($address->getId() == $inputAddressId) {
                $validAddressId = true;
                break;
            }
        }
        // Check shipping address belong to correct customer
        if ($validAddressId) {
            // Check if shipping_address_id have been changed
            if ($orgAddressId != $inputAddressId) {
                if (!$course->getData('allow_change_address')) {
                    $this->setReturnMessage(10, 'This supscription course do not allow change address');
                }
            }
        } else {
            if ($course->getData('allow_change_address')) {
                $this->setReturnMessage(10, 'This shipping address do not belong to the customer');
            }
        }
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
                $this->setReturnMessage(10, 'This supscription course do not allow change product');
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
                    $this->setReturnMessage(10, 'This supscription course do not allow change quantity');
                }
            }
        }
    }
    /**
     * @param $subCourse
     * @param $profile
     * @param $cartData
     * @param $profileOrigin
     */
    private function validateAmountRestriction($subCourse, $profile, $cartData, $profileOrigin)
    {
        try {
            $customer = $profileOrigin->getCustomer();
            $simulatorOrder = $this->simulator->createMageOrderForAPI(
                $profile->getId(),
                $cartData,
                $customer
            );
            if ($simulatorOrder) {
                $result = $this->subscriptionHelperOrder
                    ->validateAmountRestriction(
                        $simulatorOrder,
                        $subCourse,
                        $profileOrigin
                    );
                if (!$result['status']) {
                    $this->setReturnMessage(10, $result['message']);
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->info($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param $productCartData
     * @param $profileId
     * @throws LocalizedException
     */
    private function validateMaximumQtyRestriction($productCartData, $profileId)
    {
        try {
            $resultValidate = $this->subscriptionValidator->setProfileId($profileId)
                ->setProductCarts($productCartData)
                ->validateMaximumQtyRestriction();
            if ($resultValidate['error']) {
                $message = $this->subscriptionValidator->getMessageMaximumError(
                    $resultValidate['product_errors'],
                    $resultValidate['maxQty']
                );

                $this->setReturnMessage(10, $message);
            }
        } catch (LocalizedException $e) {
            $this->logger->info($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param int $customerId
     * @param int $campaignId
     * @return string[]
     */
    public function getMultipleCategoryCampaignProfileByCustomer($customerId, $campaignId)
    {
        $result = [];

        // Load campaign model
        $campaignModel = $this->campaignFactory->create()->load($campaignId);
        $courseIds = $campaignModel->getData('course_ids');
        $profileModel = $this->profileFactory->create();
        $profiles = $profileModel->getCustomerSubscriptionProfileExcludeCourseCode($customerId, $courseIds);
        if ($profiles->getSize()) {
            foreach ($profiles as $key => $profile) {
                // If profile is monthly, don't show it in popup
                if ($profile->getData('subscription_type') == CourseType::TYPE_MONTHLY_FEE) {
                    $profiles->removeItemByKey($profile->getId());
                    continue;
                }
                $shippingAddressType = $this->helperProfile
                    ->getCustomerAddressType($this->getShippingAddressId($profile));

                $data['profile_id'] = $profile->getProfileId();
                $data['course_name'] = $profile->getData('course_name');
                $data['frequency_interval'] =  $profile->getData('frequency_interval');
                $data['frequency_unit'] =  $profile->getData('frequency_unit');
                $data['shipping_address_type'] = __($shippingAddressType);
                $data['next_delivery_date'] = $this->helperProfile->formatDate($profile->getNextDeliveryDate());

                $result[] = $data;
            }
        }
        if (!empty($result)) {
            return $result;
        }

        return ['error_message' => __('There are no subscription profiles for regular flights covered by this campaign.')];
    }

    /**
     * @param $profile
     * @return |null
     */
    private function getShippingAddressId($profile)
    {
        $productCarts = $profile->getProductCart();
        foreach ($productCarts as $productCart) {
            if ($productCart->getShippingAddressId()) {
                return $productCart->getShippingAddressId();
            }
        }
        return null;
    }

    public function infoStateProfile($profileId, string $message, $data) {

        if (empty($profileId)) {
            $this->loggerStateProfile->info("Cannot write to log because profileId is empty");
            return $this;
        }

        if (!is_string($message)) {
            $this->loggerStateProfile->info("Cannot write to log because message is not string type");
            return $this;
        }

        try {
            $message = $message.': '.$profileId.' : '.\Zend_Json_Encoder::encode($data);
            $this->loggerStateProfile->info($message);
        } catch (\Exception $e) {
            $this->loggerStateProfile->info("Cannot write to log due to exception");
            $this->loggerStateProfile->critical($e);
        }

        return $this;
    }

	/**
	 * @param int $customerId
     * @param int $landingPageId
	 * @return string[]
	 */
	public function getProfileByCustomerForSummerCampaign($customerId, $landingPageId)
	{
		$result = [];

		$profileModel  = $this->profileFactory->create();
		$landingPage   = $this->landingPageFactory->create()->load($landingPageId);

		$excludeCourses = [];
        if ($landingPage->getId()) {
            $excludeCourses = $landingPage->getCourseIds();
        }

 		$profiles      = $profileModel->getCustomerSubscriptionProfileExcludeCourseCode($customerId, $excludeCourses);

		$profiles->addFieldToFilter('subscription_course.allow_change_product', 1);
		$profiles->addFieldToFilter('subscription_course.subscription_type', ['neq' => CourseType::TYPE_MONTHLY_FEE]);

		if ($profiles->getSize()) {
			foreach ($profiles as $key => $profile) {
				$shippingAddressType = $this->helperProfile
					->getCustomerAddressType($this->getShippingAddressId($profile));

				$data['profile_id']            = $profile->getProfileId();
				$data['course_name']           = $profile->getData('course_name');
				$data['frequency_interval']    = $profile->getData('frequency_interval');
				$data['frequency_unit']        = $profile->getData('frequency_unit');
				$data['shipping_address_type'] = __($shippingAddressType);
				$data['next_delivery_date']    = $this->helperProfile->formatDate($profile->getNextDeliveryDate());

				$result[] = $data;
			}
		}

		return $result;

	}
}
