<?php
namespace Riki\Subscription\Helper;

use Wyomind\AdvancedInventory\Model\ResourceModel\Item\CollectionFactory as ItemCollectionFactory;
use \Riki\BackOrder\Helper\Data as BackOrderHelper;
use Riki\DeliveryType\Model\Delitype as Dtype;

class CalculateDeliveryDate extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DEFAULT_COUNTRY_CODE = 'JP';
    const DEFAULT_REGION_CODE = 'TKY';
    const SATURDAY = 'Saturday';
    const SUNDAY = 'Sunday';

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection
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
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $modelDeliveryDate;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    protected $productCartModel;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $stdTimezone;

    /**
     * @var \Riki\Subscription\Model\Frequency\FrequencyFactory
     */
    protected $fqFactory;

    /**
     * @var \Wyomind\AdvancedInventory\Model\ResourceModel\Item\CollectionFactory
     */
    protected $itemCollectionFactory;

    /**
     * @var \Riki\BackOrder\Helper\Data
     */
    protected $backOrderHelperData;

    /**
     * @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory
     */
    protected $pointOfSaleCollectionFactory;

    /**
     * CalculateDeliveryDate constructor.
     * @param \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory
     * @param BackOrderHelper $backOrderHelper
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\CollectionFactory $leadTimeCollectionFactory
     * @param \Riki\DeliveryType\Helper\Data $helperDelivery
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartModel
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Riki\DeliveryType\Model\DeliveryDate $modelDeliveryDate
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone2
     * @param \Riki\Subscription\Model\Frequency\FrequencyFactory $fqFactory
     */
    public function __construct(
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory,
        \Riki\BackOrder\Helper\Data $backOrderHelper,
        ItemCollectionFactory $itemCollectionFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\CollectionFactory $leadTimeCollectionFactory,
        \Riki\DeliveryType\Helper\Data $helperDelivery,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartModel,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Riki\DeliveryType\Model\DeliveryDate $modelDeliveryDate,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone2,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $fqFactory
    ) {
        $this->pointOfSaleCollectionFactory = $pointOfSaleCollectionFactory;
        $this->backOrderHelperData = $backOrderHelper;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->stdTimezone = $timezone2;
        $this->productCartModel = $productCartModel;
        $this->regionFactory = $regionFactory;
        $this->productFactory = $productFactory;
        $this->leadTimeCollection = $leadTimeCollectionFactory;
        $this->helperDelivery = $helperDelivery;
        $this->dateZone = $dateTime;
        $this->timeZone = $timezone;
        $this->addressRepository = $addressRepositoryInterface;
        $this->modelDeliveryDate = $modelDeliveryDate;
        $this->fqFactory = $fqFactory;

        parent::__construct($context);
    }

    /**
     * get Region Code And Post Code
     *
     * @param $customerAddressId
     * @return array
     */
    public function getRegionCodeAndPostCode($customerAddressId)
    {
        $result = [];
        $address = $this->addressRepository->getById($customerAddressId);
        if ($address) {
            $regions = $this->regionFactory->create();
            $result['region_code'] = $regions->load($address->getRegionId())->getCode();
            $result['postcode'] = $address->getPostcode();
        }
        return $result;
    }

    /**
     * get Calendar
     *
     * @param $addressId
     * @param $arrProductInCart
     * @param null $deliveryType
     * @param null $bufferDay
     * @param null $nextDeliveryDateMain
     * @return array
     */
    public function getCalendar($addressId, $arrProductInCart, $deliveryType = null, $bufferDay = null, $nextDeliveryDateMain = null)
    {
        /*address destination*/
        $destination = $this->getDestinationByAddressId($addressId);

        if (empty($deliveryType)) {
            $deliveryType = $this->getDeliveryTypeForProductGroup($arrProductInCart['product']);
        }

        /*get warehouse lead time*/
        $warehouseLeadTime = $this->getWarehouseMaxLeadTime($deliveryType, $destination);

        /**
         * $nextDeliveryDateMain only available when edit profile have temp
         */
        return $this->calculateFinalDay(
            $warehouseLeadTime['maxLeadTime'],
            $warehouseLeadTime['warehouseCode'],
            $bufferDay,
            $nextDeliveryDateMain
        );
    }

    /**
     * Get address destination by address id
     *
     * @param $addressId
     * @return array
     */
    public function getDestinationByAddressId($addressId)
    {
        $destination = [];
        $destination['country_code'] = self::DEFAULT_COUNTRY_CODE;
        $destination['region_code'] = self::DEFAULT_REGION_CODE;
        $regionCodeAndPostCode = $this->getRegionCodeAndPostCode($addressId);
        if ($regionCodeAndPostCode != null) {
            $destination['region_code'] = $regionCodeAndPostCode['region_code'];
            $destination['postcode'] = $regionCodeAndPostCode['postcode'];
        }

        return $destination;
    }

    /**
     * Get warehouse max lead time
     *
     * @param $deliveryType
     * @param $destination
     * @return array
     */
    private function getWarehouseMaxLeadTime($deliveryType, $destination)
    {
        $regionCode = $destination['region_code'];
        $maxLeadTime = 0;
        $wareHouseCode = '';

        /*get warehouse lead time data*/
        $wareHouseLeadTime = $this->getWarehouseLeadTime($regionCode, $deliveryType);

        if ($wareHouseLeadTime) {
            $maxLeadTime = $wareHouseLeadTime->getData('shipping_lead_time');
            $wareHouseCode = $wareHouseLeadTime->getData('warehouse_id');
        }

        if (empty($maxLeadTime) && empty($wareHouseCode)) {
            $defaultWarehouseLeadTime = $this->getDefaultWarehouseLeadTime([$deliveryType]);
            if ($defaultWarehouseLeadTime) {
                $maxLeadTime = $defaultWarehouseLeadTime->getData('shipping_lead_time');
                $wareHouseCode = $defaultWarehouseLeadTime->getData('warehouse_id');
            }
        }

        return ['maxLeadTime' => $maxLeadTime, 'warehouseCode' => $wareHouseCode];
    }

    /**
     * Get warehouse lead time
     *
     * @param $regionCode
     * @param $productDeliveryType
     * @return bool|\Magento\Framework\DataObject
     */
    private function getWarehouseLeadTime($regionCode, $productDeliveryType)
    {
        /** @var \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection $leadTimeCollection */
        $leadTimeCollection = $this->leadTimeCollection->create();

        $leadTimeCollection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('pref_id', $regionCode)
            ->addFieldToFilter('delivery_type_code', $productDeliveryType)
            ->setOrder('shipping_lead_time', 'DESC');

        if ($leadTimeCollection->getSize()) {
            return $leadTimeCollection->setPageSize(1)->getFirstItem();
        }

        return false;
    }

    /**
     * Get default warehouse lead time
     *
     * @param $deliveryTypeList
     * @return bool|\Magento\Framework\DataObject
     */
    private function getDefaultWarehouseLeadTime($deliveryTypeList)
    {
        /** @var \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection $leadTimeCollection */
        $leadTimeCollection = $this->leadTimeCollection->create()->addActiveToFilter();
        $leadTimeCollection->addFieldToFilter('delivery_type_code', ['in' => $deliveryTypeList]);
        $leadTimeCollection->addFieldToFilter("pref_id", self::DEFAULT_REGION_CODE);
        $leadTimeCollection->setOrder('shipping_lead_time', 'DESC');

        if ($leadTimeCollection->getSize()) {
            return $leadTimeCollection->setPageSize(1)->getFirstItem();
        }

        return false;
    }

    /**
     * Get product delivery type
     *
     * @param $product
     * @return string
     */
    private function getProductDeliveryType($product)
    {
        /*default delivery type*/
        $deliveryType =  \Riki\DeliveryType\Setup\UpgradeData::NORMAR;

        if (!empty($product['instance'])) {
            $productObject = $product['instance'];

            if (!empty($productObject->getData('delivery_type'))) {
                $deliveryType = $productObject->getData('delivery_type');
            }
        }

        return $deliveryType;
    }

    /**
     * @param $addressId
     * @param $arrProductInCart
     * @param $deliveryType
     * @return mixed
     */
    public function calculatePostCode($addressId, $arrProductInCart, $deliveryType = null)
    {
        $destination = $this->getDestinationByAddressId($addressId);

        if (empty($deliveryType)) {
            $deliveryType = $this->getDeliveryTypeForProductGroup($arrProductInCart['product']);
        }

        $warehouseLeadTime = $this->getWarehouseMaxLeadTime($deliveryType, $destination);

        return $warehouseLeadTime['warehouseCode'];
    }

    /**
     * get Place Id By Store Code
     *
     * @param $storeCode
     * @return int
     */
    public function getPlaceIdByStoreCode($storeCode)
    {
        $collection = $this->pointOfSaleCollectionFactory->create()
            ->addFieldToFilter('store_code', $storeCode)
            ->setPageSize(1);
        if ($collection->getSize()) {
            return $collection->getFirstItem()->getPlaceId();
        }
        return 0;
    }

    /**
     * Calculate final restrict date
     *
     * @param $nextDate
     * @param $posCode
     * @return array
     */
    public function calculateFinalDay($nextDate, $posCode, $bufferDays, $nextDeliveryDateMain = null)
    {
        $bufferDays = $bufferDays !== null
            ? $bufferDays
            : $this->helperDelivery->getBufferDate();

        /** @var \DateTime $now - current day*/
        $now = $this->timeZone->date();

        if ($nextDeliveryDateMain) {
            $nextDeliveryDateOfMain = strtotime($nextDeliveryDateMain);
            $nextDeliveryDateOfMain = $this->timeZone->scopeDate(null, date('Y-m-d', $nextDeliveryDateOfMain));
            $now = max($nextDeliveryDateOfMain, $now);
        }
        $dd = $nextDate + $bufferDays + 1;
        $arrDate = [];
        $arrDate[] = $now->format('Y-m-d');
        for ($i=1; $i <= $dd; $i++) {
            /*modify current day to validate some rules for delivery date*/
            $now->modify('+1 day');

            /*restrict date*/
            $restrictDate = $now->format('Y-m-d');

            /*name of current day*/
            $dayName = $now->format('l');

            // warehouse non working on saturday
            if ($dayName == self::SATURDAY) {
                if ($this->helperDelivery->getHolidayOnSaturday($posCode)) {
                    $dd++;
                }
            }
            // warehouse non working on sunday
            if ($dayName == self::SUNDAY) {
                if ($this->helperDelivery->getHolidayOnSunday($posCode)) {
                    $dd++;
                }
            }
            // this day in list special list holiday of japan
            if ($this->helperDelivery->isSpecialHoliday($posCode, $restrictDate)) {
                if ($dayName != self::SATURDAY && $dayName != self::SUNDAY) {
                    $dd++;
                }

                if ($dayName == self::SATURDAY
                    && !$this->helperDelivery->getHolidayOnSaturday($posCode)
                ) {
                    $dd++;
                }

                if ($dayName == self::SUNDAY
                    && !$this->helperDelivery->getHolidayOnSunday($posCode)
                ) {
                    $dd++;
                }
            }
            $arrDate[] = $restrictDate;
        }
        array_pop($arrDate);
        return $arrDate;
    }

    /**
     * @return mixed|void
     */
    public function getCalendarPeriod()
    {
        return $this->modelDeliveryDate->getCalendarPeriod();
    }

    /**
     * @return mixed|void
     */
    public function getEditProfileCalendarPeriod()
    {
        return $this->modelDeliveryDate->getEditProfileCalendarPeriod();
    }

    /**
     * @return mixed|void
     */
    public function getMaximumEditProfileCalendarPeriod()
    {
        return $this->modelDeliveryDate->getMaximumEditProfileCalendarPeriod();
    }

    /**
     * @param $_checkCalendar
     * @return string
     */
    public function calculateFromDateAvailable($_checkCalendar)
    {
        $dateNotAvailable = count($_checkCalendar);
        $dateTimeNow = $this->stdTimezone->date();
        $dateInterval = \DateInterval::createFromDateString($dateNotAvailable. ' '.'day');
        $dateTimeNow->add($dateInterval);
        return $dateTimeNow->format('Y-m-d');
    }

    /**
     * @param $checkCalendar
     * @return string
     */
    public function calculateToDateAvailable($checkCalendar)
    {
        $calendarPeriod = $this->getCalendarPeriod();
        if (!$calendarPeriod) {
            $calendarPeriod = 29;
        } else {
            $calendarPeriod = (int)$calendarPeriod + count($checkCalendar) - 1;
        }
        $dateTimeNow = $this->stdTimezone->date();
        $dateInterval = \DateInterval::createFromDateString($calendarPeriod. ' '.'day');
        $dateTimeNow->add($dateInterval);
        return $dateTimeNow->format('Y-m-d');
    }

    /**
     * @param $date
     * @param $fromDate
     * @param $toDate
     * @return bool
     */
    public function checkDateAvailableBetweenFromTo($date, $fromDate, $toDate)
    {
        $dateFormat = $this->dateZone->gmtDate('YmdHis', $date);
        $fromDateFormat = $this->dateZone->gmtDate('YmdHis', $fromDate);
        $toDateFormat = $this->dateZone->gmtDate('YmdHis', $toDate);
        if ($dateFormat >= $fromDateFormat && $dateFormat <= $toDateFormat) {
            return true;
        }
        return false;
    }

    /**
     * @param $productCartId
     * @return $this
     */
    public function loadProductCartObj($productCartId)
    {
        return $this->productCartModel->create()->load($productCartId);
    }

    /**
     * @param $strBaseDate
     * @param $frequencyId
     * @return string
     */
    public function getDatePlusFrequency($strBaseDate, $frequencyId)
    {
        $objFrequency = $this->fqFactory->create()->load($frequencyId);

        if (empty($objFrequency) || empty($objFrequency->getId())) {
            return $strBaseDate;
        }

        $intBaseDateTime = strtotime($strBaseDate);

        $strFrequencyUnit = $objFrequency->getData("frequency_unit");
        $frequencyInterval = $objFrequency->getData("frequency_interval");

        $timestamp = strtotime($frequencyInterval . " " . $strFrequencyUnit, $intBaseDateTime);

        $objDate  = $this->timeZone->date();
        $objDate->setTimestamp($timestamp);

        return $objDate->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
    }

    /**
     * Check product DM
     *
     * @param $arrProductCartSession
     * @return bool
     */
    public function checkProductDm($arrProductCartSession)
    {
        $productDm = true;
        foreach ($arrProductCartSession as $cartItem) {
            if (!$this->isProductDM($cartItem['product_id'])) {
                return false;
            }
        }

        return $productDm;
    }

    /**
     * product delivery type is DM or not
     *
     * @param $productId
     * @return bool
     */
    public function isProductDM($productId)
    {
        $product = $this->productFactory->create()->load($productId);

        if ($product->getId()) {
            if ($product->getData('delivery_type') == Dtype::DM) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get delivery type for product group
     *
     * @param $productList
     * @return string
     */
    private function getDeliveryTypeForProductGroup($productList)
    {
        /*default delivery type*/
        $deliveryType =  \Riki\DeliveryType\Setup\UpgradeData::NORMAR;

        foreach ($productList as $product) {
            $productDeliveryType = $this->getProductDeliveryType($product);
            if (!empty($productDeliveryType)) {
                $deliveryType = $productDeliveryType;
                break;
            }
        }

        return $deliveryType;
    }
}
