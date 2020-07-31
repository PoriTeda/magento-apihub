<?php
/**
 * Riki Sales calculate cut off date for Shipment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Sales\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Sales\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Riki\ShipmentExporter\Helper\Data as ShipmentHelper;
use Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection as PointOfSaleCollection;
use Riki\ShipLeadTime\Model\ResourceModel\Leadtime\CollectionFactory as LeadtimeCollection;
use Riki\Checkout\Model\ResourceModel\Order\Address\Item\Collection as AddressItemCollection;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Sales\Model\Order\AddressRepository as AddressRepository;
use Magento\Sales\Model\OrderRepository;
use Riki\Subscription\Model\Profile\ProfileRepository;
use Riki\SubscriptionCourse\Model\CourseFactory;

/**
 * Class      OrderCutoffDate
 *
 * @category  RIKI
 * @package   Riki\Sales\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class OrderCutoffDate
{
    /**
     * suspicious value
     */
    const SUSPICIOUS_VALUE = 3000000;
    /**
     * subscription
     */
    const ORDER_SUBSCRIPTION = 'SUBSCRIPTION';
    /**
     * hanpukai
     */
    const ORDER_HANPUKAI = 'HANPUKAI';
    /**
     * delay payment
     */
    const ORDER_DELAY_PAYMENT = 'DELAY PAYMENT';
    /**
     * Parameter definition
     *
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $posFactory;
    /**
     * @var
     */
    protected $timeZone;
    /**
     * @var
     */
    protected $dateZone;
    /**
     *
     */
    protected $exportHelper;
    /**
     * @var
     */
    protected $logger;
    /**
     * @var CollectionFactory
     */
    protected $orderStatusHistoryFactory;
    /**
     * @var AppState
     */
    protected $appState;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $adminQuoteSession;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriticalBuilder;
    /**
     * Application Event Dispatcher
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    /**
     * @var ShipmentHelper
     */
    protected $shipmentHelper;
    /**
     * @var PointOfSaleCollection
     */
    protected $pointOfSaleCollection;
    /**
     * @var LeadtimeCollection
     */
    protected $leadTimeCollection;
    /**
     * @var AddressItemCollection
     */
    protected $addressItemCollection;
    /**
     * @var AddressFactory
     */
    protected $addressOrderRepository;
    /**
     * @var OrderRepository
     */
    protected $orderRepository;
    /**
     * @var ProfileRepository
     */
    protected $profileRepository;
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;
    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\AdvancedInventory\Helper\Logger
     */
    protected $loggerHelper;

    /**
     * @var string
     */
    protected $contentLog;

    /* @var \Riki\SubscriptionCourse\Model\CourseFactory */
    protected $courseFactory;

    /**
     * OrderCutoffDate constructor.
     *
     * @param \Riki\AdvancedInventory\Helper\Logger $loggerHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateZone
     * @param ShipmentHelper $exportHelper
     * @param \Riki\Sales\Logger\LoggerSales $logger
     * @param AppState $appState
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param OrderStatusHistoryRepositoryInterface $historyFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param ShipmentHelper $shipmentHelper
     * @param PointOfSaleCollection $pointofsaleCollection
     * @param LeadtimeCollection $leadtimeCollection
     * @param AddressItemCollection $addressItemCollection
     * @param AddressRepository $addressRepository
     * @param OrderRepository $orderRepository
     * @param ProfileRepository $profileRepository
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Riki\Sales\Helper\Order $orderHelper
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\Logger $loggerHelper,
        \Magento\Framework\Registry $registry,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateZone,
        \Riki\ShipmentExporter\Helper\Data $exportHelper,
        \Riki\Sales\Logger\LoggerSales $logger,
        AppState $appState,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        OrderStatusHistoryRepositoryInterface $historyFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        ShipmentHelper $shipmentHelper,
        PointOfSaleCollection $pointofsaleCollection,
        LeadtimeCollection $leadtimeCollection,
        AddressItemCollection $addressItemCollection,
        AddressRepository $addressRepository,
        OrderRepository $orderRepository,
        ProfileRepository $profileRepository,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Riki\Sales\Helper\Order $orderHelper,
        CourseFactory $courseFactory
    ) {
    
        $this->loggerHelper = $loggerHelper;
        $this->registry = $registry;
        $this->posFactory = $posFactory;
        $this->timezone = $timeZone;
        $this->dateZone = $dateZone;
        $this->exportHelper = $exportHelper;
        $this->logger = $logger;
        $this->orderStatusHistoryFactory = $historyFactory;
        $this->appState = $appState;
        $this->adminQuoteSession = $quoteSession;
        $this->searchCriticalBuilder = $searchCriteriaBuilder;
        $this->eventManager = $eventManager;
        $this->shipmentHelper = $shipmentHelper;
        $this->pointOfSaleCollection = $pointofsaleCollection;
        $this->leadTimeCollection = $leadtimeCollection;
        $this->addressItemCollection = $addressItemCollection;
        $this->addressOrderRepository = $addressRepository;
        $this->orderRepository = $orderRepository;
        $this->profileRepository = $profileRepository;
        $this->regionFactory = $regionFactory;
        $this->orderHelper = $orderHelper;
        $this->courseFactory = $courseFactory;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param bool $saveLog
     * @return $this
     */
    public function calculateCutoffDate(\Magento\Sales\Model\Order $order, $saveLog = true)
    {
        // Trace log NED-708
        $this->contentLog = "";

        $now = $this->timezone->formatDateTime($this->dateZone->gmtDate(), 2);
        $today = $this->dateZone->gmtDate('Y-m-d', $now);
        $paymentMethod = $order->getPayment()->getMethod();
        if ($order->getShippingAddress()
            || $paymentMethod != \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE
        ) {
            $orderItems = $order->getItems();
            $orderSpot = $order->getRikiType(); // Spot or not
            $bufferDays = $this->getBufferDays();
            $warehouses = $this->getWarehouses();
            $regionIdOrigin = $order->getShippingAddress()->getRegionId();
            if ($order->getAssignation()) {
                $assignation = \Zend_Json_Decoder::decode(
                    $order->getAssignation(),
                    \Zend_Json::TYPE_OBJECT
                );
                $minDeliveryDate = [];
                foreach ($orderItems as $orderItem) {
                    $deliveryDate = $orderItem->getDeliveryDate();
                    $regionId = $this->checkMultiAddressItem($orderItem->getId(), $regionIdOrigin);
                    $orderType = strtoupper($orderSpot);
                    if ($orderType == "SPOT") {
                        $orderTimes = 0;
                        $profileId = 0;
                    } else { //SUBSCRIPTION, HANPUKAI
                        $profileId = $order->getData('subscription_profile_id');
                        try {
                            $profileModel = $this->profileRepository->get($profileId);
                            $orderTimes = $profileModel->getOrderTimes();
                            $subscriptionCourse = $this->courseFactory->create()->load($profileModel->getCourseId());
                            $excludeBufferDays = $subscriptionCourse->getData('exclude_buffer_days');
                            if ($excludeBufferDays) {
                                $bufferDays = 0;
                            }
                        } catch (NoSuchEntityException $e) {
                            $orderTimes = 0;
                        }
                    }
                    $params = [
                        'deliveryDate' => $deliveryDate,
                        'assignation' => $assignation,
                        'warehouses' => $warehouses,
                        'regionId' => $regionId,
                        'orderType' => $orderType,
                        'bufferDays' => $bufferDays,
                        'orderTimes' => $orderTimes,
                        'orderSpot' => $orderSpot,
                        'profileId' => $profileId,
                        'today' => $today,
                        'deliveryType' => $orderItem->getDeliveryType(),
                        'productType' => $orderItem->getProductType(),
                        'orderItemId' => $orderItem->getId()
                    ];
                    $exportDate = $this->updateCutoffDateOrderItem($orderItem, $params);
                    $minDeliveryDate[] = $exportDate;

                    // Remove $params['assignation'] to reduce the log size
                    unset($params['assignation']);
                    $this->contentLog .= "Order Item ID #" . $orderItem->getId() . " with params " . json_encode($params) . "\r\n";
                    $this->contentLog .= "Order Item ID #" . $orderItem->getId() . " with export_date " . $exportDate . "\r\n";
                }
                if ($minDeliveryDate) {
                    $mindate = min($minDeliveryDate);
                    $minExportDate = date('Y-m-d', $mindate);
                    if (strtotime($minExportDate) < strtotime($today)) {
                        $minExportDate = $today;
                    }
                    $order->setMinExportDate($minExportDate);

                    $this->contentLog .= "Order with min_export_date " . $minExportDate . "\r\n";
                }
            }
        }
        //check and set order status
        $this->changeOrderStatus($order, $saveLog);
        $order->save();
        return $this;
    }

    /**
     * @param $orderItem
     * @param $params
     * @return false|int
     */
    public function updateCutoffDateOrderItem($orderItem, $params)
    {
        $deliveryDate= $params['deliveryDate'];
        $assignation = $params['assignation'];
        $warehouses = $params['warehouses'];
        $regionId = $params['regionId'];
        $orderType = $params['orderType'];
        $bufferDays = $params['bufferDays'];
        $orderTimes = $params['orderTimes'];
        $orderSpot = $params['orderSpot'];
        $profileId = $params['profileId'];
        $today = $params['today'];
        if ($deliveryDate && $assignation) {
            //get delivery type
            $deliveryType = $orderItem->getDeliveryType();
            //get warehouse_id
            if ($assignation->place_ids) {
                if ($orderItem->getProductType()== ProductType::TYPE_BUNDLE) {
                    $itemId = $this->getFirstChildenItem($orderItem);
                } else {
                    $itemId = $orderItem->getId();
                }
                $warehouseId = $this->getWarehouseAssignation($assignation, $itemId);
                if ($warehouseId) {
                    $warehouseCode = $warehouses[$warehouseId]->getStoreCode();
                    //get prefecture id
                    $leadTime = $this->getShippingLeadTime(
                        $regionId,
                        $warehouseCode,
                        $deliveryType,
                        $orderType,
                        $orderTimes
                    );
                    $exportDate = $this->getExportDate(
                        $deliveryDate,
                        $leadTime,
                        $bufferDays,
                        $orderSpot,
                        $warehouses[$warehouseId],
                        $profileId
                    );
                } else {
                    // No delivery type
                    $exportDate = $today; //today
                }
            } else {
                // No assignation
                $orderBufferNeeded = [
                    self::ORDER_HANPUKAI,
                    self::ORDER_SUBSCRIPTION
                ];
                $calculateDays = 0;

                if (in_array($orderType, $orderBufferNeeded)) {
                    $orderTimes = 0;
                    if ($orderTimes > 1) {
                        $calculateDays += $bufferDays;
                    }
                }
                $exportDate = $compareDate = $this->dateZone->gmtDate('Y-m-d', strtotime($today . '+' . $calculateDays . ' day')); //today
            }
        } else {
            //no delivery date and warehouse
            if ($orderType == "SPOT" || !$orderTimes) {
                $exportDate = $today; //today
            } else {
                $exportDate = $this->dateZone->gmtDate('Y-m-d', strtotime($today . '-' . $bufferDays . ' day')); //today
            }
        }
        if ($exportDate) {
            if (strtotime($exportDate) < strtotime($today)) {
                $exportDate = $today;
            }
            $orderItem->setExportDate($exportDate)->save();
        }
        return strtotime($exportDate);
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param bool|true $saveLog
     * @return $this
     * @throws \Exception
     */
    public function changeOrderStatus(\Magento\Sales\Model\Order $order, $saveLog = true)
    {
        $keepStatus = [OrderStatus::STATUS_ORDER_SUSPICIOUS, OrderStatus::STATUS_ORDER_PENDING_CRD_REVIEW];

        if (!in_array($order->getStatus(), $keepStatus)) {
            $this->updateOrderStatus($order, $saveLog);
        }
        // after cut off date, dispatch event to check fraud order
        // change status to suspicious, send notification email, add suspicious order to tracking table
        $this->eventManager->dispatch('order_cut_off_date_save_before', ['order' => $order]);
        return $this;
    }

    /**
     * Get buffer Days
     *
     * @return mixed
     */
    public function getBufferDays()
    {
        return $this->shipmentHelper->getBufferDay();
    }

    /**
     * Get Warehouse
     *
     * @return array
     */
    public function getWarehouses()
    {
        $whCollection = $this->pointOfSaleCollection;
        $warehouses = [];
        if ($whCollection->getSize()) {
            foreach ($whCollection as $wh) {
                $warehouses[$wh->getPlaceId()] = $wh;
            }
        }
        return $warehouses;
    }

    /**
     * @param $regionId
     * @param $warehouseCode
     * @param $deliveryType
     * @param $orderType
     * @param $orderTimes
     * @return int
     */
    public function getShippingLeadTime($regionId, $warehouseCode, $deliveryType, $orderType, $orderTimes)
    {
        //get lead time collection
        $collection = $this->leadTimeCollection->create()->addActiveToFilter();

        if ($warehouseCode) {
            $collection->addFieldToFilter('warehouse_id', $warehouseCode);
        }
        if ($deliveryType) {
            $collection->addFieldToFilter('delivery_type_code', $deliveryType);
        }
        if ($regionId) {
            //get region code for filter
            $regions = $this->regionFactory->create();
            $regionCode = $regions->load($regionId)->getCode();
            $collection->addFieldToFilter("pref_id", $regionCode);
        }
        //order by priority of prefecture
        $collection->addOrder('priority', $collection::SORT_ORDER_DESC);
        $collection->setPageSize(1);
        //get max lead time
        if ($collection->getSize()) {
            return $collection->getFirstItem()->getShippingLeadTime();
        } else {
            // does not find a leadtime
            if ($orderType == "SPOT" || !$orderTimes) {
                return 0;
            } else {
                return $this->getTokyoLeadtime($warehouseCode, $deliveryType);
            }
        }
    }

    /**
     * @param $warehouseCode
     * @param $deliveryType
     * @return int
     */
    protected function getTokyoLeadtime($warehouseCode, $deliveryType = 0)
    {
        //get lead time collection
        $collection = $this->leadTimeCollection->create()->addActiveToFilter();

        if ($warehouseCode) {
            $collection->addFieldToFilter('warehouse_id', $warehouseCode);
        }
        if ($deliveryType) {
            $collection->addFieldToFilter('delivery_type_code', $deliveryType);
        }
        $collection->addFieldToFilter("pref_id", 'TKY');
        $collection->setOrder('shipping_lead_time', 'DESC');
        $collection->setPageSize(1);
        //get max lead time
        if ($collection->getSize()) {
            return $collection->getFirstItem()->getShippingLeadTime();
        } else {
            return 0;
        }
    }

    /**
     * Get Warehouse Assignation
     *
     * @param   $assignation
     * @param   $itemid
     * @return  int|string
     */
    public function getWarehouseAssignation($assignation, $itemid)
    {
        if ($assignation->items) {
            foreach ($assignation->items as $itemKey => $item) {
                if ($item->pos && (int)($itemKey) == (int)($itemid)) {
                    foreach ($item->pos as $key => $val) {
                        if ($key) {
                            return $key;
                        }
                    }
                }
            }
        }
    }
    /**
     * @param $deliveryDate
     * @param $leadTime
     * @param $bufferDays
     * @param $orderType
     * @param $warehouse
     * @param null $profileId
     * @return false|null|string
     */
    public function getExportDate(
        $deliveryDate,
        $leadTime,
        $bufferDays,
        $orderType,
        $warehouse,
        $profileId = null
    ) {
        $calculateDays = $leadTime;
        $orderType = strtoupper($orderType);
        $orderBufferNeeded = [
            self::ORDER_HANPUKAI,
            self::ORDER_SUBSCRIPTION,
            self::ORDER_DELAY_PAYMENT
        ];
        if (in_array($orderType, $orderBufferNeeded)) {
            $orderTimes = $this->getOrderTime($profileId);
            if ($orderTimes >= 1) {
                $calculateDays += $bufferDays;
            }
        }
        $holidaySaturday = $warehouse->getHolydaySettingSaturdayEnable();
        $holidaySunday = $warehouse->getHolydaySettingSundaysEnable();
        $specialDaysRaw = $warehouse->getSpecificHolidays();
        $specialDays = [];
        if ($specialDaysRaw) {
            $specialDays = explode(';', $specialDaysRaw);
        }
        //calculate the cut off date
        $neededDays = 0;
        $isFirstCheck = 1;
        $compareDate = null;
        while ($neededDays <= $calculateDays) {
            if ($isFirstCheck) {
                $compareDate = $deliveryDate;
                $isFirstCheck = 0;
            } else {
                $compareDate = date('Y-m-d', strtotime($compareDate . ' -1 day'));
            }
            $flagNonWorkingDay = $this->validateHolidays(
                $compareDate,
                $specialDays,
                $holidaySaturday,
                $holidaySunday
            );
            if (!$flagNonWorkingDay) {
                $neededDays++;
            }
        }
        return $compareDate;
    }
    /**
     * @param $compareDate
     * @param $specialDays
     * @param $holidaySaturday
     * @param $holidaySunday
     * @return bool|int
     */
    protected function validateHolidays($compareDate, $specialDays, $holidaySaturday, $holidaySunday)
    {
        $compareDateString = strtolower(date('l', strtotime($compareDate)));
        $result = false;
        switch ($compareDateString) {
            case 'saturday':
                $result = $this->checkNonWorkingDay($compareDate, $holidaySaturday, $specialDays);
                break;
            case 'sunday':
                $result = $this->checkNonWorkingDay($compareDate, $holidaySunday, $specialDays);
                break;
            default:
                if ($specialDays && in_array($compareDate, $specialDays)) {
                    $result = 1;
                }
                break;
        }
        return $result;
    }

    /**
     * @param $compareDate
     * @param $holidayType
     * @param $specialDays
     * @return bool
     */
    protected function checkNonWorkingDay($compareDate, $holidayType, $specialDays)
    {
        if ($holidayType) {
            return true;
        } else {
            if ($specialDays && in_array($compareDate, $specialDays)) {
                return true;
            }
            return false;
        }
    }
    /**
     * Check if order is single or multiple
     *
     * @param   $orderItemId
     * @param   $originRegionId
     * @return  mixed
     */
    public function checkMultiAddressItem($orderItemId, $originRegionId)
    {
        $collectionAddress = $this->addressItemCollection;
        $collectionAddress->addFieldToFilter('order_item_id', $orderItemId)->load();
        $collectionAddress->setPageSize(1);
        if ($collectionAddress->getSize()) {
            $addressId = $collectionAddress->getFirstItem()->getOrderAddressId();
            try {
                $addressObject = $this->getAddressOrder($addressId);

                if ($addressObject->getId()) {
                    return $addressObject->getRegionId();
                }
            } catch (\Exception $e) {
                return $originRegionId;
            }
        }

        return $originRegionId;
    }

    /**
     * @param $order
     * @param bool|true $saveLog
     * @throws \Exception
     */
    public function updateOrderStatus($order, $saveLog = true)
    {
        $statusData = $this->orderHelper->getInitialOrderStatus($order);

        // use higher priority rule
        $order->setState($statusData['state']);
        $order->setStatus($statusData['status']);

        $order->setIsNotified(false);

        if ($saveLog) {
            /*add history comment after cut off date success*/
            $order->addStatusHistoryComment(
                __('Update export date order Item'),
                false
            );
        }
    }

    /**
     * @param $addressId
     * @return \Magento\Sales\Api\Data\OrderAddressInterface
     */
    protected function getAddressOrder($addressId)
    {
        return $this->addressOrderRepository->get($addressId);
    }

    /**
     * If is in generate oos order process,
     * need to get correctly order time of original order instead of generated order.
     *
     * @return null|int
     */
    public function getOosOrderTimes()
    {
        $oos = $this->registry->registry('current_oos_generating');
        if (!$oos instanceof \Riki\AdvancedInventory\Model\OutOfStock) {
            return null;
        }

        $origOrder = $oos->getOriginalOrder();
        if (!$origOrder instanceof \Magento\Sales\Model\Order) {
            return null;
        }

        return (int)$origOrder->getSubscriptionOrderTime();
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return bool
     */
    public function getFirstChildenItem(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $items = $orderItem->getChildrenItems();
        if ($items) {
            $firstItem = array_shift($items);
            return $firstItem->getId();
        }
        return false;
    }

    /**
     * @param $profileId
     * @return int|mixed|null
     */
    public function getOrderTime($profileId)
    {
        $orderTimes = 0;
        try {
            $profileModel = $this->profileRepository->get($profileId);
            $ossOrderTimes = $this->getOosOrderTimes();
            $orderTimes = isset($ossOrderTimes)
                ? $ossOrderTimes
                : $profileModel->getOrderTimes();
        } catch (NoSuchEntityException $e) {
            $this->logger->info(__('Could not load profile model with id:'.$profileId));
        }
        return $orderTimes;
    }

    /**
     * Get content log
     * @return string
     */
    public function getContentLog()
    {
        return $this->contentLog;
    }
}//end class
