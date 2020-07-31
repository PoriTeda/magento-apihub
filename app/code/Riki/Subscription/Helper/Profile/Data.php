<?php
namespace Riki\Subscription\Helper\Profile;

use Magento\Framework\Exception\NoSuchEntityException;
use Riki\ShippingProvider\Model\Carrier;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface as TimezoneInterface;
use Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\CollectionFactory as ProfileLinkCollectionFactory;
use Riki\Subscription\Model\Profile\Profile as ProfileModel;
use Riki\Subscription\Model\Profile\ProfileLinkFactory as ProfileLinkModelFactory;
use Riki\Subscription\Model\Version\VersionFactory;
use Riki\SubscriptionCourse\Model\CourseFactory;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;
use Riki\SubscriptionCourse\Model\ResourceModel\Course as SubscriptionCourseResourceModel;
use Riki\Subscription\Helper\Hanpukai\Data as HanpukaiHelperData;
use Riki\Subscription\Model\Constant;
use Magento\Framework\DataObject;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * NOTE : ALL PROFILE ID PARAM IS MAIN PROFILE ID
     */


    /**
     * Shipping tax class
     */
    const XML_PATH_TAX_CLASS = 'tax/classes/shipping_tax_class';

    const XML_PATH_SHIPPING_TAX_CONFIG = 'tax/calculation/shipping_includes_tax';

    const XML_PATH_TAX_CART_DISPLAY_CONFIG = 'tax/cart_display/shipping';

    const PROFILE_STATUS_PLANED = 'waiting_for_shipment';
    const PROFILE_STATUS_EDITABLE = 'editable';
    const PROFILE_STATUS_FOR_REFERENCE = 'for_reference';
    const SPOT_PRODUCT = 'spot';
    const MAIN_PRODUCT = 'main';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerCollection;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * Cache on code level
     *
     * @var array
     */
    protected $simpleLocalStorage = [];

    /**
     * @var \Magento\Framework\App\Resource
     */
    protected $resource;

    protected $collectionProduct;

    protected $collectionProductCart;

    protected $_regionFactory;

    protected $_date;

    protected $helperDelivery;

    protected $leadTimeFactory;

    protected $deliverytype;

    /**
     * ['profile']['id']['product']
     * ['id'] =>
     * @var array
     */
    protected $localStorage = [
    ];
    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddress;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Magento\GiftMessage\Model\MessageFactory
     */
    protected $messageFactory;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var Carrier
     */
    protected $carrier;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $_helperPrice;
    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $taxCalculation;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /* @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    protected $timezone;

    /**
     * @var \Riki\Subscription\Model\Version\VersionFactory
     */
    protected $_profileVersion;

    /* @var \Riki\Subscription\Model\Profile\ResourceModel\ProfileLink\CollectionFactory */
    protected $profileLinkCollection;

    /* @var \Riki\Subscription\Model\Profile\ProfileLinkFactory */
    protected $profileLinkModelFactory;

    /* @var \Riki\SubscriptionCourse\Model\CourseFactory */
    protected $courseFactory;

    /* @var \Riki\SubscriptionCourse\Model\ResourceModel\Course */
    protected $subscriptionCourseResourceModel;

    /* @var \Riki\Subscription\Helper\Hanpukai\Data */
    protected $hanpukaiHelperData;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;

    /* @var \Magento\Catalog\Block\Product\ImageBuilder */
    protected $_imageBuilder;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;
    /* @var \Riki\TimeSlots\Model\TimeSlotsFactory */
    protected $_timeSlotFactory;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $rikiCustomerRepository;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var DeliveryDateGenerateHelper
     */
    protected $_deliveryDateGenerateHelper;

    protected $loadedProfileItems = [];

    /**
     * list of loaded profiles by id
     *
     * @var array
     */
    protected $loadedProfiles = [];

    /**
     * loaded profiles by main profile id
     *
     * @var array
     */
    protected $loadedTmpProfiles = [];

    /**
     * Data constructor.
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param HanpukaiHelperData $hanpukaiHelperData
     * @param SubscriptionCourseResourceModel $subscriptionCourseResourceModel
     * @param CourseFactory $courseFactory
     * @param VersionFactory $versionFactory
     * @param ProfileLinkModelFactory $profileLinkModelFactory
     * @param ProfileLinkCollectionFactory $profileLinkCollection
     * @param TimezoneInterface $timezoneInterface
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProductFactory
     * @param \Riki\Subscription\Model\ProductCart\ProductCartFactory $collectionProductCart
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Riki\DeliveryType\Helper\Data $helperDelivery
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Riki\ShipLeadTime\Model\LeadtimeFactory $leadtimeFactory
     * @param \Riki\DeliveryType\Model\Product\Deliverytype $deliverytype
     * @param \Magento\Customer\Model\AddressFactory $customerAddress
     * @param \Magento\GiftMessage\Model\MessageFactory $messageFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Carrier $carrier
     * @param \Magento\Framework\Pricing\Helper\Data $helperPrice
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        HanpukaiHelperData $hanpukaiHelperData,
        SubscriptionCourseResourceModel $subscriptionCourseResourceModel,
        CourseFactory $courseFactory,
        VersionFactory $versionFactory,
        ProfileLinkModelFactory $profileLinkModelFactory,
        ProfileLinkCollectionFactory $profileLinkCollection,
        TimezoneInterface $timezoneInterface,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProductFactory,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $collectionProductCart,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Riki\DeliveryType\Helper\Data $helperDelivery,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Riki\ShipLeadTime\Model\LeadtimeFactory $leadtimeFactory,
        \Riki\DeliveryType\Model\Product\Deliverytype $deliverytype,
        \Magento\Customer\Model\AddressFactory $customerAddress,
        \Magento\GiftMessage\Model\MessageFactory $messageFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        Carrier $carrier,
        \Magento\Framework\Pricing\Helper\Data $helperPrice,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\TimeSlots\Model\TimeSlotsFactory $timeSlotsFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper
    ) {
        $this->_imageBuilder = $imageBuilder;
        $this->hanpukaiHelperData = $hanpukaiHelperData;
        $this->subscriptionCourseResourceModel = $subscriptionCourseResourceModel;
        $this->courseFactory = $courseFactory;
        $this->_profileVersion = $versionFactory;
        $this->profileLinkModelFactory = $profileLinkModelFactory;
        $this->profileLinkCollection = $profileLinkCollection;
        $this->timezone = $timezoneInterface;
        $this->_storeManager = $storeManager;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_backendUrl = $backendUrl;
        $this->_customerCollection = $collectionFactory;
        $this->objectManager = $objectManager;
        $this->categoryFactory = $categoryFactory;
        $this->resource = $resource;
        $this->connection = $this->resource->getConnection('core_write');
        $this->collectionProduct = $collectionProductFactory;
        $this->collectionProductCart = $collectionProductCart;
        $this->_paymentConfig = $paymentConfig;
        $this->_regionFactory =  $regionFactory;
        $this->_date = $date;
        $this->helperDelivery = $helperDelivery;
        $this->leadTimeFactory = $leadtimeFactory;
        $this->deliverytype = $deliverytype;
        $this->customerAddress = $customerAddress;
        $this->profileFactory = $profileFactory;
        $this->messageFactory = $messageFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->carrier = $carrier;
        $this->_helperPrice = $helperPrice;
        $this->taxCalculation = $taxCalculation;
        $this->priceCurrency = $priceCurrency;
        $this->profileRepository  = $profileRepository;
        $this->resourceConnection = $resourceConnection;
        $this->filterBuilder =  $filterBuilder;
        $this->_authSession = $authSession;
        $this->_timeSlotFactory = $timeSlotsFactory;
        $this->customerRepository = $customerRepository;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
        $this->sessionManager = $sessionManager;
        $this->orderRepository = $orderRepository;
        $this->_deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
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
     * @param $profile_id
     * @return array
     */
    public function getProductSubscriptionProfile($profile_id){
        $collection = $this->collectionProductCart->create()->getCollection();
        $collection->addFieldToSelect('product_id');
        $productCollection = $collection->addFieldToFilter('profile_id', [$profile_id]);
        $arr_products = [];
        foreach ($productCollection as $product) {
            $arr_products[] = $product->getProductId();
        }
        return $arr_products;
    }

    /**
     * Get product have time slot in subscription profile
     *
     * @param $profileId
     *
     * @return object
     */
    public function getProductHaveTimeSlot($profileId, $isOrigin = false)
    {
        $collection = $this->collectionProductCart->create()->getCollection();
        $collection->addFieldToFilter('profile_id', $profileId, $isOrigin)
            ->addFieldToFilter('delivery_time_slot', ['notnull' => true]);
        return $collection;
    }

    /**
     * @param $id
     * @return array
     */
    public function getArrProductCart($id){

        /*if(isset($this->localStorage['profile'][$id]['product'])) {
            return $this->localStorage['profile'][$id]['product'];
        }*/

        $collection = $this->collectionProductCart->create()->getCollection();
        $collection->addFieldToSelect('*');
        $productProfileCollection = $collection->addFieldToFilter('profile_id', $id);

        $arrProductProfile = [];
        foreach ($productProfileCollection as $_arrDetail) {
            $arrProductProfile[$_arrDetail['product_id']]['profile'] = $_arrDetail;
        }

        // Query to someinformation from product
        $arrPId = array_keys($arrProductProfile);

        $productCollection = $this->collectionProduct->create();
        $productCollection
            -> addAttributeToSelect("*")
            -> addAttributeToFilter("entity_id", ['in' => $arrPId ])
            -> addAttributeToFilter("status", ['eq' => 1 ]);
        foreach ($productCollection as $objProduct) {
            $arrProductProfile[$objProduct->getData("entity_id")]['details'] = $objProduct;
        }

        //$this->localStorage['profile'][$id]['product'] = $arrProductProfile;

        return $arrProductProfile;
    }

    /**
     * @param ProfileModel $profile
     * @return DataObject[]
     */
    public function getProfileItemsByProfile(
        \Riki\Subscription\Model\Profile\Profile $profile
    ) {
    
        $profileId = $profile->getId();

        if (!isset($this->loadedProfileItems[$profileId])) {
            /** @var \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\Collection $collection */
            $collection = $this->collectionProductCart->create()->getCollection();
            $collection->addFieldToSelect('*')
                ->addFieldToFilter('profile_id', $profileId);

            $items = $collection->getItems();

            foreach ($items as $item) {
                //need load single product to avoid miss some product data
                $product = $this->productRepository->getById($item->getproductId());

                if ($product && $product->getId()) {
                    $this->_eventManager->dispatch('profile_item_load_after', [
                        'profile'   =>  $profile,
                        'profile_item'  =>  $item,
                        'product'   =>  $product
                    ]);
                    $item->setProduct($product);
                }
            }

            $this->loadedProfileItems[$profileId] = $items;
        }

        return $this->loadedProfileItems[$profileId];
    }

    /**
     * get current profile items from session
     *
     * @param $profile
     * @return array
     */
    public function getCurrentProfileItemsFromSessionData($profile)
    {
        /*list item of profile*/
        $items = [];
        $profileDataFromSession = $this->sessionManager->getProfileData();

        if ($profileDataFromSession) {
            $profileId = $profile->getId();

            if (isset($profileDataFromSession[$profileId])) {
                $profileData = $profileDataFromSession[$profileId];

                if (isset($profileData['product_cart']) && !empty($profileData['product_cart'])) {
                    foreach ($profileData['product_cart'] as $item) {
                        try {
                            //need load single product to avoid miss some product data
                            $product = $this->productRepository->getById($item->getProductId());
                        } catch (NoSuchEntityException $e) {
                            $this->_logger->info(sprintf('Product ID #%s doesn\'t exist.', $item->getProductId()));
                            $product = null;
                        }

                        if ($product && $product->getId()) {
                            $this->_eventManager->dispatch('profile_item_load_after', [
                                'profile'   =>  $profile,
                                'profile_item'  =>  $item,
                                'product'   =>  $product
                            ]);

                            // Fix PHP Fatal error:  Uncaught Exception: Serialization of 'Closure' when change qty
                            $a = clone $item;
                            $a->setProduct($product);

                            $items[] = $a;
                        }
                    }
                }
            }
        }

        return $items;
    }

    /**
     * @param $arr_products
     * @return mixed
     */
    public function getAttributesProduct($arr_products){

        if (is_array($arr_products) && count($arr_products) > 0) {
            $collection = $this->collectionProduct->create();
            $collection->addAttributeToSelect('delivery_type');
            $collection->addAttributeToFilter('entity_id', $arr_products);
            return $collection;
        }
    }

    /**
     * @param $id
     * @return \Riki\Subscription\Model\Profile\Profile | bool
     */
    public function load($id)
    {
        if (! isset($this->localStorage[$id])) {
            $objProfile = $this->profileFactory->create()->load($id);

            if (empty($objProfile) || empty($objProfile->getId())) {
                return $this->localStorage[$id] = false;
            }

            $this->localStorage[$id] = $objProfile;
        }

        return $this->localStorage[$id];
    }

    /**
     * Return next delivery date of main profile
     * @param $profileId
     * @return array
     */
    public function getNextDeliveryDateProfile($profileId) {
        $profileData = $this->load($profileId);
        if ($profileData->getId()){
            return $profileData->getData('next_delivery_date');
        }
        return null;
    }
    /**
     * That profile must belong to customer,
     *
     * @param $customerId
     * @param $profileId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isHaveViewProfilePermission($customerId, $profileId)
    {
        $objProfile = $this->load($profileId);

        if ($objProfile === false) {
            return true;
        }

        return ($objProfile->getData("customer_id") == $customerId);
    }

    public function getPrefectureCodeOfRegion($arrRegion){
        $regionModel = $this->_regionFactory->create()->getCollection();
        $region_code = $regionModel->addFieldToFilter('main_table.region_id', $arrRegion);
        $prefecture =[];
        foreach ($region_code as $region) {
            $prefecture[] = $region->getCode();
        }
        return $prefecture;
    }
    public function calNextOrderDate($next_delivery, $region, $productIds, $excludeBufferDays = false)
    {
        $prefecture = $this->getPrefectureCodeOfRegion($region);
        if (!$prefecture) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Cannot load Prefecture of profile'));
        }

        if ($excludeBufferDays) {
            $buffer_date = 0;
        } else {
            $buffer_date = $this->helperDelivery->getBufferDate();
        }

        $delivery_type = $this->getDeliveryType($productIds);
        if (sizeof($delivery_type) == 0) {
            $delivery_type[] ='normal';
        }
        $leadtime = $this->getShippingLeadTimeByPrefecture($prefecture, $delivery_type);
        if ($leadtime and $posId = $leadtime->getData('warehouse_id')) {
            $dayDeducted = (int)$leadtime->getData('shipping_lead_time') + $buffer_date;
            for ($i = 1; $i<= $dayDeducted; $i++) {
                $date_tmp = $this->_date->date('Y-m-d', strtotime($next_delivery." -".$i." day"));
                if ($this->checkWHHoliday($date_tmp, $posId)) {
                    $dayDeducted +=1;
                }
            }
            $cut_off_date = $this->_date->date('Y-m-d', strtotime($next_delivery." -".$dayDeducted." day"));
        } else {
            $cut_off_date = $this->_date->date('Y-m-d', strtotime($next_delivery." -".$buffer_date." day"));
        }
        $timestamp = strtotime($cut_off_date);

        $objDate  = new \DateTime();
        $objDate->setTimestamp($timestamp);

        return $objDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }
    public function checkWHHoliday($date, $posId){
        // warehouse non working on saturday
        if (date('l', strtotime($date)) == 'Saturday') {
            if ($this->helperDelivery->getHolidayOnSaturday($posId)) {
                return true;
            }
        }
        // warehouse non working on sunday
        if (date('l', strtotime($date)) == 'Sunday') {
            if ($this->helperDelivery->getHolidayOnSunday($posId)) {
                return true;
            }
        }
        // this day in list special list holiday of japan
        if ($this->helperDelivery->isSpecialHoliday($posId, $date)) {
            return true;
        }
        return false;
    }

    /**
     * Get MAX shipping lead time by prefecture (apply only for subscription module)
     *
     * @param $prefecture: code prefecture
     * @param $delivery_type: array delivery_type
     * @return mixed
     */
    public function getShippingLeadTimeByPrefecture($prefecture, $delivery_type, $callback = true){
        $leadTimeModel = $this->leadTimeFactory->create()->getCollection()->addActiveToFilter();
        $leadTimeModel->addFieldToFilter('pref_id', $prefecture);
        $leadTimeModel->addFieldToFilter('delivery_type_code', $delivery_type);
        $leadTimeModel->addFieldToFilter('is_active', 1);
        $leadTimeModel->setOrder('shipping_lead_time', 'DESC');
        if (sizeof($leadTimeModel) >0) {
            return $leadTimeModel->getFirstItem();
        } else {
            $tokyo = $this->_regionFactory->create()->load('Tokyo', 'default_name');
            if ($tokyo->getId()) {
                $prefecture[] = $tokyo->getCode();
                if (!$callback) {
                    return false;
                }
                return $this->getShippingLeadTimeByPrefecture($prefecture, $delivery_type, false);
            }
        }
        return false;
    }

    /**
     * Get All delivery Type in a profile
     *
     * @param int $profile_id
     * @return array
     */
    public function getDeliveryTypeOfProfile($profile_id = 0)
    {
        $arr_delivery_text = $this->deliverytype->getOptionArray();
        $arr_delivery = [];
        $deliveryTextList = [];
        if ($profile_id) {
            $products = $this->getProductSubscriptionProfile($profile_id);
            $arr_delivery = $this->getDeliveryType($products);
            foreach ($arr_delivery as $delivery) {
                if (isset($arr_delivery_text[$delivery]) and !in_array($arr_delivery_text[$delivery], $deliveryTextList)) {
                    $deliveryTextList[] = $arr_delivery_text[$delivery];
                }
            }
        }
        return implode(', ', $deliveryTextList);
    }

    /**
     * Conver Delivery type to priority delivery type
     *
     * @param $arrDeliveryType array
     */
    public function convertDeliveryType($arrDeliveryType){
    }

    public function definePriorityDeliveryType(){
    }

    /**
     * @param $arr_products
     * @return array
     */
    public function getDeliveryType($arr_products){

        asort($arr_products);

        $key = implode("-", $arr_products);
        if (isset($this->simpleLocalStorage['delivery-type-by-product'][$key])) {
            return $this->simpleLocalStorage['delivery-type-by-product'][$key];
        }

        $deliveryType = [];
        if (is_array($arr_products) && count($arr_products) > 0) {
            foreach ($arr_products as $productId) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $_product = $objectManager->get('Magento\Catalog\Model\Product')->load($productId);
                $deliveryType[] =$_product->getData('delivery_type');
            }
        }

        $this->simpleLocalStorage['delivery-type-by-product'][$key]  = $deliveryType;

        return $this->simpleLocalStorage['delivery-type-by-product'][$key];
    }

    public function getTimeSlotOfProfile($profile_id){
        $productCart = $this->collectionProductCart->create()->getCollection();
        $productCart->addFieldToFilter('profile_id', $profile_id);
        $productCart->addFieldToSelect('delivery_time_slot');

        $timeSlot = [];
        foreach ($productCart as $product) {
            if ($product->getData('delivery_time_slot') != null and !in_array($product->getData('delivery_time_slot'), $timeSlot)) {
                $timeSlot[] = $product->getData('delivery_time_slot');
            }
        }
        return implode(', ', $timeSlot);
    }

    /**
     * Get Unit Case Product Cart Profile
     *
     * @param $profile_id
     *
     * @param $product_id
     *
     * @return mixed
     */
    public function getUnitCaseProductCartProfile($profile_id, $product_id){
        $productCart = $this->collectionProductCart->create()->getCollection();
        $productCart->addFieldToFilter('profile_id', $profile_id);
        $productCart->addFieldToFilter('product_id', $product_id);
        $productCart->addFieldToSelect('unit_case');

        return $productCart->getData('unit_case');
    }

    public function getAddressNameOfProfile($profile_id){
        return '';
    }

    public function getAddressArrOfProfile($profileId, $attribute){
        $profileModel = $this->profileFactory->create()->load($profileId);
        if ($profileModel->getId()) {
            $profileId = $profileModel->getId();
        }
        $arrProduct = $this->getArrProductCart($profileId);
        $arrAddress = [];
        $result = [];
        foreach ($arrProduct as $product) {
            if (!isset($product['profile'])) {
                return [];
            }
            $arrAddress[] = $product['profile']->getData('shipping_address_id');
        }
        if ($attribute == 'region_id') {
            $objAddress = $this->customerAddress->create()->getCollection();
            $objAddress->addFieldToFilter('entity_id', $arrAddress);
            $result = [];
            foreach ($objAddress as $address) {
                $result[] = $address->getData($attribute);
            }
        }
        if ($attribute == 'riki_nickname') {
            foreach ($arrAddress as $addressId) {
                if ($this->getAddressNameByAddressId($addressId) != null) {
                    if (!in_array($this->getAddressNameByAddressId($addressId), $result)) {
                        $result[] = $this->getAddressNameByAddressId($addressId);
                    }
                }
            }
        }
        return $result;
    }
    public function getAddressNameByAddressId($addressId){
        $customerAddress = $this->customerAddress->create()->load($addressId);
        $rikiNickName = $customerAddress->getCustomAttribute('riki_nickname');
        if ($rikiNickName) {
            return $rikiNickName->getValue();
        }
        return null;
    }

    /**
     * Get address type of customer address
     *
     * @param $addressId
     * @return mixed|string
     */
    public function getCustomerAddressType($addressId)
    {
        $address = $this->customerAddress->create()->load($addressId);
        if ($address->getId()) {
            $addressType = $address->getData('riki_type_address');
            return $addressType;
        }
        return '';
    }

    /**
     * @param $messageId
     * @param $message
     * @return bool|mixed
     */
    public function saveMessage($messageId, $message){

        $giftMessage = $this->messageFactory->create();
        if ($messageId != '' && $messageId != null) {
            $giftMessage->load($messageId);
        }
        if ($message == '') {
            if ($giftMessage->getId()) {
                try {
                    $giftMessage->delete();
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
            return false;
        }
        if (trim($message[0]) == '' || trim($message[1] == '' || trim($message[2] == ''))) {
            if ($giftMessage->getId()) {
                try {
                    $giftMessage->delete();
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
            return false;
        }
        if (trim($message[0]) != '' && trim($message[1] != '' && trim($message[2] != ''))) {
            try {
                $giftMessage->setSender(
                    $message[0]
                )->setRecipient(
                    $message[1]
                )->setMessage(
                    $message[2]
                )->setCustomerId(
                    0
                )->save();
                return $giftMessage->getId();
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
        return false;
    }

    public function getShippingFeeByProfileId($profileId, $storeId){
        $profileModel = $this->profileFactory->create()->load($profileId);
        if ($profileModel->getId()) {
            $productCart = $this->collectionProductCart->create()->getCollection();
            $productCart->addFieldToFilter('profile_id', $profileModel->getId());
            $shippingFee = $this->getShippingFeeView($productCart, $storeId);
            return $this->_helperPrice->currency($shippingFee, true, false);
        }
        return $this->_helperPrice->currency(0, true, false);
    }

    public function getShippingFeeView($arrProductCart, $storeId){
        $productCartId = [];
        foreach ($arrProductCart as $productCart) {
            $productCartId[$productCart->getData('product_id')] = $productCart->getData('qty');
        }
        if (!empty($productCartId)) {
            $filter = $this->searchCriteriaBuilder->addFilter('entity_id', array_keys($productCartId), 'in')->create();
            $productRepository = $this->productRepository->getList($filter);
            $productRepository->getItems();
            foreach ($productRepository->getItems() as $product) {
                $product->setData('qty', $productCartId[$product->getId()]);
            }
            $baseShippingFee = $this->carrier->calculateShippingFee($productRepository);
            $shippingFee = $this->getShippingInclueTax($baseShippingFee, $storeId);
            return $shippingFee;
        }
        return 0;
    }
    public function getShippingInclueTax($shippingFee, $storeId = null){
        $shippingFee = (float) $shippingFee;
        $shippingTaxSetting = $this->scopeConfig->getValue(self::XML_PATH_SHIPPING_TAX_CONFIG, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $taxClass = $this->scopeConfig->getValue(
            self::XML_PATH_TAX_CLASS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($taxClass) {
            $taxRate = $this->taxCalculation->getCalculatedRate($taxClass);
        } else {
            return $shippingFee;
        }
        $taxCartDisplay = $this->scopeConfig->getValue(self::XML_PATH_TAX_CART_DISPLAY_CONFIG, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        if ($shippingTaxSetting) {//calculate shipping fee include tax
            $shippingFeeExclTax = round($shippingFee * 100 / (100 + $taxRate));
            $shippingFeeTax = $shippingFee - $shippingFeeExclTax;
        } else {//calculate shipping fee exclude tax
            $shippingFeeExclTax = $shippingFee;
            $shippingFeeTax = $shippingFee * $taxRate / 100;
        }
        if ($taxCartDisplay == 1) {//exclude tax
            return $shippingFeeExclTax;
        } else {
            return $shippingFeeExclTax + $shippingFeeTax;
        }
    }

    public function isProfileEditable($profile)
    {
        $nextOrderDate = $profile->getData('next_order_date');
        $OrderDate = $this->_date->gmtDate('Ymd', $nextOrderDate);
        $nextDeliveryDate = $profile->getData('next_delivery_date');
        $DeliveryDate = $this->_date->gmtDate('Ymd', $nextDeliveryDate);
        $origin_date =  $this->timezone->formatDateTime($this->_date->gmtDate(), 2);
        $currentDate = $this->_date->gmtDate('Ymd', $origin_date);
        if ($OrderDate <= $currentDate && $currentDate < $DeliveryDate) {
            return false;
        } else {
            return true;
        }
    }

    public function CheckAndMakeTmpProfile($profile, $arrResult)
    {
        $profileId = $profile->getData('profile_id');
        if ($this->checkProfileHaveVersion($profileId)) {
            $profileMain =  $this->profileFactory->create()->load($profileId, null, true);
            if ($profileMain->getId()) {
                $profile =  $profileMain;
            }
        }
        $profileLinkCollection = $this->profileLinkCollection->create()
            ->addFieldToFilter('profile_id', $profile->getData('profile_id'))->setOrder('link_id', 'desc');
        if (count($profileLinkCollection) > 0) {
            // Have exits profile tmp for this profile id
            return null;
        } else {
            // Make new
            $this->makeNewTmpSubscriptionProfile($profile, $arrResult);
        }
    }

    /**
     * Profile have tmp
     *
     * @param $profileId
     *
     * @return bool|object (object of temp profile)
     */

    public function getTmpProfile($profileId)
    {
        $profileLinkCollection = $this->profileLinkCollection->create()
            ->addFieldToFilter('profile_id', $profileId)->setOrder('link_id', 'desc');
        if ($profileLinkCollection->getSize() > 0) {
            return $profileLinkCollection->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * @param $mainProfileId
     * @return false|\Riki\Subscription\Model\Profile\Profile
     */
    public function getTmpProfileModel($mainProfileId)
    {
        if (!isset($this->loadedTmpProfiles[$mainProfileId])) {
            $this->loadedTmpProfiles[$mainProfileId] = false;

            $link = $this->getTmpProfile($mainProfileId);

            if ($link) {
                $tmpProfileId = $link->getData('linked_profile_id');

                $this->loadedTmpProfiles[$mainProfileId] = $this->profileFactory->create()
                    ->load($tmpProfileId);
            }
        }

        return $this->loadedTmpProfiles[$mainProfileId];
    }

    /**
     * Get main profile by tmp profile id
     *
     * @param $profileTmpId
     * @return bool|DataObject
     */
    public function getProfileMainByProfileTmpId($profileTmpId)
    {
        $profileLinkCollection = $this->profileLinkCollection->create();

        $profileLinkCollection->addFieldToFilter(
            'linked_profile_id',
            $profileTmpId
        )->setOrder(
            'link_id',
            'desc'
        );

        if ($profileLinkCollection->getSize() > 0) {
            $mainProfileId = $profileLinkCollection->setPageSize(1)->getFirstItem()->getProfileId();
            $mainProfile = $this->profileFactory->create()->load($mainProfileId, null, true);
            if ($mainProfile->getId()) {
                return $mainProfile;
            }
        }

        return false;
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
     * Make new tmp subscription profile
     *
     * @param $profile
     * @param $arrResult
     *
     * @throws \Exception
     */
    public function makeNewTmpSubscriptionProfile($profile, $arrResult, $linkType = 1)
    {
        $newTmpProfileId = null;
        $mainProfileId = $profile->getData('profile_id');
        $arrProductCart = $this->getArrProductCartForNormalSubscriptionAndHanpukaiSequence($profile->getId(), $arrResult);
        $profileModel = $this->profileFactory->create();
        $region = $this->getAddressArrOfProfile($profile->getId(), 'region_id');
        $productIds = [];
        foreach ($arrProductCart as $product) {
            /** @var \Magento\Catalog\Model\Product\Interceptor $productDetail */
            if (array_key_exists('details', $product)) {
                $productDetail = $product['details'];
                $productIds[] = $productDetail->getId();
            }
        }

        $sAdminUpdatedBy = '';
        if ($this->_authSession->getUser()) {
            $sAdminUpdatedBy = $this->_authSession->getUser()->getUserName();
        }

        $orderTimes = $profile->getData('order_times') + 1;
        $tmpProfileData = $profile->getData();
        unset($tmpProfileData['profile_id']);
        $tmpProfileData['next_delivery_date'] = $arrResult[1]['delivery_date'];

        $excludeBufferDays = $this->getExcludeBufferDays($profile->getId());
        $tmpProfileData['next_order_date'] = $this->calNextOrderDate($arrResult[1]['delivery_date'], $region, $productIds, $excludeBufferDays);
        $tmpProfileData['status'] = ProfileModel::STATUS_ENABLED;
        $tmpProfileData['order_times'] = $orderTimes;
        $tmpProfileData['created_date'] = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        $tmpProfileData['updated_date'] = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        $tmpProfileData['create_order_flag'] = 0;
        $tmpProfileData['type'] = 'tmp';
        $tmpProfileData['admin_updated_by'] = $sAdminUpdatedBy;
        $profileModel->setData($tmpProfileData);

        $profileModel->save();
        $newTmpProfileId = $profileModel->getId();

        if ($newTmpProfileId) {
            $profileModel = $this->profileFactory->create()->load($newTmpProfileId);
            if ($profileModel->getId()) {
                foreach ($arrProductCart as $product) {
                    $productCart = $product['profile'];
                    $productCartNew = $this->collectionProductCart->create();
                    $data = $productCart->getData();
                    unset($data['cart_id']);
                    $data['profile_id'] = $newTmpProfileId;
                    $productCartNew->setData($data);

                    $productCartNew->save();
                }
            }
        }

        if ($newTmpProfileId) {
            $profileLinkModelNew = $this->profileLinkModelFactory->create();
            $profileLinkModelNew->setData('profile_id', $mainProfileId);
            $profileLinkModelNew->setData('linked_profile_id', $newTmpProfileId);
            $profileLinkModelNew->setData('link_type_id', $linkType);

            $profileLinkModelNew->save();
        }
    }

    /**
     * Get Profile data by query direct to sql.
     *
     * @param $profileId
     *
     * @return array()
     */
    public function getProfileDataWithId($profileId)
    {
        $connection = $this->resource->getConnection('sales');
        $profileCollection = $connection->select('*')->from([
            'sp' => $connection->getTableName('subscription_profile')
        ])->where('sp.profile_id = ?', $profileId);
        $profileData = $connection->fetchAll($profileCollection);
        if (count($profileData) > 0) {
            return $profileData[0];
        } else {
            return null;
        }
    }

    /**
     * Calculate delivery date
     *
     * @param $numberDelivery
     * @param $profileItem
     * @param $deliveryDateOrigin
     *
     * @return string
     */
    public function calculateDeliveryDate($numberDelivery, $profileItem, $deliveryDateOrigin)
    {
        $deliveryDate = null;
        $profileId = null;
        if ($numberDelivery == 0) {
            return $deliveryDateOrigin;
        } else {
            if (is_object($profileItem)) {
                $frequencyUnit = $profileItem->getData('frequency_unit');
                $frequencyInterval = $profileItem->getData('frequency_interval');
                $profileId = $profileItem->getId();
            } else {
                $frequencyUnit = $profileItem['frequency_unit'];
                $frequencyInterval = $profileItem['frequency_interval'];
                $profileId = $profileItem['profile_id'];
            }
            if ($this->checkProfileHaveVersion($profileId) === false) {
                $deliveryDateN1 = $deliveryDateOrigin;
            } else {
                // If Profile have version get origin data
                $profileData = $this->getProfileDataWithId($profileId);
                if (is_array($profileData) && array_key_exists('next_delivery_date', $profileData)) {
                    $deliveryDateN1 = $profileData['next_delivery_date'];
                    $frequencyUnit = $profileData['frequency_unit'];
                    $frequencyInterval = $profileData['frequency_interval'];
                } else {
                    $deliveryDateN1 = '';
                }
            }

            if (($profileLinkObj = $this->getTmpProfile($profileId))
                && ($tmpProfileModel = $this->getTmpProfileModel($profileId))
            ) {
                if ($numberDelivery == 1) {
                    return $tmpProfileModel->getData('next_delivery_date');
                }
                if ($profileLinkObj->getData('change_type') != 1) {
                    $frequencyUnit = $tmpProfileModel->getData('frequency_unit');
                    $frequencyInterval = $tmpProfileModel->getData('frequency_interval');
                    $deliveryDateN1 = $tmpProfileModel->getData('next_delivery_date');
                    if ($numberDelivery == 2) {
                        $deliveryDate =  $this->calculateDate(
                            $frequencyUnit,
                            false,
                            1,
                            $frequencyInterval,
                            $deliveryDateN1
                        );
                        if ($deliveryDate instanceof \DateTime) {
                            return $deliveryDate->format('Y/m/d');
                        }
                        return 'N/A';
                    }
                }
            }
            if ($frequencyUnit == 'week') {
                $dateInterval = \DateInterval::createFromDateString(
                    ((int)$frequencyInterval * 7 * (int)$numberDelivery) . ' ' . 'day'
                );
                $deliveryDate = $this->timezone->date(new \DateTime($deliveryDateN1))->add($dateInterval);
            } elseif ($frequencyUnit == 'month') {
                $dateInterval = \DateInterval::createFromDateString(
                    ((int)$frequencyInterval * (int)$numberDelivery) . ' ' . 'month'
                );
                $deliveryDate = $this->timezone->date(new \DateTime($deliveryDateN1))->add($dateInterval);
            }
        }


        return $deliveryDate->format('Y/m/d');
    }

    /**
     * Calculate delivery date for tmp
     *
     * @param $profileId
     * @return bool|\DateTime|string
     */
    public function calculateDeliveryDateForTmp($profileId)
    {
        $originProfile = $this->getProfileDataWithId($profileId);
        if (array_key_exists('next_delivery_date', $originProfile)) {
            $deliveryDateN1 = $originProfile['next_delivery_date'];
            $isSkipNextDelivery = $originProfile['skip_next_delivery'];
            $frequencyUnit = $originProfile['frequency_unit'];
            $frequencyInterval = $originProfile['frequency_interval'];

            $nextDeliveryDate = $this->calculateDate($frequencyUnit, $isSkipNextDelivery, 1, $frequencyInterval, $deliveryDateN1);
            $nextDeliveryDate = $this->_deliveryDateGenerateHelper->getLastDateOfMonth(
                $nextDeliveryDate->format('Y-m-d'),
                $originProfile['data_generate_delivery_date']
            );

            if ($this->isDayOfWeekAndUnitMonthAndNotStockPoint($originProfile)) {
                if ($originProfile['day_of_week'] != null
                    && $originProfile['nth_weekday_of_month'] != null
                ) {
                    $nextDeliveryDate = $this->_deliveryDateGenerateHelper->getDeliveryDateForSpecialCase(
                        $nextDeliveryDate,
                        $originProfile['day_of_week'],
                        $originProfile['nth_weekday_of_month']
                    );
                }
            }
            $nextDeliveryDate = $this->timezone->date($nextDeliveryDate);
            return $nextDeliveryDate;
        }
        return false;
    }

    /**
     * Calculate delivery date for tmp
     *
     * @param $profileId
     * @return bool|\DateTime|string
     */
    public function calculateDeliveryDateForProductTmp($profileId, $deliveryDateN1)
    {
        $originProfile = $this->getProfileDataWithId($profileId);
        if (array_key_exists('next_delivery_date', $originProfile)) {
            $isSkipNextDelivery = $originProfile['skip_next_delivery'];
            $frequencyUnit = $originProfile['frequency_unit'];
            $frequencyInterval = $originProfile['frequency_interval'];
            $nextDeliveryDate = $this->calculateDate($frequencyUnit, $isSkipNextDelivery, 1, $frequencyInterval, $deliveryDateN1);
            $nextDeliveryDate = $this->_deliveryDateGenerateHelper->getLastDateOfMonth(
                $nextDeliveryDate->format('Y-m-d'),
                $originProfile['data_generate_delivery_date']
            );
            $nextDeliveryDate = $this->timezone->date($nextDeliveryDate);

            return $nextDeliveryDate;
        }
        return false;
    }

    /**
     * Calculate delivery date with delivery number
     *
     * @param $frequencyUnit
     * @param $isSkipNextDelivery
     * @param $numberDelivery
     * @param $frequencyInterval
     * @param $deliveryDateN1
     *
     * @return \DateTime|string
     */
    public function calculateDate($frequencyUnit, $isSkipNextDelivery, $numberDelivery, $frequencyInterval, $deliveryDateN1)
    {
        $deliveryDate = '';
        if ($frequencyUnit == 'week') {
            if ($isSkipNextDelivery == 0) {
                $dateInterval = \DateInterval::createFromDateString(
                    ((int)$frequencyInterval * 7 * (int)$numberDelivery) . ' ' . 'day'
                );
            } else {
                $dateInterval = \DateInterval::createFromDateString(
                    ((int)$frequencyInterval * 2 * 7 * (int)$numberDelivery) . ' ' . 'day'
                );
            }
            $deliveryDate = $this->timezone->date($deliveryDateN1)->add($dateInterval);
        } elseif ($frequencyUnit == 'month') {
            if ($isSkipNextDelivery == 0) {
                $dateInterval = \DateInterval::createFromDateString(
                    ((int)$frequencyInterval * (int)$numberDelivery) . ' ' . 'month'
                );
            } else {
                $dateInterval = \DateInterval::createFromDateString(
                    ((int)$frequencyInterval * 2 * (int)$numberDelivery) . ' ' . 'month'
                );
            }
            $deliveryDate = $this->timezone->date($deliveryDateN1)->add($dateInterval);
        }
        return $deliveryDate;
    }
    /**
     * Check Profile Have Version
     *
     * @param $profileId
     *
     * @return string | bool
     */
    public function checkProfileHaveVersion($profileId)
    {
        $profileVersionModel = $this->_profileVersion->create()->getCollection();
        $profileVersionModel->addFieldToFilter('rollback_id', $profileId);
        $profileVersionModel->addFieldToFilter('status', true);
        $profileVersionModel->addOrder('moved_to', 'DESC');
        $profileVersionModel->setPageSize(1);
        if ($profileVersionModel->getSize() > 0) {
            return $profileVersionModel->getFirstItem()->getData('moved_to');
        }
        return false;
    }

    /**
     * Get arr product cart for subscription and hanpukai sequence subscription
     *
     * @param $id
     * @param null $arrResult
     * @return array
     */
    public function getArrProductCartForNormalSubscriptionAndHanpukaiSequence($id, $arrResult = null){

        $profileModel  = $this->getProfileDataWithId($id);
        $arrProductProfile = [];
        $isHanpukaiSequence= false;
        $courseModel = null;
        if ($profileModel) {
            $courseModel = $this->courseFactory->create()->load($profileModel['course_id']);
            if ($courseModel->getData('hanpukai_type') == CourseType::TYPE_HANPUKAI_SEQUENCE) {
                $isHanpukaiSequence = true;
            }
        }

        if ($isHanpukaiSequence == false) {
            $collection = $this->collectionProductCart->create()->getCollection();
            $collection->addFieldToSelect('*');
            $productProfileCollection = $collection->addFieldToFilter('profile_id', [$id], true);
            foreach ($productProfileCollection as $_arrDetail) {
                $_arrDetail->setData(
                    'delivery_date',
                    $arrResult[1]['delivery_date']
                );
                $_arrDetail->setData(
                    'created_at',
                    (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)
                );
                $_arrDetail->setData(
                    'updated_at',
                    (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)
                );
                $arrProductProfile[$_arrDetail['product_id']]['profile'] = $_arrDetail;
            }

            // Query to someinformation from product
            $arrPId = array_keys($arrProductProfile);

            $productCollection = $this->collectionProduct->create();
            $productCollection
                ->addAttributeToSelect("*")
                ->addAttributeToFilter("entity_id", ['in' => $arrPId])
                ->addAttributeToFilter("status", ['eq' => 1]);
            foreach ($productCollection as $objProduct) {
                $arrProductProfile[$objProduct->getData("entity_id")]['details'] = $objProduct;
            }
        } else {
            if ($courseModel->getId()) {
                $arrProductHanpukaiSequence
                    = $this->getArrHanpukaiProductIdSequenceByNumberDelivery(($profileModel['order_times'] + 1), $courseModel);
                if (count($arrProductHanpukaiSequence) > 0) {
                    foreach ($arrProductHanpukaiSequence as $productId => $productDetail) {
                        try {
                            $productObj = $this->productRepository->getById($productId);
                        } catch (\Exception $e) {
                            $this->_logger->critical($e);
                            continue;
                        }

                        $productProfileDetail = $this->makeObjectProductCart(
                            $profileModel,
                            $productDetail,
                            $productObj,
                            $arrResult[1]['delivery_date']
                        );
                        $arrProductProfile[$productId]['profile'] = $productProfileDetail;
                        $arrProductProfile[$productId]['details'] = $productObj;
                    }
                }
            }
        }

        return $arrProductProfile;
    }


    /**
     * Make object product cart item
     *
     * @param $profileModel (is array info of subscription profile not model object)
     * @param $dataReplace
     * @param $product
     * @param $newDeliveryDate
     *
     * @return \Magento\Framework\DataObject
     */
    public function makeObjectProductCart($profileModel, $dataReplace, $product, $newDeliveryDate)
    {
        /* @var $product \Magento\Catalog\Model\Product */
        $collection = $this->collectionProductCart->create()->getCollection();
        $collection->addFieldToSelect('*');
        $productProfileCollection = $collection->addFieldToFilter('profile_id', $profileModel['profile_id'], true);
        if ($productProfileCollection->getSize() > 0) {
            $productCartItem = $productProfileCollection->getFirstItem();
            $productCartItem->setData('qty', $dataReplace['qty']);
            $productCartItem->setData('product_type', $product->getTypeId());
            $productCartItem->setData('product_id', $product->getId());
            $productCartItem->setData('product_option', null);
            $productCartItem->setData('parent_item_id', null);
            $productCartItem->setData(
                'created_at',
                (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)
            );
            $productCartItem->setData(
                'updated_at',
                (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)
            );
            $productCartItem->setData('delivery_date', $newDeliveryDate);
            $productCartItem->setData('unit_case', $dataReplace['unit_case']);
            $productCartItem->setData('unit_qty', $dataReplace['unit_qty']);
            return $productCartItem;
        } else {
            // Profile not any product ????
            throw new \Magento\Framework\Exception\LocalizedException(__('Sorry not add product to profile'));
        }
    }

    public function getArrHanpukaiProductIdSequenceByNumberDelivery($numberDelivery, $courseModel)
    {
        $arrResult = [];
        $arrHanpukaiProductIdSequence
            = $this->subscriptionCourseResourceModel->getHanpukaiSequenceProductsData($courseModel);
        foreach ($arrHanpukaiProductIdSequence as $productId => $productDetail) {
            if ($productDetail['delivery_number'] == $numberDelivery) {
                $arrResult[$productId] = $productDetail;
            }
        }
        return $arrResult;
    }

    /*
     * Roll back area
     */
    public function rollBack($profileId)
    {
        $profileHaveTmp = $this->getTmpProfile($profileId);
        $isRollback = false;
        $profileModel = $this->profileFactory->create()->load($profileId);

        $isProfileHaveVersion = $this->checkProfileHaveVersion($profileId);
        if ($isProfileHaveVersion != false) {
            $isRollback = true;
        }

        if ($profileHaveTmp === false) {
            if ($isRollback) {
                $this->expiredVersion($profileId);
            }

            if ($profileModel->getId()) {
                $this->updateMainProfile($profileId, $profileModel, $isRollback);
            }
        } else {
            $typeChange = $profileHaveTmp->getData('change_type');
            if ($typeChange == 1) {
                // Create new version for original profile
                if ($isRollback) {
                    $this->expiredVersion($profileId);
                }
                $this->makeProfileVersionFromTmpProfile($profileId, $profileHaveTmp->getData('linked_profile_id'));
                if ($profileModel->getId()) {
                    $this->updateMainProfile($profileId, $profileModel);
                }
            } else {
                // change type is apply all -> update Tmp for main profile
                if ($isRollback) {
                    $this->expiredVersion($profileId);
                }
                $this->deleteSubscriptionProfileLink($profileHaveTmp->getData('linked_profile_id'));
                $this->updateTmpProfileForMainProfile($profileId, $profileHaveTmp->getData('linked_profile_id'));
            }
        }
    }

    /**
     * Update tmp profile for main profile
     *
     * @param $mainProfile
     * @param $tmpProfile
     */
    public function updateTmpProfileForMainProfile($mainProfileId, $tmpProfileId)
    {
        $profileMainObj = $this->profileFactory->create()->load($mainProfileId);
        $profileTmpObj = $this->profileFactory->create()->load($tmpProfileId);
        if ($profileMainObj->getId() && $profileTmpObj->getId()) {
            $profileTmpData = $profileTmpObj->getData();
            unset($profileTmpData['profile_id']);
            $profileTmpData['type'] = null;
            $profileTmpData['sales_count'] = $profileMainObj->getData('sales_count');
            $profileTmpData['sales_value_count'] = $profileMainObj->getData('sales_value_count');
            $profileTmpData['order_times'] = $profileMainObj->getData('order_times');
            $profileTmpData['publish_message'] = 0;
            $profileMainObj->addData($profileTmpData);

            $profileMainObj->save();
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Not update profile tmp for profile main'));
        }
        $this->updateProductCartForMainProfileFromTmp($mainProfileId, $tmpProfileId);
    }

    /**
     * Update Product Cart For Main Profile From Tmp
     *
     * @param $mainProfileId
     * @param $tmpProfileId
     */
    public function updateProductCartForMainProfileFromTmp($mainProfileId, $tmpProfileId)
    {
        $productCartModelOfMainProfile = $this->collectionProductCart->create()->getCollection();
        $productCartModelOfMainProfile->addFieldToFilter('profile_id', $mainProfileId);
        if ($productCartModelOfMainProfile->getSize() > 0) {
            foreach ($productCartModelOfMainProfile as $item) {
                $item->delete();
            }
        }
        $productCartModelOfTmpProfile = $this->collectionProductCart->create()->getCollection()
            ->addFieldToFilter('profile_id', $tmpProfileId);
        if ($productCartModelOfTmpProfile->getSize() > 0) {
            foreach ($productCartModelOfTmpProfile as $item) {
                $item->setData('profile_id', $mainProfileId);
                if ($item->getData('is_skip_seasonal') and $item->getData('skip_from') <= $item->getData('skip_to') and $item->getData('skip_to') <= $item->getData('delivery_date')) {
                    $item->setData('is_skip_seasonal', null);
                    $item->setData('skip_from', null);
                    $item->setData('skip_to', null);
                }
                $item->save();
            }
        }

        $profileTmpModelObj = $this->profileFactory->create()->load($tmpProfileId);
        $profileTmpModelObj->delete();
    }

    /**
     * Make profile version from tmp profile
     *
     * @param $mainProfile
     * @param $profileTmpId
     */
    public function makeProfileVersionFromTmpProfile($mainProfile, $profileTmpId)
    {
        $profileModel = $this->profileFactory->create()->load($profileTmpId);
        if ($profileModel->getId()) {
            $profileModel->setData('type', 'version');

            $profileModel->save();
            $this->deleteSubscriptionProfileLink($profileTmpId);
            $this->makeLinkProfileVersion($mainProfile, $profileTmpId);
        }
    }

    /**
     * Make Link from tmp profile to main profile when tmp profile is a profile version
     *
     * @param $mainProfileId
     * @param $versionProfileId
     */
    public function makeLinkProfileVersion($mainProfileId, $versionProfileId)
    {
        $profileVersionObj = $this->_profileVersion->create();
        $profileVersionObj->setData(
            'start_time',
            (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)
        );
        $profileVersionObj->setData('name', null);
        $profileVersionObj->setData('description', null);
        $profileVersionObj->setData('rollback_id', $mainProfileId);
        $profileVersionObj->setData('is_campaign', null);
        $profileVersionObj->setData('is_rollback', 0);
        $profileVersionObj->setData('moved_to', $versionProfileId);
        $profileVersionObj->setData('status', 1);
        $profileVersionObj->setData('publish_message', 0);

        $profileVersionObj->save();
    }

    /**
     * Delete subscription link (tmp is profile version or tmp is update for main version
     *
     * @param $linkedProfileId
     */
    public function deleteSubscriptionProfileLink($linkedProfileId)
    {
        $collection = $this->profileLinkCollection->create()
            ->addFieldToFilter('linked_profile_id', $linkedProfileId);
        if ($collection->getSize() > 0) {
            foreach ($collection as $item) {
                $item->delete();
            }
        }
    }

    /**
     * Update main profile
     *
     * @param $profileId
     * @param $profileModel
     */
    public function updateMainProfile($profileId, $profileModel, $isRollBack = false)
    {
        if ($isRollBack == true) {
            $profileModel = $this->profileFactory->create()->load($profileId);
            // When not version createMageOrder + 1 to origin order
//            $profileModel->setData('order_times', $profileModel->getData('order_times') + 1);
        }
        $region = $this->getAddressArrOfProfile($profileId, 'region_id');
        $productIds = $this->getProductSubscriptionProfile($profileId);
        $course = $this->getCourseData($profileModel->getData('course_id'));

        $dayOfWeek = $nthWeekdayOfMonth = null;

        $nextDeliveryDate = $this->_calNextDeliveryDate(
            $profileModel->getData('next_delivery_date'),
            $profileModel->getData('frequency_interval'),
            $profileModel->getData('frequency_unit')
        );

        if ($profileModel->getData('frequency_unit') == 'month') {
            /**
             * Check day of next delivery date equal last day of month.
             */
            $nextDeliveryDateModel = $profileModel->getData('next_delivery_date');
            $isLastDateOfMonth = $this->_deliveryDateGenerateHelper->canCalculatorLastDayOfMonth($nextDeliveryDateModel);
            if ($isLastDateOfMonth) {
                $nextDeliveryDate = $this->_deliveryDateGenerateHelper->getLastDateOfMonth(
                    $nextDeliveryDate,
                    $profileModel['data_generate_delivery_date']
                );
                $profileModel->setData('isLastDateOfMonth', true);
            } else {
                /**
                 * Need set null data data_generate_delivery_date.
                 * Because next_delivery_date does not match with day of last month
                 */
                $profileModel->setData('data_generate_delivery_date', null);
            }

            // NED-638: Calculation of the next delivery date
            // If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
            // AND interval_unit="month"
            // AND not Stock Point
            if ($course->getData('next_delivery_date_calculation_option')
                == \Riki\SubscriptionCourse\Model\Course::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK
                && !$profileModel->getData('stock_point_profile_bucket_id')
            ) {
                if ($profileModel->getData('day_of_week') != null
                    && $profileModel->getData('nth_weekday_of_month') != null
                ) {
                    $dayOfWeek = $profileModel->getData('day_of_week');
                    $nthWeekdayOfMonth = $profileModel->getData('nth_weekday_of_month');
                } else {
                    $dayOfWeek = date('l', strtotime($profileModel->getData('next_delivery_date')));
                    $nthWeekdayOfMonth = $this->_deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                        $profileModel->getData('next_delivery_date')
                    );
                }

                $nextDeliveryDate = $this->_deliveryDateGenerateHelper->getDeliveryDateForSpecialCase(
                    $nextDeliveryDate,
                    $dayOfWeek,
                    $nthWeekdayOfMonth
                );
            }
        }
        $excludeBufferDays = $this->getExcludeBufferDays($profileId);
        $nextOrderDate = $this->calNextOrderDate($nextDeliveryDate, $region, $productIds, $excludeBufferDays);

        $profileModel->setData('skip_next_delivery', false);
        $profileModel->setData('next_delivery_date', $nextDeliveryDate);
        $profileModel->setData('next_order_date', $nextOrderDate);
        $profileModel->setData('create_order_flag', false);
        $profileModel->setData('publish_message', false);
        $profileModel->setData('day_of_week', $dayOfWeek);
        $profileModel->setData('nth_weekday_of_month', $nthWeekdayOfMonth);

        // When Monthly Fee profile generated order successfully,
        // the system need to set profile[is_monthly_fee_confirmed] = 0
        if ($course->getData('subscription_type') == SubscriptionType::TYPE_MONTHLY_FEE &&
            $profileModel->getData('is_monthly_fee_confirmed')) {
            $profileModel->setData('is_monthly_fee_confirmed', 0);
        }

        try {
            $profileModel->save();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        $this->updateProductCart($profileModel);
        $checkLeadTime = $this->checkLeadTimeIsActiveForProfile($profileId, true);
        if (is_array($checkLeadTime) and isset($checkLeadTime['result']) and !$checkLeadTime['result']) {
            $skus = implode(', ', $checkLeadTime['sku']);
            $message = __("Subscription $profileId generated order successfully but it cannot calculate next delivery date /cut-off date because $skus was inactive with warehouse/delivery type/prefecture for both Toyo and Bizex.");
            throw new \Riki\AdvancedInventory\Exception\AssignationException($message);
        }
    }

    public function getExcludeBufferDays($profileId) {
        $profileModel = $this->profileFactory->create()->load($profileId);
        $subsciptionCourse = $this->getCourseData($profileModel->getData('course_id'));
        return $subsciptionCourse->getData('exclude_buffer_days');
    }

    /**
     * Expire
     *
     * @param $profileId
     */
    public function expiredVersion($profileId)
    {
        $profileVersionModel = $this->_profileVersion->create()->getCollection();
        $profileVersionModel->addFieldToFilter('rollback_id', $profileId);
        $profileVersionModel->addFieldToFilter('status', true);
        foreach ($profileVersionModel as $version) {
            $version->setData('status', false);
            try {
                $version->save();
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
    }

    public function _calNextDeliveryDate($time, $frequencyInterval, $strFrequencyUnit)
    {
        $today = date('Y-m-d', strtotime($this->_date->gmtDate()));
        $i = 0;
        do {
            $i++;
            $frequencyIntervalNext = $i * (int)$frequencyInterval;
            $timestamp = strtotime($frequencyIntervalNext . " " . $strFrequencyUnit, strtotime($time));
        } while (strtotime($today) >= $timestamp);
        $objDate  = new \DateTime();
        $objDate->setTimestamp($timestamp);
        //return $objDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        $deliveryDate = $objDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);

/*        $listDeliveryDate= $this->_deliveryDateGenerateHelper->generateDeliveryDate($time,$frequencyInterval,$strFrequencyUnit,3);
        if(is_array($listDeliveryDate)&& count($listDeliveryDate)>0)
        {
            $day = date('d', strtotime($time));
            if(is_array($listDeliveryDate) && isset($listDeliveryDate[$day]) && isset($listDeliveryDate[$day][1])) {
                $deliveryDate =  $listDeliveryDate[$day][1] ;
            }
        }*/

        return $deliveryDate;
    }

    public function updateProductCart($profileModel){
        $profileId = $profileModel->getData('profile_id');
        $hanpukaiQty = $profileModel->getData('hanpukai_qty');
        $frequencyInterval = $profileModel->getData('frequency_interval');
        $strFrequencyUnit = $profileModel->getData('frequency_unit');
        $productCartModel = $this->collectionProductCart->create()->getCollection();
        $productCartModel->addFieldToFilter('profile_id', $profileId, true);
        $error = 0;

        $subscriptionType = $this->hanpukaiHelperData->getHanpukaiType($profileModel->getData('course_id'));
        if ($subscriptionType == 'hsequence') {
            if ($profileModel->getData('status') != 2) {
                $product = $productCartModel->getFirstItem();
                $this->deleteAllProductBefore($profileId);
                $productInfo = [
                    'profile_id' => $profileId,
                    'shipping_address_id' => $product->getData('shipping_address_id'),
                    'billing_address_id' => $product->getData('billing_address_id'),
                    'delivery_date' => $profileModel->getData('next_delivery_date')
                ];
                $newProduct = $this->hanpukaiHelperData->replaceHanpukaiSequenceProduct(
                    $profileModel->getData('course_id'),
                    $profileModel->getData('order_times') + 1,
                    $productInfo
                );
                if (sizeof($newProduct) == 0) {
                    $profileModel->setData('status', 2);
                    $profileModel->save();
                }
                foreach ($newProduct as $itemCart) {
                    $itemQty = $itemCart['qty'];
                    if ($hanpukaiQty > 1) {
                        $itemCart['qty'] = $itemQty * $hanpukaiQty;
                    }
                    $newProductCartModel = $this->collectionProductCart->create();
                    $newProductCartModel->setData($itemCart);
                    try {
                        $newProductCartModel->save();
                    } catch (\Exception $e) {
                        $this->_logger->critical($e);
                    }
                }
            }
        }
        if ($subscriptionType == 'hfixed') {
            $product = $productCartModel->getFirstItem();
            $this->deleteAllProductBefore($profileId);
            $productInfo = [
                'profile_id' => $profileId,
                'shipping_address_id' => $product->getData('shipping_address_id'),
                'billing_address_id' => $product->getData('billing_address_id'),
                'delivery_date' => $profileModel->getData('next_delivery_date')
            ];
            $newProduct = $this->hanpukaiHelperData->replaceHanpukaiFixedProduct($profileModel->getData('course_id'), $productInfo);
            foreach ($newProduct as $itemCart) {
                $itemQty = $itemCart['qty'];
                if ($hanpukaiQty > 1) {
                    $itemCart['qty'] = $itemQty * $hanpukaiQty;
                }
                $newProductCartModel = $this->collectionProductCart->create();
                $newProductCartModel->setData($itemCart);
                try {
                    $newProductCartModel->save();
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
        }
        if ($profileModel->getData('status') != 2) {
            foreach ($productCartModel as $product) {
                if ($product->getData('is_spot')) {
                    $product->delete();
                    continue;
                }
                $productCartDeliveryDate = $this->_calNextDeliveryDate($product->getData('delivery_date'), $frequencyInterval, $strFrequencyUnit);

                if ($profileModel->getData('isLastDateOfMonth')) {
                    //force delivery date for single
                    $productCartDeliveryDate  = $this->_deliveryDateGenerateHelper->getLastDateOfMonth(
                        $productCartDeliveryDate,
                        $profileModel->getData('data_generate_delivery_date')
                    );
                }
                // NED-638: Update the next delivery date of product cart
                // If subscription profile has day_of_week is not null and nth_weekday_of_month is not null
                if ($profileModel->getData('day_of_week') != null
                    && $profileModel->getData('nth_weekday_of_month') != null
                ) {
                    $productCartDeliveryDate = $profileModel->getData('next_delivery_date');
                }

                $product->setData('delivery_date', $productCartDeliveryDate);

                if ($profileModel->isStockPointProfile()) {
                    $originDelivery = $this->_calNextDeliveryDate(
                        $product->getData('original_delivery_date'),
                        $frequencyInterval,
                        $strFrequencyUnit
                    );
                    $product->setData('original_delivery_date', $originDelivery);
                }

                if ($product->getData('is_skip_seasonal') &&
                    $product->getData('skip_from') <= $product->getData('skip_to') &&
                    $product->getData('skip_to') <= $product->getData('delivery_date')
                ) {
                    $product->setData('is_skip_seasonal', null);
                    $product->setData('skip_from', null);
                    $product->setData('skip_to', null);
                }
                try {
                    $product->save();
                } catch (\Exception $e) {
                    $error++;
                    $this->_logger->critical($e);
                }
            }
            if ($error > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Delete all product before
     *
     * @param $profileId
     * @throws \Exception
     */
    public function deleteAllProductBefore($profileId)
    {
        $productCartModel = $this->collectionProductCart->create()->getCollection();
        $productCartModel->addFieldToFilter('profile_id', $profileId, true);
        if ($productCartModel->getSize() > 0) {
            foreach ($productCartModel as $productCartItem) {
                $productCartItem->delete();
            }
        }
    }

    /**
     * Check profile id is tmp return origin profile
     *
     * @param $profileId
     * @return bool
     */
    public function getProfileOriginFromTmp($profileId)
    {
        $profileLinkCollection = $this->profileLinkCollection->create()
            ->addFieldToFilter('linked_profile_id', $profileId)->setOrder('link_id', 'desc');
        if ($profileLinkCollection->getSize() > 0) {
            return $profileLinkCollection->getFirstItem()->getData('profile_id');
        } else {
            return $profileId;
        }
    }

    /**
     * Check profile id is tmp profile
     *
     * @param $profileId
     * @param $profileModel
     *
     * @return bool
     */
    public function isTmpProfileId($profileId, $profileModel = false)
    {
        if (!$profileModel) {
            $profileModel = $this->load($profileId);
        }
        if ($profileModel && $profileModel->getId()) {
            if ($profileModel->getData('type') == 'tmp' || ($profileModel->getData('type') == 'version' and $profileModel->getOrigData('profile_id') == $profileModel->getData('profile_id') )) {
                return true;
            }
        }
        return false;
    }

    /**
     * Re-calculate next order date for profile when update next delviery date
     *
     * @param $nextDeliveryDate
     * @param $profileId
     * @return string
     */
    public function calculatorNextOrderDateFromProfile($nextDeliveryDate, $profileId){
        /*case1: Profile does not have version*/
        $productCartModel = $this->profileRepository->getListProductCart($profileId);
        $arrShippingAddress = [];
        $regionIds = [];
        $productIds = [];
        foreach ($productCartModel->getItems() as $productCartItem) {
            $productIds[] = $productCartItem->getProductId();
            $arrShippingAddress[] = $productCartItem->getShippingAddressId();
        }
        $addressModel = $this->customerAddress->create()->getCollection();
        $addressModel->addFieldToFilter('entity_id', $arrShippingAddress);
        foreach ($addressModel as $address) {
            $regionIds[] = $address->getRegionId();
        }

        $excludeBufferDays = $this->getExcludeBufferDays($profileId);
        $nextOrderDate = $this->calNextOrderDate($nextDeliveryDate, $regionIds, $productIds, $excludeBufferDays);
        return $nextOrderDate;
    }

    /**
     * Delete tmp profile
     *
     * @param $tmpProfile
     *
     * @return bool
     */
    public function deleteTmpProfile($tmpProfile)
    {
        if (is_object($tmpProfile)) {
            $tmpProfileId = $tmpProfile->getData('linked_profile_id');
            $tmpProfile->delete();
            try {
                $profileModel = $this->profileRepository->get($tmpProfileId);
                $this->profileRepository->delete($profileModel);

                return true;
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }

        return false;
    }

    public function UpdateFrequency($profileId, $frequencyUnit, $frequencyInterval)
    {

        if ($this->getTmpProfile($profileId) === false && $this->checkProfileHaveVersion($profileId) === false) {
            // case 1 just has origin profile
            $profileModel = $this->load($profileId);

            $profileModel->setData('frequency_unit', $frequencyUnit);
            $profileModel->setData('frequency_interval', $frequencyInterval);
            $profileModel->save();
        } elseif ($this->getTmpProfile($profileId) === false && $this->checkProfileHaveVersion($profileId) !== false) {
            $profileIdOrigin = $profileId;
            $profileIdVersion = $this->checkProfileHaveVersion($profileId);

            $profileOriginModel = $this->profileFactory->create()->load($profileIdOrigin, null, true);
            $profileOriginModel->setData('frequency_unit', $frequencyUnit);
            $profileOriginModel->setData('frequency_interval', $frequencyInterval);
            $profileOriginModel->save();

            $profileVersionModel = $this->profileFactory->create()->load($profileIdVersion, null, true);
            $profileVersionModel->setData('frequency_unit', $frequencyUnit);
            $profileVersionModel->setData('frequency_interval', $frequencyInterval);
            $profileVersionModel->save();
        } elseif ($this->getTmpProfile($profileId) !== false && $this->checkProfileHaveVersion($profileId) === false) {
            $subProfileLinkObj = $this->getTmpProfile($profileId);
            $mainProfileId = $subProfileLinkObj->getData('profile_id');
            $tmpProfileId = $subProfileLinkObj->getData('linked_profile_id');

            $profileOriginModel = $this->profileFactory->create()->load($mainProfileId, null, true);
            $profileOriginModel->setData('frequency_unit', $frequencyUnit);
            $profileOriginModel->setData('frequency_interval', $frequencyInterval);
            $profileOriginModel->save();

            $profileTmpModel = $this->profileFactory->create()->load($tmpProfileId);
            $profileTmpModel->setData('frequency_unit', $frequencyUnit);
            $profileTmpModel->setData('frequency_interval', $frequencyInterval);
            $profileTmpModel->save();
        } else {
            // case have tmp and version
            $subProfileLinkObj = $this->getTmpProfile($profileId);
            $mainProfileId = $subProfileLinkObj->getData('profile_id');
            $tmpProfileId = $subProfileLinkObj->getData('linked_profile_id');
            $profileIdVersion = $this->checkProfileHaveVersion($profileId);

            $profileOriginModel = $this->profileFactory->create()->load($mainProfileId, null, true);
            $profileOriginModel->setData('frequency_unit', $frequencyUnit);
            $profileOriginModel->setData('frequency_interval', $frequencyInterval);
            $profileOriginModel->save();

            $profileTmpModel = $this->profileFactory->create()->load($tmpProfileId);
            $profileTmpModel->setData('frequency_unit', $frequencyUnit);
            $profileTmpModel->setData('frequency_interval', $frequencyInterval);
            $profileTmpModel->save();

            $profileVersionModel = $this->profileFactory->create()->load($profileIdVersion, null, true);
            $profileVersionModel->setData('frequency_unit', $frequencyUnit);
            $profileVersionModel->setData('frequency_interval', $frequencyInterval);
            $profileVersionModel->save();
        }
    }

    public function updateSalesCount($profileId, $salesCount, $salesValueCount)
    {
        if ($this->getTmpProfile($profileId) === false && $this->checkProfileHaveVersion($profileId) === false) {
            // case 1 just has origin profile
            $profileModel = $this->load($profileId);

            $profileModel->setData('sales_count', $salesCount);
            $profileModel->setData('sales_value_count', $salesValueCount);
            $profileModel->save();
        } elseif ($this->getTmpProfile($profileId) === false && $this->checkProfileHaveVersion($profileId) !== false) {
            $profileIdOrigin = $profileId;
            $profileIdVersion = $this->checkProfileHaveVersion($profileId);

            $profileOriginModel = $this->profileFactory->create()->load($profileIdOrigin, null, true);
            $profileOriginModel->setData('sales_count', $salesCount);
            $profileOriginModel->setData('sales_value_count', $salesValueCount);
            $profileOriginModel->save();

            $profileVersionModel = $this->profileFactory->create()->load($profileIdVersion, null, true);
            $profileVersionModel->setData('sales_count', $salesCount);
            $profileVersionModel->setData('sales_value_count', $salesValueCount);
            $profileVersionModel->save();
        } elseif ($this->getTmpProfile($profileId) !== false && $this->checkProfileHaveVersion($profileId) === false) {
            $subProfileLinkObj = $this->getTmpProfile($profileId);
            $mainProfileId = $subProfileLinkObj->getData('profile_id');
            $tmpProfileId = $subProfileLinkObj->getData('linked_profile_id');

            $profileOriginModel = $this->profileFactory->create()->load($mainProfileId, null, true);
            $profileOriginModel->setData('sales_count', $salesCount);
            $profileOriginModel->setData('sales_value_count', $salesValueCount);
            $profileOriginModel->save();

            $profileTmpModel = $this->profileFactory->create()->load($tmpProfileId);
            $profileTmpModel->setData('sales_count', $salesCount);
            $profileTmpModel->setData('sales_value_count', $salesValueCount);
            $profileTmpModel->save();
        } else {
            // case have tmp and version
            $subProfileLinkObj = $this->getTmpProfile($profileId);
            $mainProfileId = $subProfileLinkObj->getData('profile_id');
            $tmpProfileId = $subProfileLinkObj->getData('linked_profile_id');
            $profileIdVersion = $this->checkProfileHaveVersion($profileId);

            $profileOriginModel = $this->profileFactory->create()->load($mainProfileId, null, true);
            $profileOriginModel->setData('sales_count', $salesCount);
            $profileOriginModel->setData('sales_value_count', $salesValueCount);
            $profileOriginModel->save();

            $profileTmpModel = $this->profileFactory->create()->load($tmpProfileId);
            $profileTmpModel->setData('sales_count', $salesCount);
            $profileTmpModel->setData('sales_value_count', $salesValueCount);
            $profileTmpModel->save();

            $profileVersionModel = $this->profileFactory->create()->load($profileIdVersion, null, true);
            $profileVersionModel->setData('sales_count', $salesCount);
            $profileVersionModel->setData('sales_value_count', $salesValueCount);
            $profileVersionModel->save();
        }
    }

    /**
     * get id list of active profiles filter by customer
     *
     * @param $customerId
     * @param null $subscriptionType
     * @return array
     */
    public function getActiveProfileIdsOfSpecialCustomer($customerId, $subscriptionType = null){

        $select = $this->resource->getConnection('sales')->select()->from(
            'subscription_profile',
            'profile_id'
        )->join(
            'subscription_course',
            'subscription_course.course_id=subscription_profile.course_id',
            'subscription_type'
        )->where(
            'subscription_profile.customer_id = ?',
            $customerId
        )->where(
            'subscription_profile.disengagement_date IS NULL or subscription_profile.disengagement_date = \'\''
        )->where(
            'subscription_profile.disengagement_reason IS NULL or subscription_profile.disengagement_reason = \'\''
        )->where(
            'subscription_profile.disengagement_user IS NULL or subscription_profile.disengagement_user = \'\''
        );

        $profileIdToType = $this->resource->getConnection('sales')->fetchPairs($select);

        $hanpukaiIds = [];
        $subscriptionIds = [];
        foreach ($profileIdToType as $id => $type) {
            if ($type == CourseType::TYPE_SUBSCRIPTION) {
                $subscriptionIds[] = $id;
            } elseif ($type == CourseType::TYPE_HANPUKAI) {
                $hanpukaiIds[] = $id;
            }
        }

        if ($subscriptionType == CourseType::TYPE_SUBSCRIPTION) {
            $result = $subscriptionIds;
        } elseif ($subscriptionType == CourseType::TYPE_HANPUKAI) {
            $result = $hanpukaiIds;
        } else {
            $result = [
                CourseType::TYPE_SUBSCRIPTION   =>  $subscriptionIds,
                CourseType::TYPE_HANPUKAI   =>  $hanpukaiIds,
            ];
        }

        return $result;
    }

    /**
     * Save Spot Product
     */
    public function saveSpotProduct($arrParam, $productCartObjSpotProduct)
    {
        $profileId = $arrParam[Constant::BO_SAVE_SPOT_POST_PARAM_PROFILE_ID];
        $tmpProfile = $this->getTmpProfile($profileId);
        $isVersion = $this->checkProfileHaveVersion($profileId);
        if ($tmpProfile === false && $isVersion === false) {
            /**
             * Case 1 just have origin profile => add spot to main profile
             */
            $result = $this->checkSpotProductIsExistAndIncrease(
                $arrParam[Constant::BO_SAVE_SPOT_POST_PARAM_PROFILE_ID],
                $productCartObjSpotProduct
            );
            if (!$result) {
                return $this->addSpotToProductCart($profileId, $productCartObjSpotProduct);
            }
        } elseif ($tmpProfile === false && $isVersion !== false) {
            /**
             * case 2 not tmp origin have version => add spot to version
             */
            $result = $this->checkSpotProductIsExistAndIncrease(
                $arrParam[Constant::BO_SAVE_SPOT_POST_PARAM_PROFILE_ID],
                $productCartObjSpotProduct
            );
            $versionId = $isVersion;
            if (!$result) {
                return $this->addSpotToProductCart($versionId, $productCartObjSpotProduct);
            }
        } elseif ($tmpProfile !== false && $isVersion === false) {
            /**
             * case 3 have tmp origin not version => add spot to tmp and change type is 1 (only for next)
             */
            $result = $this->checkSpotProductIsExistAndIncrease(
                $arrParam[Constant::BO_SAVE_SPOT_POST_PARAM_PROFILE_ID],
                $productCartObjSpotProduct
            );
            $tmpProfileId = $tmpProfile->getData('linked_profile_id');
            if (!$result) {
                return $this->addSpotToProductCart($tmpProfileId, $productCartObjSpotProduct);
            }
        } elseif ($tmpProfile !== false && $isVersion !== false) {
            /**
             * case 4 have tmp origin have version => add spot to tmp and change type is 1 (only for next)
             */
            $result = $this->checkSpotProductIsExistAndIncrease(
                $arrParam[Constant::BO_SAVE_SPOT_POST_PARAM_PROFILE_ID],
                $productCartObjSpotProduct
            );
            $tmpProfileId = $tmpProfile->getData('linked_profile_id');
            if (!$result) {
                return $this->addSpotToProductCart($tmpProfileId, $productCartObjSpotProduct);
            }
        } else {
            /**
             * Case ??????
             */
            return false;
        }
        return true;
    }

    /**
     * add spot to product cart just make product cart object and replace true profile id
     * Warning Profile id is not main profile id
     *
     * @param $neededProfileId
     * @param $objectNewProduct
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function addSpotToProductCart($neededProfileId, $objectNewProduct, $type = self::SPOT_PRODUCT)
    {
        $productCartNew = $this->collectionProductCart->create();
        $data = $objectNewProduct->getData();
        unset($data['cart_id']);
        $data['profile_id'] = $neededProfileId;
        if ($type == self::MAIN_PRODUCT) {
            $data['is_spot'] = 0;
        } else {
            $data['is_spot'] = 1;
        }
        $productCartNew->setData($data);
        $productCartNew->save();

        return true;
    }

    /**
     * Check spot product is exist
     *
     * @param $profileId
     * @param $objectNewProduct
     * @param $type
     *
     * @throws \Exception
     */
    public function checkSpotProductIsExistAndIncrease($profileId, $objectNewProduct, $type = self::SPOT_PRODUCT)
    {
        if ($this->getTmpProfile($profileId) !== false) {
            $profileId = $this->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        $productCartModel = $this->collectionProductCart->create()->getCollection();
        $productCartModel->addFieldToFilter('profile_id', $profileId);
        $productCartModel->addFieldToFilter('product_id', $objectNewProduct->getData('product_id'));
        if ($type == self::MAIN_PRODUCT) {
            $productCartModel->addFieldToFilter('is_spot', 0);
        } else {
            $productCartModel->addFieldToFilter('is_spot', 1);
        }
        if ($productCartModel->getSize() > 0) {
            foreach ($productCartModel as $item) {
                $item->setQty($item->getQty() + $objectNewProduct->getData('qty'));
                $item->save();
            }
            return true;
        }
        return false;
    }

    /**
     * Save Main Product
     */
    public function saveMainProduct($arrParam, $productCartObjSpotProduct)
    {
        $profileId = $arrParam[Constant::BO_SAVE_SPOT_POST_PARAM_PROFILE_ID];
        $tmpProfile = $this->getTmpProfile($profileId);
        $isVersion = $this->checkProfileHaveVersion($profileId);
        if ($tmpProfile === false && $isVersion === false) {
            /**
             * Case 1 just have origin profile => add spot to main profile
             */
            $result = $this->checkSpotProductIsExistAndIncrease(
                $arrParam[Constant::BO_SAVE_SPOT_POST_PARAM_PROFILE_ID],
                $productCartObjSpotProduct,
                self::MAIN_PRODUCT
            );
            if (!$result) {
                return $this->addSpotToProductCart($profileId, $productCartObjSpotProduct, self::MAIN_PRODUCT);
            }
        } elseif ($tmpProfile === false && $isVersion !== false) {
            /**
             * case 2 not tmp origin have version => add spot to version
             */
            $result = $this->checkSpotProductIsExistAndIncrease(
                $arrParam[Constant::BO_SAVE_SPOT_POST_PARAM_PROFILE_ID],
                $productCartObjSpotProduct,
                self::MAIN_PRODUCT
            );
            $versionId = $isVersion;
            if (!$result) {
                return $this->addSpotToProductCart($versionId, $productCartObjSpotProduct, self::MAIN_PRODUCT);
            }
        } elseif ($tmpProfile !== false && $isVersion === false) {
            /**
             * case 3 have tmp origin not version => add spot to tmp and change type is 1 (only for next)
             */
            $result = $this->checkSpotProductIsExistAndIncrease(
                $arrParam[Constant::BO_SAVE_SPOT_POST_PARAM_PROFILE_ID],
                $productCartObjSpotProduct,
                self::MAIN_PRODUCT
            );
            $tmpProfileId = $tmpProfile->getData('linked_profile_id');
            if (!$result) {
                return $this->addSpotToProductCart($tmpProfileId, $productCartObjSpotProduct, self::MAIN_PRODUCT);
            }
        } elseif ($tmpProfile !== false && $isVersion !== false) {
            /**
             * case 4 have tmp origin have version => add spot to tmp and change type is 1 (only for next)
             */
            $result = $this->checkSpotProductIsExistAndIncrease(
                $arrParam[Constant::BO_SAVE_SPOT_POST_PARAM_PROFILE_ID],
                $productCartObjSpotProduct,
                self::MAIN_PRODUCT
            );
            $tmpProfileId = $tmpProfile->getData('linked_profile_id');
            if (!$result) {
                return $this->addSpotToProductCart($tmpProfileId, $productCartObjSpotProduct, self::MAIN_PRODUCT);
            }
        } else {
            /**
             * Case ??????
             */
            return false;
        }
        return true;
    }
    /**
     * Make object simulate now use for add spot product
     *
     * @param $profileId
     * @param $arrNewProductData
     * @param $arrMultipleNewProduct
     * @return DataObject
     */
    public function makeObjectDataForSimulate($profileId, $arrNewProductData, $arrMultipleNewProduct = [])
    {
        $profileModel = $this->loadProfileModel($profileId);
        $productCartData = $this->makeProductCartData($profileId, $arrNewProductData, $arrMultipleNewProduct);
        $obj = new DataObject();
        $obj->setData($profileModel->getData());
        $obj->setData('course_data', $this->getCourseData($profileModel->getData('course_id')));
        $obj->setData("product_cart", $productCartData);
        return $obj;
    }

    /**
     * @param $profileId
     * @param bool $original
     * @return \Riki\Subscription\Model\Profile\Profile
     */
    public function loadProfileModel($profileId, $original = false)
    {
        if ($this->getTmpProfile($profileId) !== false) {
            $profileId = $this->getTmpProfile($profileId)->getData('linked_profile_id');
        }

        if (!isset($this->loadedProfiles[$profileId])) {
            $this->loadedProfiles[$profileId] = $this->profileFactory->create()->load($profileId, null, $original);
        }

        return $this->loadedProfiles[$profileId];
    }

    /**
     * Make product cart data and new product add
     *
     * @param $profileId
     * @param $arrNewProductData
     * @param $arrMultipleNewProduct
     *
     * @return array|void
     */
    public function makeProductCartData($profileId, $arrNewProductData, $arrMultipleNewProduct = [])
    {
        if ($this->getTmpProfile($profileId) !== false) {
            $profileId = $this->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        $productCartCollection = $this->collectionProductCart->create()->getCollection();
        $productCartCollection->addFieldToFilter('profile_id', $profileId);

        $data = [];
        foreach ($productCartCollection->getItems() as $item) {
            try {
                $productModel = $this->productRepository->getById($item->getData('product_id'));
                if ($productModel && $productModel->getStatus() == 1) {
                    $obj = new DataObject();
                    $obj->setData($item->getData());
                    $data[$obj->getData("cart_id")] = $obj;
                }
            } catch (\Exception $e) {
                $this->_logger->error('Product ID #' . $item->getData('product_id') . ' was delete');
            }
        }

        if (is_array($arrMultipleNewProduct) && count($arrMultipleNewProduct)>0) {
            $arrNewsProductAdd = [];
            $currentObAddCart = null;
            foreach ($arrMultipleNewProduct as $newProduct) {
                $currentObAddCart = $this->addNewProductToDataObject($data, $newProduct);
                if (isset($currentObAddCart['new_product'])) {
                    $objectProductNew = $currentObAddCart['new_product'] ;
                    $arrNewsProductAdd['new_product_'.$objectProductNew->getProductId()] =$objectProductNew;
                    unset($currentObAddCart['new_product']);
                }
            }

            $data = array_merge($currentObAddCart, $arrNewsProductAdd);
        } else {
            $data = $this->addNewProductToDataObject($data, $arrNewProductData);
        }

        return $data;
    }

    /**
     * Add new product to data object
     *
     * @param $data
     * @param $arrNewProductData
     * @return mixed
     */
    public function addNewProductToDataObject($data, $arrNewProductData)
    {
        // get first item data standard and replaced data
        $newProductObj = $this->loadProductById($arrNewProductData['product_id']);
        if (count($data) > 0) {
            foreach ($data as $productCartId => $value) {
                $obj = new DataObject();
                $obj->setData($value->getData());
                $obj->setData('product_id', $arrNewProductData['product_id']);
                $obj->setData('qty', $arrNewProductData['qty']);
                $obj->setData('product_type', $newProductObj->getTypeId());
                $obj->setData('product_options', $arrNewProductData['product_options']);
                $obj->setData('updated_at', (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
                $obj->setData('unit_case', strtoupper($arrNewProductData['unit_case']));
                $obj->setData('unit_qty', $arrNewProductData['unit_qty']);
                $obj->setData('gw_id', $arrNewProductData['gw_id']);
                $obj->setData('gift_message_id', $arrNewProductData['gift_message_id']);
                $obj->setData('parent_item_id', 0);
                $obj->setData('gw_used', null);
                $obj->setData('gift_message_id', null);
                $obj->setData('is_skip_seasonal', null);
                $obj->setData('skip_from', null);
                $obj->setData('skip_to', null);
                $obj->setData('is_spot', $newProductObj->getData('spot_allow_subscription'));
                $obj->setData('cart_id', 'new_product');
                $data[$obj->getData('cart_id')] = $obj;
                return $data;
            }
        }
        return $data;
    }

    /**
     * Load product by id
     *
     * @param $productId
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Exception
     */
    public function loadProductById($productId)
    {
        return $this->productRepository->getById($productId);
    }

    /**
     * Get course data
     *
     * @return mixed
     * @throws \Exception
     */
    public function getCourseData($courseId)
    {
        return $this->courseFactory->create()->load($courseId);
    }

    public function checkCourseIsExistedInProfile($courseId){
        $searchResult = $this->searchCriteriaBuilder->addFilter('course_id', $courseId)->create();
        $profileRepository = $this->profileRepository->getList($searchResult);
        if (sizeof($profileRepository->getItems()) > 0) {
            return false;
        }
        return true;
    }

    public function checkFrequencyIsExistedInProfile($frequencyUnit, $frequencyInterval){
        $filter1 = $this->filterBuilder->setField('frequency_interval')
            ->setValue($frequencyInterval)
            ->create();
        $filter2 = $this->filterBuilder->setField('frequency_unit')
            ->setValue($frequencyUnit)
            ->create();
        $searchResult = $this->searchCriteriaBuilder->addFilters([$filter1])->addFilters([$filter2])->setPageSize(1)->setCurrentPage(1)->create();
        $profileRepository = $this->profileRepository->getList($searchResult);
        if (sizeof($profileRepository->getItems()) > 0) {
            return false;
        }
        return true;
    }
    public function checkCatalogIsExistedInCourseAndProfile($catalogId){
        $connection = $this->resourceConnection->getConnection('sales');
        $select = $connection->select()->from(
            'subscription_course_category'
        )->where(
            'category_id' . ' = ?',
            $catalogId
        );
        $arrCourseId = $connection->fetchCol($select);
        if ($arrCourseId) {
            $searchResult = $this->searchCriteriaBuilder->addFilter('course_id', $arrCourseId, 'in')->create();
            $profileModel = $this->profileRepository->getList($searchResult);
            if (sizeof($profileModel->getItems())) {
                return false;
            }
            return true;
        }
        return true;
    }
    public function checkCustomerIsExistedInProfile($customerId){
        $searchResult = $this->searchCriteriaBuilder->addFilter('customer_id', $customerId)->create();
        $profileRepository = $this->profileRepository->getList($searchResult);
        if (sizeof($profileRepository->getItems()) > 0) {
            return false;
        }
        return true;
    }
    public function getSubscriptionCourseResourceModel()
    {
        return $this->subscriptionCourseResourceModel;
    }

    /**
     * Convert date format
     *
     * @param string $date
     * @param string $format
     *
     * @return string
     */
    public function formatDate($date, $format = 'Y/m/d')
    {
        return $this->timezone->date($date)->format($format);
    }
    public function getArrThreeDeliveryOfProfile($profileId, $ndelivery)
    {
        $result = null;
        $profileModel = $this->profileFactory->create()->load($profileId);
        if ($profileModel->getId()) {
            $arrThreeDelivery = $this->calculateNextDelivery($profileModel);
            switch ($ndelivery) {
                case 'n':
                    $result = $arrThreeDelivery[0];
                    break;
                case 'n+1':
                    $result = $arrThreeDelivery[1];
                    break;
                case 'n+2':
                    $result = $arrThreeDelivery[2];
                    break;
                default:
            }
        }
        return $result;
    }

    /**
     * @param $profileItem
     * @return array
     */
    public function calculateNextDelivery($profileItem,$isHanpukai = false)
    {
        $arrResult = [];
        $profileTmpId = $this->isProfileHaveTmp($profileItem->getData('profile_id'));
        $arrStatus = $this->calculateStatus($profileItem);
        for ($i = 0; $i < 3; $i++) {
            $checkedProfile = $this->getTmpProfileModel($profileItem->getId());
            if($i == 0) {
                $deliveryDate = $profileItem->getData('next_delivery_date');
            }
            if($i == 1) {
                $deliveryDate = $this->calculateDeliveryDate(
                    $i,
                    $profileItem,
                    $profileItem->getData('next_delivery_date')
                );
                /*Has temp*/
                if($checkedProfile) {
                    $deliveryDate =  $checkedProfile->getData('next_delivery_date');

                } else {
                    $deliveryDate = $this->getFinalDeliveryDate($profileItem,$deliveryDate);
                }
            }
            if ($i == 2) {
                /*Has temp*/
                if($checkedProfile) {
                    $deliveryDate = $this->calculateDeliveryDate(
                        $i-1,
                        $checkedProfile,
                        $checkedProfile->getData('next_delivery_date')
                    );
                    $deliveryDate = $this->getFinalDeliveryDate($checkedProfile,$deliveryDate);

                } else {
                    $deliveryDate = $this->calculateDeliveryDate(
                        $i,
                        $profileItem,
                        $profileItem->getData('next_delivery_date')
                    );
                    $deliveryDate = $this->getFinalDeliveryDate($profileItem,$deliveryDate);
                }
            }
            $timeSlot = $this->getSlotName($i, $profileItem);
            $arrResult[$i]['delivery_date'] = $deliveryDate;
            if ($isHanpukai) {
                $isSubStop = $this->hanpukaiHelperData->calculateIsSubStop($profileItem, $i + 1);
                $arrResult[$i]['is_stop'] = $isSubStop;
            }
            $arrResult[$i]['time_slot'] = $timeSlot;
            $arrResult[$i]['status'] = $arrStatus[$i];
            $arrResult[$i]['link']
                = ($i == 1 && $profileTmpId) ? $this->getLinkTmpSubProfile($profileItem->getData('profile_id')) : '';
        }
        return $arrResult;
    }

    /**
     * @param $profileModel \Riki\Subscription\Model\Profile\Profile
     * @param $nDelivery
     */
    public function getFinalDeliveryDate($profileModel, $deliveryDate) {
        $generateDeliveryDate = $profileModel->getData('data_generate_delivery_date');
        if ($profileModel->getData('frequency_unit')=='month') {
            $deliveryDate = $this->_deliveryDateGenerateHelper->getLastDateOfMonth(
                $deliveryDate,
                $generateDeliveryDate
            );
        }
        // NED-638: Calculation of the next delivery date
        // If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
        // AND interval_unit="month"
        // AND not Stock Point
        if ($this->isDayOfWeekAndUnitMonthAndNotStockPoint($profileModel)) {
            $dayOfWeek = $profileModel->getData('day_of_week');
            $nthWeekdayOfMonth = $profileModel->getData('nth_weekday_of_month');

            $dayOfWeek = $dayOfWeek? $dayOfWeek
                : date('l', strtotime($profileModel->getData('next_delivery_date')));

            $nthWeekdayOfMonth = $nthWeekdayOfMonth? $nthWeekdayOfMonth
                : $this->_deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                    $profileModel->getData('next_delivery_date')
                );

            $deliveryDate = $this->_deliveryDateGenerateHelper->getDeliveryDateForSpecialCase(
                $deliveryDate,
                $dayOfWeek,
                $nthWeekdayOfMonth
            );
        }
        return $deliveryDate;
    }

    /**
     * @param $profileId
     * @return string
     */
    public function getLinkTmpSubProfile($profileId)
    {
        if ($profileId) {
            return $this->getBaseUrlSubcriptionProfile($profileId);
        } else {
            return '';
        }
    }
    /**
     * Get base url subscription profile
     *
     * @param $id
     *
     * @return string
     */
    public function getBaseUrlSubcriptionProfile($id)
    {
        return $this->_getUrl('subscriptions/profile/edit/id/' . (int)$id);
    }
    public function isProfileHaveTmp($profileId)
    {
        if ($this->getTmpProfile($profileId) == false) {
            return false;
        } else {
            return $this->getTmpProfile($profileId)->getData('linked_profile_id');
        }
    }
    /**
     * Calculate status
     *
     * @param $profileItem
     *
     * @return mixed
     */
    public function calculateStatus($profileItem)
    {
        $isTmpProfile = $this->isProfileHaveTmp($profileItem->getData('profile_id'));
        if ($isTmpProfile === false) {
            $arrResult[0] = self::PROFILE_STATUS_EDITABLE;
            $arrResult[1] = self::PROFILE_STATUS_EDITABLE;
            $arrResult[2] = self::PROFILE_STATUS_EDITABLE;
        } else {
            $arrResult[0] = self::PROFILE_STATUS_PLANED;
            $arrResult[1] = self::PROFILE_STATUS_EDITABLE;
            $arrResult[2] = self::PROFILE_STATUS_EDITABLE;
        }
        return $arrResult;
    }
    /**
     * Get Slot Name
     *
     * @param $profileItem
     *
     * @return string
     */
    public function getSlotName($nDelivery, $profileItem)
    {
        $profileId = $profileItem->getData('profile_id');
        $profileVersion = $this->checkProfileHaveVersion($profileId);
        if ($profileVersion and $nDelivery == 0) {
            $collectionProduct = $this->getProductHaveTimeSlot($profileVersion);
        } else {
            $collectionProduct = $this->getProductHaveTimeSlot($profileId, true);
        }
        if ($this->getTmpProfile($profileId) !== false) {
            $profileLinkObj = $this->getTmpProfile($profileId);
            if ($nDelivery == 1 || ($profileLinkObj->getData('change_type') != 1 and $nDelivery == 2)) {
                $profileTmpId = $this->getTmpProfile($profileId)->getData('linked_profile_id');
                $collectionProduct = $this->getProductHaveTimeSlot($profileTmpId, true);
            }
        }

        if ($collectionProduct->getSize() > 0) {
            $firstItem = $collectionProduct->getFirstItem();
            $timeSlotObj = $this->_timeSlotFactory->create()->load($firstItem->getData('delivery_time_slot'));
            if ($timeSlotObj->getId()) {
                return $timeSlotObj->getData('slot_name');
            }
        }
        return 'unspecified';
    }

    /**
     * Check Profile Have Tmp
     *
     * @param $profileId
     *
     * @return string | bool
     */
    public function checkProfileHaveTmp($profileId)
    {
        $profileLinkCollection = $this->profileLinkCollection->create()
            ->addFieldToFilter('profile_id', $profileId)->setOrder('link_id', 'desc');
        if (count($profileLinkCollection) > 0) {
            // Have exits profile tmp for this profile id
            return true;
        } else {
            return false;
        }
    }

    public function checkProfileBelongToCustomer($profileId, $customerId){
        $profileCollection = $this->profileFactory->create()->getCollection();
        $profileCollection->addFieldToFilter('profile_id', $profileId);
        $profileCollection->addFieldToFilter('customer_id', $customerId);
        if ($profileCollection->getSize() >= 1) {
            return true;
        }
        return false;
    }

    /**
     * Get main profile id from tmp profile id
     *
     * @param $tmpProfileId
     * @return mixed
     */
    public function getMainFromTmpProfile($tmpProfileId){
        $linkProfileModel = $this->profileLinkModelFactory->create()->load($tmpProfileId, 'linked_profile_id');
        if ($linkProfileModel->getId()) {
            return $linkProfileModel->getData('profile_id');
        }
        return $tmpProfileId;
    }

    /**
     * Get is_spot for product cart session
     *
     * @param $productCart
     * @return array
     */
    public function getListProductIsSpotProductCart($productCart)
    {
        $spotIds = [];
        $noSpot  = 0;
        foreach ($productCart as $productItem) {
            if ($productItem->getData('is_spot')==1) {
                $spotIds [] = $productItem->getData('product_id');
            } else {
                $noSpot++;
            }
        }

        //check main product on cart
        if ($noSpot>0) {
            $spotIds = [];
        }

        return $spotIds;
    }

    /**
     * Check delete product
     *
     * @param $productCart
     * @param $productId
     * @return bool
     */
    public function checkDeleteProductSpot($productCart)
    {
        if (is_array($productCart) && count($productCart)>0) {
            $spotIds = $this->getListProductIsSpotProductCart($productCart);
            if (count($spotIds)>0) {
                return true;
            }
        }
        return false;
    }


    /**
     * Reset session profile after effected to DB
     *
     * @param $profileId
     */
    public function resetProfileSession($profileId){
        if ($sessionWrapper = $this->sessionManager->getProfileData()) {
            if (isset($sessionWrapper[$profileId])) {
                unset($sessionWrapper[$profileId]);
                $this->sessionManager->setProfileData($sessionWrapper);
            }
        }
    }

    /**
     * Check profile have product invalid leadtime on both warehouse
     *
     * @param $profileId
     * @param bool $getSku
     * @return array|bool
     */
    public function checkLeadTimeIsActiveForProfile($profileId, $getSku = false){
        $productCartModel = $this->profileRepository->getListProductCart($profileId);
        $shippingAddress  = null;
        $deliveryType = null;
        $prefecture = [];
        $productSkus = [];
        $result = false;

        $productIds = array_map(function ($profileItem) {
            return $profileItem->getProductId();
        }, $productCartModel->getItems());

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection = $this->collectionProduct->create();
        $productCollection->addAttributeToSelect('delivery_type')
            ->addIdFilter($productIds);
        $productCollectionItems = $productCollection->getItems();

        foreach ($productCartModel->getItems() as $product) {
            $shippingAddressId = $product->getShippingAddressId();
            $shippingAddressModel = $this->customerAddress->create()->load($shippingAddressId);
            if ($shippingAddressModel->getId()) {
                $regionId = $shippingAddressModel->getRegionId();
                $prefecture = $this->getPrefectureCodeOfRegion([$regionId]);
            }
            if (array_key_exists($product->getProductId(), $productCollectionItems)) {
                $deliveryType  = $productCollectionItems[$product->getProductId()]->getData('delivery_type');

                if ($getSku) {
                    $productSkus[] = $productCollectionItems[$product->getProductId()]->getSku();
                }
            }

        }
        if ($deliveryType && sizeof($prefecture) >0) {
            $leadTimeCollection = $this->leadTimeFactory->create()->getCollection()
                                    ->addFieldToFilter('delivery_type_code', $deliveryType)
                                    ->addFieldToFilter('pref_id', $prefecture)
                                    ->addFieldToFilter('is_active', 1)
                                    ->getSize();
            if ($leadTimeCollection == 0) {
                $result =  false;
            } else {
                $result = true;
            }
        } else {
            $result = true;
        }

        if (!$getSku) {
            return $result;
        } else {
            return ['result'=>$result,'sku'=>$productSkus];
        }
    }

    public function changePaymentAndDeliveryDateForProfile(\Riki\Subscription\Model\Profile\Profile $profile, array $dataChange) {
        if (isset($dataChange['payment_method'])) {
            $nextDeliveryDate = $dataChange['delivery_date_new'];
            $nextDeliveryDateDefault = $dayOfWeek = $nthWeekdayOfMonth = null;
            if ($profile->getData('frequency_unit')=='month' && $nextDeliveryDate !=null) {
                $dayDeliveryDate = (int)date('d', strtotime($nextDeliveryDate));
                if ($dayDeliveryDate>28) {
                    $nextDeliveryDateDefault = trim($nextDeliveryDate);
                    $nextDeliveryDate = $this->_deliveryDateGenerateHelper->getLastDateOfMonth($nextDeliveryDate, $nextDeliveryDateDefault);
                }
            }

            // NED-638: Calculation of the next delivery date
            // If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
            // AND interval_unit="month"
            // AND not Stock Point
            if ($this->isDayOfWeekAndUnitMonthAndNotStockPoint($profile)) {
                if ($nextDeliveryDate == $profile->getData('next_delivery_date')
                    && $profile->getData('day_of_week') != null
                    && $profile->getData('nth_weekday_of_month') != null
                ) {
                    $dayOfWeek = $profile->getData('day_of_week');
                    $nthWeekdayOfMonth = $profile->getData('nth_weekday_of_month');
                } else {
                    $dayOfWeek = date('l', strtotime($nextDeliveryDate));
                    $nthWeekdayOfMonth = $this->_deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                        $nextDeliveryDate
                    );
                }
            }

            $newNextOrderDate = $this->calculatorNextOrderDateFromProfile($nextDeliveryDate, $profile->getProfileId());

            /*Update profile*/
            $profile->setPaymentMethod($dataChange['payment_method']);
            $profile->setNextDeliveryDate($nextDeliveryDate);
            $profile->setNextOrderDate($newNextOrderDate);
            $profile->setDataGenerateDeliveryDate($nextDeliveryDateDefault);

            // Update day_of_week and nth_weekday_of_month
            $profile->setData('day_of_week', $dayOfWeek);
            $profile->setData('nth_weekday_of_month', $nthWeekdayOfMonth);

            /*Update product cart*/
            /* @var $profileProductCart \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\Collection*/
            $profileProductCart = $profile->getProductCart(true);
            foreach ($profileProductCart as $productCart) {
                $productCart->setData('delivery_date', $nextDeliveryDate);
                $productCart->setData('delivery_time_slot', $dataChange['delivery_timeslot_new']);
                try {
                    $productCart->save();
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            try {
                $profile->save();
                return true;
            } catch (\Exception $exception) {
                throw $exception;
            }
        }
    }

    /**
     * Get last delivery date of last order profile
     *
     * @param $profileId
     * @param $orderTimes
     *
     * @return string|null
     */
    public function getLastOrderDeliveryDateOfProfile($profileId, $orderTimes){
        $filter1 = $this->filterBuilder->setField('subscription_profile_id')
            ->setValue($profileId)
            ->setConditionType('eq')
            ->create();
        $filter2 = $this->filterBuilder->setField('subscription_order_time')
            ->setValue($orderTimes)
            ->setConditionType('eq')
            ->create();
        $this->searchCriteriaBuilder->addFilters([$filter1]);
        $searchResult = $this->searchCriteriaBuilder->addFilters([$filter2])->create();
        $orderRepo = $this->orderRepository->getList($searchResult);
        $deliveryDate = null;
        if (sizeof($orderRepo->getItems()) > 0) {
            foreach ($orderRepo->getItems() as $order) {
                foreach ($order->getItems() as $item) {
                    if (strtotime($item->getDeliveryDate()) > strtotime($deliveryDate)) {
                        $deliveryDate = $item->getDeliveryDate();
                    }
                }
            }
        }
        return $deliveryDate;
    }

    /**
     * @param ProfileModel $profile
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cleanDataProfileStockPoint(\Riki\Subscription\Model\Profile\Profile $profile)
    {
        if ($profile) {
            /**
             * Update profile
             */
            $profile->setStockPointProfileBucketId(null)
                ->setStockPointDeliveryType(null)
                ->setStockPointDeliveryInformation(null);

            /**
             * @var $profileProductCart \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\Collection
             */
            $profileProductCart = $profile->getProductCart(true);
            foreach ($profileProductCart as $productCart) {
                $productCart->setData('stock_point_discount_rate', null);
                $productCart->save();
            }

            if ($profile->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Is day of week and unit month and not stock point
     * If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
     * AND interval_unit="month"
     * AND not Stock Point
     *
     * @param $profileModel
     * @return boolean
     */
    public function isDayOfWeekAndUnitMonthAndNotStockPoint($profileModel)
    {
        $course = $this->getCourseData($profileModel['course_id']);

        if (empty($course) || empty($course->getId())) {
            return false;
        }

        if ($course->getData('next_delivery_date_calculation_option')
            == \Riki\SubscriptionCourse\Model\Course::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK
            && $profileModel['frequency_unit'] == 'month'
            && !$profileModel['stock_point_profile_bucket_id']
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param $profileId
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getUnitQtyTypeOptions($profileId, \Magento\Catalog\Model\Product $product)
    {

        if ('bundle' == $product->getTypeId()) {
            return [];
        }

        if ($product->getCaseDisplay() == 1) {
            return ['ea' => __('EA')];
        } elseif ($product->getCaseDisplay() == 2) {
            return ['cs' => __('CS')];
        } elseif ($product->getCaseDisplay() == 3) {
            $_obj_unit_case = $this->getUnitCaseProductCartProfile($profileId, $product->getId());

            $_unit_case = 'EA';
            if (isset($_obj_unit_case[0]['unit_case'])) {
                $_unit_case = $_obj_unit_case[0]['unit_case'];
            }

            if ($_unit_case == 'EA') {
                return ['ea' => __('EA')];
            } elseif ($_unit_case == 'CS') {
                return ['cs' => __('CS')];
            } else {
                return ['ea' => __('EA'), 'cs' => __('CS')];
            }
        }

        return ['ea' => __('EA')];
    }

    /**
     * Prepare data for validate maximum qty restriction from
     *
     * @param array $productCartData
     *
     * @return mixed
     */
    public function prepareDataForValidateMaximumQty($productCartData)
    {
        $arrResult = [];

        foreach ($productCartData as $product) {
            if ($product->getData('is_spot')) {
                $productId = $product->getData('product_id');

                if (isset($arrResult[$productId])) {
                    $newQty = $arrResult[$productId]->getData('qty') + $product->getData('qty');
                    $arrResult[$productId]->setData('qty', $newQty);
                } else {
                    $objProduct = new DataObject();
                    $objProduct->setData('product_id', $productId);
                    $objProduct->setData('qty', $product->getData('qty'));
                    $objProduct->setData('unit_case', $product->getData('unit_case'));
                    $objProduct->setData('unit_qty', $product->getData('unit_qty'));

                    $arrResult[$productId] = $objProduct;
                }
            }
        }

        return $arrResult;
    }

    /**
     * Check profile has data changed
     * @param $rootProfile
     * @param $CacheProfile
     * @return bool
     */
    public function hasDataChanged($rootProfile, $CacheProfile)
    {
        $dbUpdatedDate = $rootProfile->getUpdatedDate();
        $cacheUpdatedDate = $CacheProfile->getUpdatedDate();
        return $dbUpdatedDate > $cacheUpdatedDate;
    }

}
