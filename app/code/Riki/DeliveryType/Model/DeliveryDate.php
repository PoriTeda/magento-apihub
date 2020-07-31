<?php

namespace Riki\DeliveryType\Model;

use Magento\Backend\App\Area\FrontNameResolver;
use Riki\Subscription\Model\Constant as SubscriptionConstant;
use \Riki\BackOrder\Helper\Data as BackOrderHelper;

class DeliveryDate
{
    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $_posFactory;
    /**
     * @var \Wyomind\Core\Helper\Data
     */
    protected $_helperCore;
    /**
     * @var \Wyomind\AdvancedInventory\Model\StockFactory
     */
    protected $_stockFactory;
    /**
     * @var \Wyomind\AdvancedInventory\Helper\Data
     */
    protected $_helperData;
    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $modelAssignation;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Riki\ShipLeadTime\Model\Leadtime
     */
    protected $leadTimeCollection;
    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $helperDelivery;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateZone;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;
    /**
     * @var \Riki\TimeSlots\Model\ResourceModel\TimeSlots\Collection
     */
    protected $collectionTimeSlot;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var Delitype
     */
    protected $_deliveryType;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;
    /**
     * @var \Magento\Quote\Api\Data\CartItemInterface
     */
    protected $cartItemInterface;
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;
    /**
     * @var \Magento\Sales\Api\Data\OrderItemInterface
     */
    protected $orderItemInterface;
    /**
     * @var \Riki\AdvancedInventory\Helper\AdvancedInventory\Data
     */
    protected $_advancedInventoryHelper;
    /**
     * @var \Wyomind\AdvancedInventory\Model\Stock
     */
    protected $stock;

    /* @var \Riki\BackOrder\Helper\Data */
    protected $backOrderHelper;

    protected $_timeSlot = null;

    protected $_timeSlotOptions;

    protected $_productIdsToAssignedQty = [];
    protected $calendarPeriod;
    protected $editProfileCalendarPeriod;
    protected $maximumEditProfileCalendarPeriod;

    /**
     * @var \Riki\TimeSlots\Model\TimeSlotsFactory
     */
    protected $timeslotModelFactory;

    /**
     * DeliveryDate constructor.
     * @param BackOrderHelper $backOrderHelper
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory
     * @param \Wyomind\Core\Helper\Data $helperCore
     * @param \Wyomind\AdvancedInventory\Model\StockFactory $stockFactory
     * @param \Wyomind\AdvancedInventory\Helper\Data $helperData
     * @param \Riki\AdvancedInventory\Model\Assignation $modelAssignation
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Riki\ShipLeadTime\Model\Leadtime $leadTimeCollection
     * @param \Riki\DeliveryType\Helper\Data $helperDelivery
     * @param \Riki\TimeSlots\Model\ResourceModel\TimeSlots\Collection $collectionTimeSlot
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateZone
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Delitype $collectionDeliveryType
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItemInterface
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItemInterface
     * @param \Riki\AdvancedInventory\Helper\AdvancedInventory\Data $advancedInventoryHelper
     * @param \Wyomind\AdvancedInventory\Model\Stock $stock
     * @param \Riki\TimeSlots\Model\TimeSlotsFactory $timeslotModelFactory
     */
    public function __construct(
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory,
        \Wyomind\Core\Helper\Data $helperCore,
        \Wyomind\AdvancedInventory\Model\StockFactory $stockFactory,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Riki\AdvancedInventory\Model\Assignation $modelAssignation,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\ShipLeadTime\Model\Leadtime $leadTimeCollection,
        \Riki\DeliveryType\Helper\Data $helperDelivery,
        \Riki\TimeSlots\Model\ResourceModel\TimeSlots\Collection $collectionTimeSlot,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateZone,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\DeliveryType\Model\Delitype $collectionDeliveryType,
        \Magento\Framework\App\State $appState,
        \Magento\Quote\Api\Data\CartItemInterface $cartItemInterface,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Sales\Api\Data\OrderItemInterface $orderItemInterface,
        \Riki\AdvancedInventory\Helper\AdvancedInventory\Data $advancedInventoryHelper,
        \Wyomind\AdvancedInventory\Model\Stock $stock,
        \Riki\TimeSlots\Model\TimeSlotsFactory $timeslotModelFactory
    ) {
        $this->backOrderHelper = $backOrderHelper;
        $this->_posFactory = $posFactory;
        $this->_helperCore = $helperCore;
        $this->_stockFactory = $stockFactory;
        $this->_helperData = $helperData;
        $this->modelAssignation = $modelAssignation;
        $this->_productFactory = $productFactory;
        $this->leadTimeCollection = $leadTimeCollection;
        $this->helperDelivery = $helperDelivery;
        $this->collectionTimeSlot = $collectionTimeSlot;
        $this->dateZone = $dateZone;
        $this->timezone = $timezone;
        $this->_scopeConfig = $scopeConfig;
        $this->_deliveryType = $collectionDeliveryType;
        $this->_appState = $appState;
        $this->cartItemInterface = $cartItemInterface;
        $this->quoteRepository = $quoteRepository;
        $this->orderItemInterface = $orderItemInterface;
        $this->_advancedInventoryHelper = $advancedInventoryHelper;
        $this->stock = $stock;
        $this->timeslotModelFactory = $timeslotModelFactory;
    }

    /**
     * @return \Riki\DeliveryType\Helper\Data
     */
    public function getDeliveryTypeHelper()
    {
        return $this->helperDelivery;
    }

    /**
     * Get list time slot from database
     *
     * @return array|null
     */
    public function getListTimeSlot()
    {
        if ($this->_timeSlot === null) {
            $arrTimeSlot = [];
            $collection = $this->collectionTimeSlot->addOrder("position", \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);
            if ($collection->getSize()) {
                $arrTimeSlot[] = [
                    'value' => -1,
                    'label' => __("Unspecified")
                ];
                foreach ($collection->getData() as $data) {
                    $arrTimeSlot[] = [
                        'value' => $data["id"],
                        'label' => $data['slot_name']
                    ];
                }
            }

            $this->_timeSlot = $arrTimeSlot;
        }
        return $this->_timeSlot;
    }

    /**
     * collection to array (id  => name)
     *
     * @return array
     */
    public function toOptions()
    {
        if ($this->_timeSlotOptions === null) {
            $this->_timeSlotOptions = [];
            $collection = $this->collectionTimeSlot->addOrder("position", \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);
            if ($collection->getSize()) {
                foreach ($collection->getData() as $data) {
                    $this->_timeSlotOptions[$data['id']] = $data['slot_name'];
                }
            }
        }
        return $this->_timeSlotOptions;
    }

    /**
     * @return array|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListTimeSlotForCheckout()
    {
        if ($this->_appState->getAreaCode() == FrontNameResolver::AREA_CODE) {
            return $this->toOptions();
        } else {
            return $this->getListTimeSlot();
        }
    }

    /**
     * @param $timeSlotId
     * @return bool|\Riki\TimeSlots\Model\TimeSlots
     * @throws \Zend_Validate_Exception
     */
    public function getTimeSlotInfo($timeSlotId)
    {
        if ($timeSlotId == -1 || !\Zend_Validate::is($timeSlotId, 'NotEmpty') || $timeSlotId == 0) {
            return false;
        }

        $timeSlotModel = $this->timeslotModelFactory->create()->load($timeSlotId);

        if ($timeSlotModel->getId()) {
            return $timeSlotModel;
        }
        return false;
    }

    /**
     * Check quote only have direct_mail delivery type
     *
     * @param $arrayItem
     * @param bool|false $isOrder
     * @return bool
     */
    public function checkOnlyDirectMailCheckout($listType)
    {
        if (count($listType)) {
            foreach ($listType as $type) {
                if ($type != \Riki\DeliveryType\Setup\UpgradeSchema::DM) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check quote only have direct_mail delivery type
     *
     * @param $arrayItem
     * @param bool|false $isOrder
     * @return bool
     */
    public function checkOnlyDirectMail($arrayItem, $isOrder = false)
    {
        // for edit order case
        if ($isOrder) {
            return $this->checkOnlyDirectMailByOrderItems($arrayItem);
        }

        return true;
    }

    /**
     * Get nam of group item cool_normal_directmail
     *
     * @param $listType
     *
     * @return bool|string
     */
    public function getNameGroup($listType)
    {
        $cool = $normal = $directMail = false;

        foreach ($listType as $type) {
            if ($type == \Riki\DeliveryType\Model\Delitype::COOL) {
                $cool = true;
            } elseif ($type == \Riki\DeliveryType\Model\Delitype::NORMAl) {
                $normal = true;
            } else {
                $directMail = true;
            }
        }
        if ($cool) {
            return __('Cool ship type');
        } elseif ($normal) {
            return __('Normal ship type');
        } elseif ($directMail) {
            return __('DM ship type');
        }
        return '';
    }


    /**
     * @param $arrayItem
     * @return bool
     */
    public function checkOnlyDirectMailByOrderItems($arrayItem)
    {
        if (empty($arrayItem)) {
            return false;
        }
        $itemIds = [];
        foreach ($arrayItem as $itemId) {
            $itemIds = array_merge($itemIds, array_keys($itemId));
        }

        foreach ($itemIds as $itemId) {
            $itemData = $this->orderItemInterface->load($itemId);
            if ($itemData->getId()) {
                if ($itemData->getDeliveryType() != \Riki\DeliveryType\Setup\UpgradeSchema::DM) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Calculate final restrict date
     * @param $nextDate
     * @param $posCode
     * @param array $extendInfo
     * @param null $customAvailableDate
     * @return array
     */
    public function caculateFinalDay($nextDate, $posCode, $extendInfo = [], $customAvailableDate = null)
    {

        $extendInfo = array_merge([
            'firstCal' => true, // first calculate next delivery date
            'now' => $this->timezone->formatDateTime($this->dateZone->gmtDate(), 2),
        ], $extendInfo);

        $now = ($customAvailableDate and (strtotime($customAvailableDate) > strtotime($extendInfo['now']))) ? $customAvailableDate : $extendInfo['now'];
        $bufferDate = $extendInfo['firstCal'] == true ? 1 : 1 + $this->helperDelivery->getBufferDate();

        $dd = $nextDate + $bufferDate; // + 1 buffer ( it's not config of buffer days)
        $arrDate = [];
        $arrDate[] = date('Y-m-d', strtotime($now));
        for ($i = 1; $i <= $dd; $i++) {
            $nextDay = date('Y-m-d', strtotime($now . ' +' . $i . ' day'));
            // warehouse non working on saturday
            if (date('l', strtotime($nextDay)) == 'Saturday') {
                if ($this->helperDelivery->getHolidayOnSaturday($posCode)) {
                    $dd++;
                }
            }
            // warehouse non working on sunday
            if (date('l', strtotime($nextDay)) == 'Sunday') {
                if ($this->helperDelivery->getHolidayOnSunday($posCode)) {
                    $dd++;
                }
            }
            // this day in list special list holiday of japan
            if ($this->helperDelivery->isSpecialHoliday($posCode, $nextDay)) {
                if (date('l', strtotime($nextDay)) != 'Saturday' && date('l', strtotime($nextDay)) != 'Sunday') {
                    $dd++;
                }
                if (date('l', strtotime($nextDay)) == 'Saturday' && !$this->helperDelivery->getHolidayOnSaturday($posCode)) {
                    $dd++;
                }
                if (date('l', strtotime($nextDay)) == 'Sunday' && !$this->helperDelivery->getHolidayOnSunday($posCode)) {
                    $dd++;
                }
            }
            $arrDate[] = $nextDay;
        }
        array_pop($arrDate);
        return $arrDate;
    }

    /**
     * Caculate Date
     *
     * @param $listWh
     * @param $listType
     * @param $regionCode
     * @return bool|mixed
     */
    public function caculateDate($listWh, $listType, $regionCode)
    {
        $collection = $this->leadTimeCollection->getCollection()->addActiveToFilter();
        $collection->addFieldToFilter('warehouse_id', ['in' => $listWh]);
        $collection->addFieldToFilter('delivery_type_code', ['in' => $listType]);
        $collection->addFieldToFilter("pref_id", $regionCode);
        //get max lead time
        $collection->setOrder('shipping_lead_time', 'DESC');
        $collection->setCurPage(1);
        $collection->setPageSize(1);
        $model = $collection->getFirstItem();

        return $model->hasData() ? $model->getData() : false;
    }

    /**
     * Get list delivery type
     *
     * @param $deliveryItem
     *
     * @return array
     */
    public function getDeliveryTypeFromListItem($deliveryItem)
    {
        $listType = [];
        foreach ($deliveryItem as $itemId) {
            $itemData = $this->cartItemInterface->load($itemId);
            if ($itemData->getId()) {
                $listType[] = $itemData->getDeliveryType();
            }
        }
        return $listType;
    }



    /**
     * Caculate delivery type
     *
     * @param $assignation
     * @param bool|false $orderMode
     * @return array
     */
    public function caculateDeliveryType($assignation, $orderMode = false)
    {
        $deliveryType = [];
        $posIds = explode(",", $assignation['place_ids']);
        foreach ($posIds as $posId) {
            $deliveryType[$posId] = [];
            $items = isset($assignation['items']) ? $assignation['items'] : [];
            foreach ($items as $itemId => $val) {
                //load item
                if ($orderMode) {
                    $itemData = $this->orderItemInterface->load($itemId);
                } else {
                    $itemData = $this->cartItemInterface->load($itemId);
                }
                if ($itemData->getId()) {
                    $deliveryItem = $itemData->getDeliveryType();
                    /*if(!$deliveryItem) {
                        $deliveryProduct =  \Riki\DeliveryType\Setup\UpgradeSchema::NORMAR;
                    }*/
                    foreach ($val["pos"] as $posIdItem => $qty) {
                        if ($posIdItem == $posId && $deliveryItem && !in_array($deliveryItem, $deliveryType[$posId])) {
                            $deliveryType[$posId][] = $deliveryItem;
                        }
                    }
                }
            }
        }
        return $deliveryType;
    }


    /**
     * Function will calculate warehouse for a group item
     *
     * @param $destination
     * @param $quote
     * @param $deliveryItem
     *
     * @return array
     */
    public function calculateWarehouseGroupByItem($destination, $quote, $deliveryItem)
    {
        $quoteItems = $this->getAssignationByQuoteId($quote->getEntityId(), $deliveryItem);
        return $this->calculateWarehouseGroupByCollection($destination, $quoteItems, $quote->getStoreId());
    }

    /**
     * @param $destination
     * @param $collectionData
     * @param $storeId
     * @return array
     */
    public function calculateWarehouseGroupByCollection($destination, $collectionData, $storeId)
    {

        $assignTo = ["place_ids" => [], 'item_ids' => [], 'items'   =>  []];

        $allowMultipleAssign = $this->_scopeConfig->getValue(
            "advancedinventory/settings/multiple_assignation_enabled",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        // add temp order item data to pass assignationProcess function
        foreach ($collectionData as $key => $item) {
            $collectionData[$key]['qty_to_assign'] = $item['qty'];
            $collectionData[$key]['qty_ordered'] = 1;
        }

        $itemGroupData = [
            'items' => $collectionData,
            'places' => $this->modelAssignation->getAssignationHelper()
                ->getPointOfSaleHelper()
                ->getPlacesByStore($storeId),
            'destination' => $destination
        ];

        $assignTo = $this->modelAssignation->assignationProcess($assignTo, $itemGroupData, $allowMultipleAssign);
        $this->modelAssignation->resetLog();

        sort($assignTo["place_ids"]);
        $assignTo["place_ids"] = implode(",", array_unique($assignTo["place_ids"]));

        return $assignTo;
    }

    protected $_productTypes = ['simple', 'virtual', 'downloadable', 'grouped'];

    /**
     * @param $quoteId
     * @param bool $itemId
     * @return mixed
     */
    public function getAssignationByQuoteId($quoteId, $itemId = false, array $sharedStoreIds = [])
    {
        if ($itemId) {
            //merge item child of bundle
            foreach ($itemId as $id) {
                $itemObject = $this->cartItemInterface->load($id);
                if ($itemObject->getRealProductType() == 'bundle') {
                    $sharedStoreIds[] = $itemObject->getStoreId();
                    $cart = $this->quoteRepository->get($quoteId, $sharedStoreIds);
                    if ($cart) {
                        $items = $cart->getAllItems();
                        foreach ($items as $item) {
                            if ($item->getParentItemId() == $id) {
                                $itemId[] = $item->getId();
                            }
                        }
                    }
                }
            }
        }
        $collection = $this->getAssignationQuoteItem($quoteId, $itemId);
        return $collection;
    }

    /**
     * Get assignation quote item
     *
     * @param $quoteId
     * @param bool $itemId
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Item\Collection
     */
    public function getAssignationQuoteItem($quoteId, $itemId = false)
    {
        $quoteItemCollection = $this->cartItemInterface->getCollection();
        //add filter by quote id
        $quoteItemCollection->addFieldToFilter("quote_id", ["eq" => $quoteId]);

        //add filter by array item id of current quote
        if ($itemId) {
            $quoteItemCollection->addFieldToFilter("item_id", ['in' => $itemId]);
        }

        $quoteItemCollection->addFieldToFilter("product_type", ['in' => $this->_productTypes]);

        $productIds = [];

        $deliveryTypes = [];

        foreach ($quoteItemCollection->getData() as $quoteItem) {
            $productIds[] = $quoteItem['product_id'];

            $deliveryTypes[$quoteItem['item_id']] = $quoteItem['delivery_type'];
        }

        $productMultiStockStatus = $this->_advancedInventoryHelper->getAdvancedInventoryStockStatus($productIds);

        $itemData = $quoteItemCollection->getData();
        foreach ($itemData as $k => $quoteItem) {
            if (isset($productMultiStockStatus[$quoteItem['product_id']])) {
                $itemData[$k]['multistock_enabled'] = $productMultiStockStatus[$quoteItem['product_id']];
            } else {
                $itemData[$k]['multistock_enabled'] = 0;
            }

            if ($quoteItem['parent_item_id'] && isset($deliveryTypes[$quoteItem['parent_item_id']])) {
                $quoteItem[$k]['delivery_type'] = $deliveryTypes[$quoteItem['parent_item_id']];
            }
        }

        return $itemData;
    }


    /**
     * @param bool $itemId
     * @return mixed
     */
    public function getAssignationByOrderItem($itemId = false)
    {
        $orderItemCollection = $this->orderItemInterface->getCollection();

        //add filter by array item id of current quote
        if ($itemId) {
            $orderItemCollection->addFieldToFilter("item_id", ['in' => $itemId]);
        }

        $orderItemCollection->addFieldToFilter("product_type", ['in' => $this->_productTypes]);
        
        $orderItemCollection->getSelect()
            ->columns(
                [
                    "name" => "name",
                    "sku" => "sku",
                    "item_id" => "item_id",
                    "product_id" => "product_id",
                    "product_type" => "product_type",
                    "qty" => "qty_ordered",
                    "parent_item_id" => "parent_item_id",
                    "delivery_type" => "delivery_type",
                ]
            );

        $productIds = [];

        $deliveryTypes   =   [];

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($orderItemCollection->getData() as $orderItem) {
            $productIds[] = $orderItem['product_id'];
            $deliveryTypes[$orderItem['item_id']] = $orderItem['delivery_type'];
        }

        $productMultiStockStatus = $this->_advancedInventoryHelper->getAdvancedInventoryStockStatus($productIds);

        $orderItemData = $orderItemCollection->getData();
        foreach ($orderItemData as $k => $orderItem) {
            if (isset($productMultiStockStatus[$orderItem['product_id']])) {
                $orderItemData[$k]['multistock_enabled'] = $productMultiStockStatus[$orderItem['product_id']];
            } else {
                $orderItemData[$k]['multistock_enabled'] = 0;
            }

            if ($orderItem['parent_item_id'] && isset($deliveryTypes[$orderItem['parent_item_id']])) {
                $orderItemData[$k]['delivery_type'] = $deliveryTypes[$orderItem['parent_item_id']];
            }
        }

        return $orderItemData;
    }

    /**
     * Get config calendar display period from back end
     *
     * @return mixed
     */
    public function getCalendarPeriod()
    {
        if ($this->calendarPeriod === null) {
            $this->calendarPeriod = $this->_scopeConfig->getValue('deliverydate/calendar_period/day_period');
        }
        return $this->calendarPeriod;
    }
    /**
     * Get config calendar display period from back end
     *
     * @return mixed
     */
    public function getEditProfileCalendarPeriod()
    {
        if ($this->editProfileCalendarPeriod === null) {
            $this->editProfileCalendarPeriod = $this->_scopeConfig->getValue('deliverydate/calendar_period/edit_profile_day_period');
        }
        return $this->editProfileCalendarPeriod;
    }

    /**
     * Get config calendar display period from back end
     *
     * @return mixed
     */
    public function getMaximumEditProfileCalendarPeriod()
    {
        if ($this->maximumEditProfileCalendarPeriod === null) {
            $this->maximumEditProfileCalendarPeriod = $this->_scopeConfig->getValue('deliverydate/calendar_period/maximum_edit_profile_day_period');
        }
        return $this->maximumEditProfileCalendarPeriod;
    }

    /**
     * Split quote cart by delivery type of item
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    public function splitQuoteByDeliveryType($quote)
    {
        $result = [];

        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            if (!isset($result[$item->getDeliveryType()])) {
                $result[$item->getDeliveryType()] = [];
            }

            $result[$item->getDeliveryType()][] = $item->getId();
        }

        return $result;
    }

    protected $arrayDeliveryType = null;

    /**
     * Get all delivery type from database
     *
     * @return array|null
     */
    public function getListDeliveryType()
    {
        if ($this->arrayDeliveryType === null) {
            $listDeliveryType = [];
            $data = $this->_deliveryType->getCollection();
            foreach ($data->getData() as $value) {
                $deliveryCode = $value['code'];
                $listDeliveryType[] = $deliveryCode;
            }
            $this->arrayDeliveryType = $listDeliveryType;
        }
        return $this->arrayDeliveryType;
    }

    /**
     * @param $destination
     * @param $storeId
     * @param $orderItemIds
     * @param $deliveryType
     * @return array
     */
    public function getLimitDeliveryDateDataByOrderItems($destination, $storeId, $orderItemIds, $deliveryType)
    {

        $collectionData = $this->getAssignationByOrderItem($orderItemIds);

        //get assignation warehouse for some item same delivery type
        $assignationGroupByDeliveryType = $this->calculateWarehouseGroupByCollection(
            $destination,
            $collectionData,
            $storeId
        );

        $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
        $listWh = [];
        foreach ($listPlace as $posId) {
            if (!in_array($posId, $listWh)) {
                $pointOfSale = $this->_posFactory->create()->load($posId);
                $listWh[] = $pointOfSale->getStoreCode();
            }
        }
        $calendarInfo = $this->getDeliveryCalendar($listWh, [$deliveryType], $destination['region_code']);

        if (in_array($deliveryType, [Delitype::COLD, Delitype::CHILLED, Delitype::COSMETIC])) {
            $calendarInfo['time_slots'] = $this->toOptions();
        } else {
            $checkOnlyDm = $this->checkOnlyDirectMailByOrderItems(isset($assignationGroupByDeliveryType['item_ids']) ? $assignationGroupByDeliveryType['item_ids'] : []);
            if ($checkOnlyDm) {
                $calendarInfo['time_slots'] = false;
                $calendarInfo['only_dm'] = 1;
            } else {
                $calendarInfo['time_slots'] = $this->toOptions();
            }
        }

        return $calendarInfo;
    }

    /**
     * Caculate list days will disable for calendar , group item have same delivery type
     *
     * @param $listWh
     * @param $listType
     * @param $regionCode
     * @return array
     */
    public function getDeliveryCalendar($listWh, $listType, $regionCode, $extendInfo = [])
    {
        //caculate number next date
        $leadTimeCollection = $this->caculateDate($listWh, $listType, $regionCode);

        $numberNextDate = 0;
        $posCode = $listWh[0];

        if ($leadTimeCollection) {
            $numberNextDate = $leadTimeCollection['shipping_lead_time'];
            $posCode = $leadTimeCollection['warehouse_id'];
        }

        $finalDelivery = $this->caculateFinalDay($numberNextDate, $posCode, $extendInfo);

        //caculate preriod display calendar
        if ($this->getCalendarPeriod()) {
            $period = $this->getCalendarPeriod() + count($finalDelivery) - 1;
        } else {
            $period = 29 + count($finalDelivery);
        }
        $calendar = [
            "period" => $period,
            "deliverydate" => $finalDelivery
        ];

        return $calendar;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    public function isBackOrder(\Magento\Quote\Model\Quote $quote, $assignationGroupByDeliveryType, $deliveryItem)
    {
        return false;
    }
    /**
     * Get list warehouse
     *
     * @param $listPlace
     *
     * @return array
     */
    public function getListWH($listPlace)
    {
        $listWh = [];
        foreach ($listPlace as $posId) {
            if (!in_array($posId, $listWh)) {
                $pointOfSale = $this->_posFactory->create()->load($posId);
                $listWh[] = $pointOfSale->getStoreCode();
            }
        }
        return $listWh;
    }
}
