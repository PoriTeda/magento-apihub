<?php

namespace Riki\DeliveryType\Controller\Delivery;

use Magento\Payment\Model\MethodList;
use Magento\Quote\Model\Quote;
use Riki\Subscription\Model\Constant;
use Riki\DeliveryType\Model\Delitype as Delitype;

class ShippingAddress extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJson;
    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;
    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $modelDeliveryDate;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $_pointOfSaleFactory;
    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $_dlHelper;
    /**
     * @var \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    protected $_calDDHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_courseFactory;
    /**
     * @var \Riki\Subscription\Model\FrequencyFactory
     */
    protected $_fqFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_timezone;
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $_subscriptionPageHelper;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $preOrderHelper;

    /**
     * @var \Riki\Quote\Api\ShippingAddressManagementInterface
     */
    protected $shippingAddressManagement;

    /**
     * @var MethodList
     */
    protected $methodList;

    /**
     * ShippingAddress constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJson
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Riki\DeliveryType\Model\DeliveryDate $modelDeliveryDate
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     * @param \Riki\DeliveryType\Helper\Data $dlHelper
     * @param \Riki\Subscription\Helper\CalculateDeliveryDate $calDDHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\Subscription\Model\Frequency\FrequencyFactory $fqFactory
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Riki\Quote\Api\ShippingAddressManagementInterface $shippingAddressManagement
     * @param \Riki\Preorder\Helper\Data $preOrderHelper
     * @param MethodList $methodList
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJson,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Riki\DeliveryType\Model\DeliveryDate $modelDeliveryDate,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Riki\DeliveryType\Helper\Data $dlHelper,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calDDHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $fqFactory,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Riki\Quote\Api\ShippingAddressManagementInterface $shippingAddressManagement,
        \Riki\Preorder\Helper\Data $preOrderHelper,
        MethodList $methodList
    ) {
        parent::__construct($context);
        $this->_regionFactory = $regionFactory;
        $this->_resultJson = $resultJson;
        $this->addressRepository = $addressRepository;
        $this->modelDeliveryDate = $modelDeliveryDate;
        $this->_checkoutSession = $checkoutSession;
        $this->_pointOfSaleFactory = $pointOfSaleFactory;
        $this->_dlHelper = $dlHelper;
        $this->_calDDHelper = $calDDHelper;
        $this->_dateTime = $dateTime;
        $this->_timezone = $timezone;
        $this->_courseFactory = $courseFactory;
        $this->_fqFactory = $fqFactory;
        $this->_subscriptionPageHelper = $subscriptionPageHelper;
        $this->productRepository = $productRepository;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->preOrderHelper = $preOrderHelper;
        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->methodList = $methodList;
    }

    protected $_request;

    /**
     * Load Product
     *
     * @param $productId
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    protected function _initProduct($productId)
    {
        if ($productId) {
            $storeId = $this->storeManagerInterface->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * @return array|bool
     */
    private function _getDestination()
    {
        $this->_request = $this->getRequest();
        $params = $this->_request->getParams();

        $type = $params['customerAddressType'];
        $customerAddressId = $params['customerAddress'];
        $customerAddressPostcode = $params['customerAddressPostcode'];

        $destination = [];
        $destination['country_code'] = 'JP';
        $destination['address_id'] = $customerAddressId;

        if ($type == 'customer-address') {
            try {
                $address = $this->addressRepository->getById($customerAddressId);
            } catch (\Exception $e) {
                return false;
            }
            $destination['region_code'] = $address->getRegion()->getRegionCode();
            $destination['postcode'] = $address->getPostcode();
            $destination['region'] = $address->getRegion()->getRegion();
        } else {
            $regions = $this->_regionFactory->create()->load($customerAddressId);
            $destination['region_code'] = $regions->getCode();
            $destination['postcode'] = $customerAddressPostcode;
            $destination['region'] = $regions->getName();
        }
        return $destination;
    }

    /**
     * Check Pre-order
     *
     * @return bool
     */
    private function _isPreOrder()
    {
        $items = $this->getQuote()->getAllItems();

        /* list cart item id */
        $cartItem = [];

        /* flag to check preorder cart */
        $isPreorder = false;

        /*delivery type of preorder product*/
        $deliveryType = '';

        foreach ($items as $item) {
            array_push($cartItem, $item->getId());
            $product = $this->_initProduct($item->getProductId());
            if($this->preOrderHelper->getIsProductPreorder($product)) {
                $isPreorder = true;
                $deliveryType = $product->getDeliveryType();
            } else {
                $isPreorder = false;
            }
        }

        // return data
        $dataCalendar = [];

        if ($isPreorder) {
            $dataCalendar['pre_order'] = true;
            $dataCalendar['timeslot'] = [];
            $dataCalendar['period'] = 0;
            $dataCalendar['name'] = $deliveryType;
            $dataCalendar["cartItems"] = $cartItem;
            $dataCalendar['serverInfo']['currentDate'] = $this->_timezone->date()->format("Y-m-d");
        } else {
            $dataCalendar['pre_order'] = false;
        }

        return $dataCalendar;
    }
    
    /**
     * Get Quote
     * 
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * Get list warehouse
     * 
     * @param $listPlace
     * @return array
     */
    private function _getListWarehouse($listPlace)
    {
        $listWh = [];
        foreach ($listPlace as $posId) {
            if (!in_array($posId, $listWh)) {
                $pointOfSale = $this->_pointOfSaleFactory->create()->load($posId);
                $listWh[] = $pointOfSale->getStoreCode();
            }
        }
        return $listWh;
    }
    /**
     * Caculate calendar delivery date
     *
     * @return mixed
     */
    public function execute()
    {
        $hasError = false;
        //check quote exist and not pre-order
        $quote = $this->getQuote();
        if (!$quote->hasItems()
            || $quote->getHasError()
            || !$quote->validateMinimumAmount()
        ) {
            $hasError = true;
        }

        //get destination
        $destination = $this->_getDestination();

        if ($hasError || !$destination) {
            $json = ['error'    =>  1];
            $resultJson = $this->_resultJson->create();
            return $resultJson->setData($json);
        }

        $result = [
            'cart_item_data' => []
        ];

        $checkPreOrder = $this->_isPreOrder();
        if (isset($checkPreOrder['pre_order']) && $checkPreOrder['pre_order']) {
            $result['cart_item_data'][] = $checkPreOrder;
        } else {
            $result['cart_item_data'] = $this->generateCartItemData($quote, $destination);
        }

        $result['payment_methods'] = [];

        $paymentMethods = $this->getPaymentMethods($quote);

        foreach ($paymentMethods as $paymentMethod) {
            $result['payment_methods'][] = [
                'code' => $paymentMethod->getCode(),
                'title' => $paymentMethod->getTitle()
            ];
        }

        $resultJson = $this->_resultJson->create();
        return $resultJson->setData($result);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $destination
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateCartItemData(\Magento\Quote\Model\Quote $quote, array $destination)
    {
        /** @var  $isCheckoutSub */
        $courseId = $quote->getData(Constant::RIKI_COURSE_ID);
        $isCheckoutSub = !empty($courseId);
        $allowChooseDeliveryDate = (boolean)$quote->getAllowChooseDeliveryDate();

        $strCurrentDate = $this->_timezone->date()->format("Y-m-d");


        $listDeliveryTypeGroupByItem = $this->modelDeliveryDate->splitQuoteByDeliveryType($quote);
        $calendar = [];

        foreach ($listDeliveryTypeGroupByItem as $key => $deliveryItem)
        {
            switch($key)
            {
                case Delitype::COLD:
                    //get assignation warehouse for some item same delivery type
                    $assignationGroupByDeliveryType = $this->modelDeliveryDate->calculateWarehouseGroupByItem($destination,$quote,$deliveryItem);

                    $listType = [];
                    $listType[] = Delitype::COLD;

                    $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                    $listWh = $this->_getListWarehouse($listPlace);

                    $dataCalendar = $this->getDeliveryCalendar($listWh,$listType,$destination['region_code']);

                    $dataCalendar['timeslot'] =$this->modelDeliveryDate->getListTimeSlot();
                    $dataCalendar['name'] = Delitype::COLD;
                    $dataCalendar["cartItems"] = $deliveryItem;
                    $dataCalendar['serverInfo']['currentDate'] = $strCurrentDate;
                    $dataCalendar['allow_choose_delivery_date'] = $allowChooseDeliveryDate;
                    $dataCalendar['onlyDm'] = false;

                    // allow next delivery date or not
                    if($isCheckoutSub) {
                        $strLastRetrictDD = isset($dataCalendar['deliverydate']) ? end($dataCalendar['deliverydate']) : '';
                        $dataCheckouSub = $this->_dataCheckoutSub($courseId,$strLastRetrictDD);

                        $calendar[] = array_merge( $dataCalendar, $dataCheckouSub );
                    } else {
                        $calendar[] = $dataCalendar;
                    }

                    break;
                case Delitype::CHILLED:
                    //get assignation warehouse for some item same delivery type
                    $assignationGroupByDeliveryType = $this->modelDeliveryDate->calculateWarehouseGroupByItem($destination,$quote,$deliveryItem);

                    $listType = [];
                    $listType[] = Delitype::CHILLED;

                    $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                    $listWh = $this->_getListWarehouse($listPlace);

                    $dataCalendar = $this->getDeliveryCalendar($listWh,$listType,$destination['region_code']);
                    $dataCalendar['timeslot'] =$this->modelDeliveryDate->getListTimeSlot();
                    $dataCalendar['name'] = Delitype::CHILLED;
                    $dataCalendar["cartItems"] = $deliveryItem;
                    $dataCalendar['serverInfo']['currentDate'] = $strCurrentDate;
                    $dataCalendar['allow_choose_delivery_date'] = $allowChooseDeliveryDate;
                    $dataCalendar['onlyDm'] = false;
                    // allow next delivery date or not
                    if($isCheckoutSub) {
                        $strLastRetrictDD = isset($dataCalendar['deliverydate']) ? end($dataCalendar['deliverydate']) : '';
                        $dataCheckouSub = $this->_dataCheckoutSub($courseId,$strLastRetrictDD);

                        $calendar[] = array_merge( $dataCalendar, $dataCheckouSub );
                    } else {
                        $calendar[] = $dataCalendar;
                    }

                    break;
                case Delitype::COSMETIC;
                    //get assignation warehouse for some item same delivery type
                    $assignationGroupByDeliveryType = $this->modelDeliveryDate->calculateWarehouseGroupByItem($destination,$quote,$deliveryItem);

                    $listType = [];
                    $listType[] = Delitype::COSMETIC;

                    $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                    $listWh = $this->_getListWarehouse($listPlace);

                    $dataCalendar = $this->getDeliveryCalendar($listWh,$listType,$destination['region_code']);
                    $dataCalendar['timeslot'] =$this->modelDeliveryDate->getListTimeSlot();
                    $dataCalendar['name'] = Delitype::COSMETIC;
                    $dataCalendar["cartItems"] = $deliveryItem;
                    $dataCalendar['serverInfo']['currentDate'] = $strCurrentDate;
                    $dataCalendar['allow_choose_delivery_date'] = $allowChooseDeliveryDate;
                    $dataCalendar['onlyDm'] = false;
                    // allow next delivery date or not
                    if($isCheckoutSub) {
                        $strLastRetrictDD = isset($dataCalendar['deliverydate']) ? end($dataCalendar['deliverydate']) : '';
                        $dataCheckouSub = $this->_dataCheckoutSub($courseId,$strLastRetrictDD);

                        $calendar[] = array_merge( $dataCalendar, $dataCheckouSub );
                    } else {
                        $calendar[] = $dataCalendar;
                    }

                    break;
                default:
                    //get assignation warehouse for group item same delivery type
                    $assignationGroupByDeliveryType = $this->modelDeliveryDate->calculateWarehouseGroupByItem($destination, $quote, $deliveryItem);
                    $timeSlot = false;
                    //get list delivery type
                    if($assignationGroupByDeliveryType) {
                        $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                        $listWh = $this->_getListWarehouse($listPlace);

                        $listType = $this->modelDeliveryDate->getDeliveryTypeFromListItem($deliveryItem);

                        $dataCalendar = $this->getDeliveryCalendar($listWh,$listType,$destination['region_code']);

                        if(isset($assignationGroupByDeliveryType['items'])) {
                            $checkOnlyDm = $this->modelDeliveryDate->checkOnlyDirectMailCheckout($listType);
                            if (!$checkOnlyDm) {
                                $timeSlot = $this->modelDeliveryDate->getListTimeSlot();
                                $dataCalendar['onlyDm'] = false;
                            } else {
                                $dataCalendar['onlyDm'] = true;
                            }
                        }

                        $dataCalendar['name'] = $key;
                    } else {
                        $dataCalendar = [];
                    }

                    $dataCalendar['timeslot'] = $timeSlot;
                    $dataCalendar["cartItems"] = $deliveryItem;
                    $dataCalendar['serverInfo']['currentDate'] = $strCurrentDate;
                    $dataCalendar['allow_choose_delivery_date'] = $allowChooseDeliveryDate;

                    // allow next delivery date or not
                    if($isCheckoutSub) {
                        $strLastRetrictDD = isset($dataCalendar['deliverydate']) ? end($dataCalendar['deliverydate']) : '';
                        $dataCheckouSub = $this->_dataCheckoutSub($courseId,$strLastRetrictDD);

                        $calendar[] = array_merge( $dataCalendar, $dataCheckouSub );
                    } else {
                        $calendar[] = $dataCalendar;
                    }

                    break;
            }
        }

        return $calendar;
    }

    /**
     * Data for checkout subscription
     *
     * @param $courseId
     * @param $strLastRetrictDD
     * @return array
     */
    private function _dataCheckoutSub($courseId,$strLastRetrictDD)
    {
        $objCourse = $this->_courseFactory->create()->load($courseId);
        $isAllowChangeNextDD = $objCourse->isAllowChangeNextDeliveryDate();
        if ($this->_subscriptionPageHelper->getSubscriptionType($courseId) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            $isChangeDeliveryDateOfHanpukai = $objCourse->getData('hanpukai_delivery_date_allowed');
        }

        $strCurrentDate = $this->_timezone->date()->format("Y-m-d");
        $quote = $this->getQuote();
        $dataCalendar = [];

        $dataCalendar['isSub'] = true;
        $fid = $quote->getData(Constant::RIKI_FREQUENCY_ID);
        if(empty($strLastRetrictDD)){
            /* Get current date with timezone */
            $strLastRetrictDD = $strCurrentDate;
        }

        $dataCalendar['str_choose_next_date'] = $this->_getMinNextDD($strLastRetrictDD, $fid);
        $dataCalendar['arr_frequency'] = $this->_getArrFrequency($fid);
        $dataCalendar['next_delivery_date_calculation_option'] = $objCourse->getData('next_delivery_date_calculation_option');
        $dataCalendar['is_allow_change_next_dd'] = $isAllowChangeNextDD;
        if (isset($isChangeDeliveryDateOfHanpukai)) {
            $dataCalendar['is_allow_change_hanpukai_delivery_date'] = $isChangeDeliveryDateOfHanpukai;
            if ($isChangeDeliveryDateOfHanpukai == 0) {
                $dataCalendar['hanpukai_first_delivery_date'] = $this->_dlHelper->formatDate($objCourse->getData('hanpukai_first_delivery_date'));
            } else {
                $dataCalendar['hanpukai_delivery_date_from'] = $this->_dlHelper->formatDate($objCourse->getData('hanpukai_delivery_date_from'));
                $dataCalendar['hanpukai_delivery_date_to'] = $this->_dlHelper->formatDate($objCourse->getData('hanpukai_delivery_date_to'));
            }

            // Add data hanpukai_maximum_order_times to $dataCalendar
            $dataCalendar['hanpukai_maximum_order_times'] = $objCourse->getData('hanpukai_maximum_order_times');
        } else {
            $dataCalendar['is_allow_change_hanpukai_delivery_date'] = -1;
        }

        $dataCalendar['allow_choose_delivery_date'] = (bool)$objCourse->isAllowChooseDeliveryDate();

        return $dataCalendar;
    }

    /**
     * Caculate list days will disable for calendar , group item have same delivery type
     *
     * @param $listWh
     * @param $listType
     * @param $regionCode
     * @return array
     */
    public function getDeliveryCalendar($listWh,$listType,$regionCode, $extendInfo = [])
    {
        //caculate number next date
        $leadTimeCollection = $this->modelDeliveryDate->caculateDate($listWh,$listType,$regionCode);

        $numberNextDate = 0;
        $posCode = $listWh[0];

        if($leadTimeCollection) {
            $numberNextDate = $leadTimeCollection['shipping_lead_time'];
            $posCode = $leadTimeCollection['warehouse_id'];
        }

        $finalDelivery = $this->modelDeliveryDate->caculateFinalDay($numberNextDate, $posCode, $extendInfo);

        //caculate preriod display calendar
        if($this->modelDeliveryDate->getCalendarPeriod()) {
            $period = $this->modelDeliveryDate->getCalendarPeriod() + count($finalDelivery) - 1;
        } else {
            $period = 29 + count($finalDelivery);
        }
        $calendar = ["period" => $period , "deliverydate" => $finalDelivery];

        return $calendar;
    }

    /**
     * Get Min Nex tDD
     *
     * @param $strLastRetrictDD
     * @param $frequencyId
     * @return mixed
     */
    private function _getMinNextDD($strLastRetrictDD, $frequencyId) {


        $intMinFirstDeliveryDate =  strtotime("+1 day", strtotime($strLastRetrictDD));
        $strMinFirstDeliveryDate = date(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT, $intMinFirstDeliveryDate);

        $strMinNextDD = $this->_calDDHelper->getDatePlusFrequency($strMinFirstDeliveryDate, $frequencyId);

        return $strMinNextDD;
    }

    /**
     * Get Arr Frequency
     *
     * @param $fid
     * @return array
     */
    private function _getArrFrequency($fid)
    {
        $objFrequency = $this->_fqFactory->create()->load($fid);

        if(empty($objFrequency) || empty($objFrequency->getId())) {
            return [];
        }

        $strFrequencyUnit = $objFrequency->getData("frequency_unit");
        $frequencyInterval = $objFrequency->getData("frequency_interval");

        return [$frequencyInterval, $strFrequencyUnit];
    }

    /**
     * @param Quote $quote
     * @return \Magento\Payment\Model\MethodInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getPaymentMethods(Quote $quote)
    {
        $addressData = $this->addressRepository->getById($this->_request->getParam('customerAddress'));
        $quote->getShippingAddress()->importCustomerAddressData($addressData);

        return $this->methodList->getAvailableMethods($quote);
    }
}
