<?php
/**
 * Nestle Purina Vets
 * PHP version 7
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
namespace Nestle\Purina\Model;

use Riki\Subscription\Model\Constant;
use Riki\DeliveryType\Model\Delitype as Delitype;
use Nestle\Purina\Api\Data\DeliverytimeDataInterfaceFactory;

/**
 * Class DeliveryDateManagement
 *
 * @category Nestle_Purina
 * @package  Nestle\Purina
 * @author   Nestle <support@nestle.co.jp>
 * @license  http://nestle.co.jp/policy.html GNU General Public License
 * @link     http://vets.nestle.jp
 */
class DeliveryDateManagement
  implements \Nestle\Purina\Api\DeliveryDateInterface
{
    /**
     * Region
     *
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * Result
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJson;

    /**
     * Customer address API
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * Data object
     *
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * Delivery date model
     *
     * @var DeliveryDate
     */
    protected $modelDeliveryDate;

    /**
     * Point of sales
     *
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $pointOfSaleFactory;

    /**
     * Delivery type helper
     *
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $dlHelper;

    /**
     * Calculate Delivery date
     *
     * @var \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    protected $calDDHelper;

    /**
     * Date-time
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * Subscription course
     *
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $courseFactory;

    /**
     * Subscription frequency
     *
     * @var \Riki\Subscription\Model\FrequencyFactory
     */
    protected $fqFactory;

    /**
     * Time zone
     *
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $timezone;

    /**
     * Subscription page helper
     *
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subscriptionPageHelper;

    /**
     * Product repository
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Store interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * Quote repository
     *
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * Quote Data
     *
     * @var \Magento\Quote\Model\Quote $quoteData
     */
    protected $quoteData;

    /**
     * Pre-order helper
     *
     * @var \Riki\Preorder\Helper\Data
     */
    protected $preOrderHelper;

    /**
     * Shipping address interface
     *
     * @var \Riki\Quote\Api\ShippingAddressManagementInterface
     */
    protected $shippingAddressManagement;

    /**
     * Delivery date time interface
     *
     * @var DeliverytimeDataInterfaceFactory $deliveryDateApiDataObjectFactory
     */
    protected $deliveryDateApiDataObjectFactory;

    /**
     * @var \Riki\DeliveryType\Model\Config\DeliveryDateSelection
     */
    protected $deliveryDateSelection;

    /**
     * DeliveryDateManagement constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param DeliveryDate $modelDeliveryDate
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     * @param \Riki\DeliveryType\Helper\Data $dlHelper
     * @param \Riki\Subscription\Helper\CalculateDeliveryDate $calDDHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\Subscription\Model\Frequency\FrequencyFactory $fqFactory
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Riki\Quote\Api\ShippingAddressManagementInterface $shippingAddressManagement
     * @param \Riki\Preorder\Helper\Data $preOrderHelper
     * @param DeliverytimeDataInterfaceFactory $deliveryDateApiDataObjectFactory
     * @param \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelection
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Nestle\Purina\Model\DeliveryDate $modelDeliveryDate,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Riki\DeliveryType\Helper\Data $dlHelper,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calDDHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $fqFactory,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Riki\Quote\Api\ShippingAddressManagementInterface $shippingAddressManagement,
        \Riki\Preorder\Helper\Data $preOrderHelper,
        DeliverytimeDataInterfaceFactory $deliveryDateApiDataObjectFactory,
        \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelection
    ) {
        $this->regionFactory = $regionFactory;
        $this->addressRepository = $addressRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->modelDeliveryDate = $modelDeliveryDate;
        $this->pointOfSaleFactory = $pointOfSaleFactory;
        $this->quoteRepository = $quoteRepository;
        $this->dlHelper = $dlHelper;
        $this->calDDHelper = $calDDHelper;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->courseFactory = $courseFactory;
        $this->fqFactory = $fqFactory;
        $this->subscriptionPageHelper = $subscriptionPageHelper;
        $this->productRepository = $productRepository;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->preOrderHelper = $preOrderHelper;
        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->deliveryDateApiDataObjectFactory = $deliveryDateApiDataObjectFactory;
        $this->deliveryDateSelection = $deliveryDateSelection;
    }

    /**
     * Calculate delivery date
     *
     * @param int $cartId    cart_id
     * @param int $addressId address_id
     *
     * @return array|mixed|\Nestle\Purina\Api\Data\DeliverytimeDataInterface|
     * \Nestle\Purina\Api\Data\DeliverytimeDataInterface[]
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function calculateDeliveryDate($cartId, $addressId)
    {
        $quote =  $this->_initQuote($cartId);
        if (!$quote->hasItems()
            || $quote->getHasError()
            || !$quote->validateMinimumAmount()
        ) {
            return ['error' =>  1];
        }
        //get destination
        $destination = $this->_getDestination($addressId);

        $checkPreOrder = $this->_isPreOrder();
        if (isset($checkPreOrder['pre_order']) && $checkPreOrder['pre_order']) {
            return $this->_processResponse($checkPreOrder);
        }
        return $this->generateCartItemData($quote, $destination);
    }

    /**
     * Process response
     *
     * @param mixed $apiData api data
     *
     * @return \Nestle\Purina\Api\Data\DeliverytimeDataInterface
     */
    private function _processResponse($apiData)
    {
        $apiDataResponse = $this->deliveryDateApiDataObjectFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $apiDataResponse,
            $apiData,
            \Nestle\Purina\Api\Data\DeliverytimeDataInterface::class
        );
        return $apiDataResponse;
    }

    /**
     * Calculate list days will disable for calendar,
     * group item have same delivery type
     *
     * @param mixed $listWh     list warehouse
     * @param mixed $listType   list type
     * @param mixed $regionCode Region
     * @param array $extendInfo extra
     *
     * @return array
     */
    public function getDeliveryCalendar(
        $listWh, $listType, $regionCode, $extendInfo = []
    ) {
        $leadTimeCollection = $this->modelDeliveryDate
            ->caculateDate($listWh, $listType, $regionCode);
        $numberNextDate = 0;
        $posCode = $listWh[0];
        if ($leadTimeCollection) {
            $numberNextDate = $leadTimeCollection['shipping_lead_time'];
            $posCode = $leadTimeCollection['warehouse_id'];
        }
        if ($this->deliveryDateSelection->getDisableChangeDeliveryDateConfig()) {
            $finalDelivery = [];
        } else {
            $finalDelivery = $this->modelDeliveryDate
                ->caculateFinalDay($numberNextDate, $posCode, $extendInfo);
        }

        if ($this->modelDeliveryDate->getCalendarPeriod()) {
            $period = $this->modelDeliveryDate->getCalendarPeriod()
                + count($finalDelivery) - 1;
        } else {
            $period = 29 + count($finalDelivery);
        }
        $calendar = ['period' => $period , 'deliverydate' => $finalDelivery];
        return $calendar;
    }

    /**
     * Data for checkout subscription
     *
     * @param mixed $courseId         course id
     * @param mixed $strLastRetrictDD last id
     *
     * @return array
     */
    private function _dataCheckoutSub($courseId,$strLastRetrictDD, $quote = null)
    {
        $objCourse = $this->courseFactory->create()->load($courseId);
        $isAllowChangeNextDD = $objCourse->isAllowChangeNextDeliveryDate();
        if ($this->subscriptionPageHelper->getSubscriptionType($courseId) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI
        ) {
            $isChangeDeliveryDateOfHanpukai
                = $objCourse->getData('hanpukai_delivery_date_allowed');
        }
        $strCurrentDate = $this->timezone->date()->format("Y-m-d");
        if (!$quote) {
            $quote = $this->quoteData;
        }
        $dataCalendar = [];

        $dataCalendar['isSub'] = true;
        $fid = $quote->getData(Constant::RIKI_FREQUENCY_ID);
        if (empty($strLastRetrictDD)) {
            /* Get current date with timezone */
            $strLastRetrictDD = $strCurrentDate;
        }
        $dataCalendar['str_choose_next_date']
            = $this->_getMinNextDD($strLastRetrictDD, $fid);
        $dataCalendar['arr_frequency'] = $this->_getArrFrequency($fid);
        $dataCalendar['is_allow_change_next_dd'] = $isAllowChangeNextDD;
        if (isset($isChangeDeliveryDateOfHanpukai)) {
            $dataCalendar['is_allow_change_hanpukai_delivery_date']
                = $isChangeDeliveryDateOfHanpukai;
            if ($isChangeDeliveryDateOfHanpukai == 0) {
                $dataCalendar['hanpukai_first_delivery_date']
                    = $this->dlHelper->formatDate(
                        $objCourse->getData('hanpukai_first_delivery_date')
                    );
            } else {
                $dataCalendar['hanpukai_delivery_date_from']
                    = $this->dlHelper->formatDate(
                        $objCourse->getData('hanpukai_delivery_date_from')
                    );
                $dataCalendar['hanpukai_delivery_date_to']
                    = $this->dlHelper->formatDate(
                        $objCourse->getData('hanpukai_delivery_date_to')
                    );
            }
        } else {
            $dataCalendar['is_allow_change_hanpukai_delivery_date'] = -1;
        }
        return $dataCalendar;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getListTimeSlot()
    {
        if ($this->deliveryDateSelection->getDisableChangeDeliveryDateConfig()) {
            return [];
        }
        return $this->modelDeliveryDate->getListTimeSlot();
    }

    /**
     * Generate cart item data
     *
     * @param \Magento\Quote\Model\Quote $quote       quote
     * @param array                      $destination destination
     *
     * @return array
     */
    public function generateCartItemData(
        \Magento\Quote\Model\Quote $quote,
        array $destination = []
    ) {
        $courseId = $quote->getData(Constant::RIKI_COURSE_ID);
        $isCheckoutSub = !empty($courseId);
        $strCurrentDate = $this->timezone->date()->format("Y-m-d");
        $listDeliveryTypeGroupByItem = $this->modelDeliveryDate
            ->splitQuoteByDeliveryType($quote);
        $calendar = [];
        foreach ($listDeliveryTypeGroupByItem as $key => $deliveryItem) {
            switch($key) {
            case Delitype::COLD:
                //get assignation warehouse for some item same delivery type
                $assignationGroupByDeliveryType
                    = $this->modelDeliveryDate->calculateWarehouseGroupByItem(
                        $destination, $quote, $deliveryItem
                    );
                $listType = [];
                $listType[] = Delitype::COLD;

                $listPlace
                    = explode(",", $assignationGroupByDeliveryType['place_ids']);
                $listWh = $this->_getListWarehouse($listPlace);

                $dataCalendar = $this->getDeliveryCalendar(
                    $listWh, $listType, $destination['region_code']
                );
                $dataCalendar['timeslot']
                    = $this->getListTimeSlot();
                $dataCalendar['name'] = Delitype::COLD;
                $dataCalendar["cartItems"] = $deliveryItem;
                $dataCalendar['serverInfo']['currentDate'] = $strCurrentDate;
                // allow next delivery date or not
                if ($isCheckoutSub) {
                    $strLastRetrictDD = isset($dataCalendar['deliverydate'])
                        ? end($dataCalendar['deliverydate']) : '';
                    $dataCheckouSub = $this
                        ->_dataCheckoutSub($courseId, $strLastRetrictDD, $quote);
                    $calendar[] = $this->_processResponse(
                        array_merge($dataCalendar, $dataCheckouSub)
                    );
                } else {
                    $calendar[] = $this->_processResponse($dataCalendar);
                }
                break;
            case Delitype::CHILLED:
                //get assignation warehouse for some item same delivery type
                $assignationGroupByDeliveryType
                    = $this->modelDeliveryDate->calculateWarehouseGroupByItem(
                        $destination, $quote, $deliveryItem
                    );
                $listType = [];
                $listType[] = Delitype::CHILLED;
                $listPlace = explode(
                    ",", $assignationGroupByDeliveryType['place_ids']
                );
                $listWh = $this->_getListWarehouse($listPlace);
                $dataCalendar = $this->getDeliveryCalendar(
                    $listWh, $listType, $destination['region_code']
                );
                $dataCalendar['timeslot'] = $this->getListTimeSlot();
                $dataCalendar['name'] = Delitype::CHILLED;
                $dataCalendar["cartItems"] = $deliveryItem;
                $dataCalendar['serverInfo']['currentDate'] = $strCurrentDate;
                // allow next delivery date or not
                if ($isCheckoutSub) {
                    $strLastRetrictDD = isset($dataCalendar['deliverydate'])
                        ? end($dataCalendar['deliverydate']) : '';
                    $dataCheckouSub = $this
                        ->_dataCheckoutSub($courseId, $strLastRetrictDD, $quote);
                    $calendar[] = $this->_processResponse(
                        array_merge($dataCalendar, $dataCheckouSub)
                    );
                } else {
                    $calendar[] = $dataCalendar;
                }
                break;
            case Delitype::COSMETIC;
                //get assignation warehouse for some item same delivery type
                $assignationGroupByDeliveryType
                    = $this->modelDeliveryDate->calculateWarehouseGroupByItem(
                        $destination, $quote, $deliveryItem
                    );
                $listType = [];
                $listType[] = Delitype::COSMETIC;
                $listPlace
                    = explode(",", $assignationGroupByDeliveryType['place_ids']);
                $listWh = $this->_getListWarehouse($listPlace);
                $dataCalendar = $this->getDeliveryCalendar(
                    $listWh, $listType, $destination['region_code']
                );
                $dataCalendar['timeslot'] = $this->getListTimeSlot();
                $dataCalendar['name'] = Delitype::COSMETIC;
                $dataCalendar["cartItems"] = $deliveryItem;
                $dataCalendar['serverInfo']['currentDate'] = $strCurrentDate;
                // allow next delivery date or not
                if ($isCheckoutSub) {
                    $strLastRetrictDD = isset($dataCalendar['deliverydate'])
                        ? end($dataCalendar['deliverydate']) : '';
                    $dataCheckouSub = $this
                        ->_dataCheckoutSub($courseId, $strLastRetrictDD, $quote);
                    $calendar[] = $this->_processResponse(
                        array_merge($dataCalendar, $dataCheckouSub)
                    );
                } else {
                    $calendar[] = $this->_processResponse($dataCalendar);
                }
                break;
            default:
                //get assignation warehouse for group item same delivery type
                $assignationGroupByDeliveryType
                    = $this->modelDeliveryDate->calculateWarehouseGroupByItem(
                        $destination, $quote, $deliveryItem
                    );
                $timeSlot = [];
                //get list delivery type
                if ($assignationGroupByDeliveryType) {
                    $listPlace = explode(
                        ",", $assignationGroupByDeliveryType['place_ids']
                    );
                    $listWh = $this->_getListWarehouse($listPlace);
                    $listType = $this->modelDeliveryDate
                        ->getDeliveryTypeFromListItem($deliveryItem);
                    $dataCalendar = $this->getDeliveryCalendar(
                        $listWh, $listType, $destination['region_code']
                    );
                    if (isset($assignationGroupByDeliveryType['items'])) {
                        $checkOnlyDm = $this->modelDeliveryDate
                            ->checkOnlyDirectMailCheckout($listType);
                        if (!$checkOnlyDm) {
                            $timeSlot = $this->getListTimeSlot();
                        }
                    }
                    $dataCalendar['name'] = $key;
                } else {
                    $dataCalendar = [];
                }
                $dataCalendar['timeslot'] = $timeSlot;
                $dataCalendar["cartItems"] = $deliveryItem;
                $dataCalendar['serverInfo']['currentDate'] = $strCurrentDate;
                // allow next delivery date or not
                if ($isCheckoutSub) {
                    $strLastRetrictDD = isset($dataCalendar['deliverydate']) ?
                        end($dataCalendar['deliverydate']) : '';
                    $dataCheckouSub = $this->_dataCheckoutSub(
                        $courseId, $strLastRetrictDD, $quote
                    );
                    $calendar[] = $this->_processResponse(
                        array_merge($dataCalendar, $dataCheckouSub)
                    );
                } else {
                    $calendar[] = $this->_processResponse($dataCalendar);
                }
                break;
            }
        }
        return $calendar;
    }

    /**
     * Get list warehouse
     *
     * @param mixed $listPlace list place
     *
     * @return array
     */
    private function _getListWarehouse($listPlace)
    {
        $listWh = [];
        foreach ($listPlace as $posId) {
            if (!in_array($posId, $listWh)) {
                try {
                    $pointOfSale = $this->pointOfSaleFactory->create()->load($posId);
                }
                catch (
                    \Magento\Framework\Exception\NoSuchEntityException $exception
                ) {
                    // In case of not found
                    continue 1;
                }
                $listWh[] = $pointOfSale->getStoreCode();
            }
        }
        return $listWh;
    }

    /**
     * Initialize quote
     *
     * @param int $cartId cart_id
     *
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function _initQuote($cartId)
    {
        if (!$this->quoteData instanceof \Magento\Quote\Model\Quote) {
            $this->quoteData = $this->quoteRepository->get($cartId);
        }
        return $this->quoteData;
    }

    /**
     * Check Pre-order
     *
     * @return bool
     */
    private function _isPreOrder()
    {
        $items = $this->quoteData->getAllItems();

        /* list cart item id */
        $cartItem = [];

        /* flag to check preorder cart */
        $isPreorder = false;

        /*delivery type of preorder product*/
        $deliveryType = '';

        foreach ($items as $item) {
            array_push($cartItem, $item->getId());
            $product = $this->_initProduct($item->getProductId());
            if ($this->preOrderHelper->getIsProductPreorder($product)) {
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
            $dataCalendar['serverInfo']['currentDate']
                = $this->timezone->date()->format("Y-m-d");
        } else {
            $dataCalendar['pre_order'] = false;
        }

        return $dataCalendar;
    }

    /**
     * Get Destination
     *
     * @param mixed $selectedAddressId address_id
     *
     * @return array
     */
    private function _getDestination($selectedAddressId)
    {
        $customerAddressId = $selectedAddressId;
        $destination = [];
        $destination['country_code'] = 'JP';
        $destination['address_id'] = $customerAddressId;
        try {
            $address = $this->addressRepository->getById($customerAddressId);
            $destination['region_code'] = $address->getRegion()->getRegionCode();
            $destination['postcode'] = $address->getPostcode();
            $destination['region'] = $address->getRegion()->getRegion();
        }
        catch (\Magento\Framework\Exception\LocalizedException $e) {
            $destination['region_code'] = '';
            $destination['postcode'] = '';
            $destination['region'] = '';
        }
        return $destination;
    }

    /**
     * Load Product
     *
     * @param int $productId product_id
     *
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    private function _initProduct($productId)
    {
        if ($productId) {
            $storeId = $this->storeManagerInterface->getStore()->getId();
            try {
                return $this->productRepository->getById(
                    $productId, false, $storeId
                );
            }
            catch (\Magento\Framework\Exception\NoSuchEntityException $exception){
                return false;
            }
        }
        return false;
    }

    /**
     * Get Min Nex tDD
     *
     * @param mixed $strLastRetrictDD last_id
     * @param mixed $frequencyId      frequency_id
     *
     * @return mixed
     */
    private function _getMinNextDD($strLastRetrictDD, $frequencyId)
    {
        $intMinFirstDeliveryDate = strtotime("+1 day", strtotime($strLastRetrictDD));
        $strMinFirstDeliveryDate = date(
            \Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT,
            $intMinFirstDeliveryDate
        );
        $strMinNextDD = $this->calDDHelper
            ->getDatePlusFrequency($strMinFirstDeliveryDate, $frequencyId);

        return $strMinNextDD;
    }

    /**
     * Get Arr Frequency
     *
     * @param int $fid frequency_id
     *
     * @return array
     */
    private function _getArrFrequency($fid)
    {
        $objFrequency = $this->fqFactory->create()->load($fid);
        if (empty($objFrequency) || empty($objFrequency->getId())) {
            return [];
        }
        $strFrequencyUnit = $objFrequency->getData("frequency_unit");
        $frequencyInterval = $objFrequency->getData("frequency_interval");
        return [$frequencyInterval, $strFrequencyUnit];
    }
}

