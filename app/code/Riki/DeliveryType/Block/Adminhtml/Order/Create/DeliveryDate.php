<?php
namespace Riki\DeliveryType\Block\Adminhtml\Order\Create;

use Magento\Framework\Pricing\PriceCurrencyInterface;

class DeliveryDate extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{
    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $modelDeliveryDate;
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;
    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $_pointOfSaleFactory;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_courseFactory;

    /**
     * @var \Riki\Subscription\Model\Frequency\FrequencyFactory
     */
    protected $_frequencyFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_tzHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_subscriptionCourseModel;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Riki\DeliveryType\Model\DeliveryDate $modelDeliveryDate,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory,
        \Magento\Framework\Stdlib\DateTime\Timezone $tzHelper,
        \Riki\SubscriptionCourse\Model\Course $subscriptionCourseModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        array $data = []
    ) {
        $this->_dateTime = $dateTime;
        $this->_subscriptionCourseModel = $subscriptionCourseModel;
        $this->modelDeliveryDate = $modelDeliveryDate;
        $this->_regionFactory = $regionFactory;
        $this->_pointOfSaleFactory = $pointOfSaleFactory;
        $this->_courseFactory = $courseFactory;
        $this->_frequencyFactory = $frequencyFactory;
        $this->_tzHelper = $tzHelper;

        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $data);
    }

       /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('sales_order_create_deliverydate');
    }

    /**
     * Retrieve quote shipping address model
     *
     * @return \Magento\Quote\Model\Quote\Address
     */
    public function getAddress()
    {
        return $this->getQuote()->getShippingAddress();
    }

    /**
     * Is subscription checkout
     *
     * @return bool
     */
    public function isSubscriptionCheckout()
    {
        $quote = $this->getQuote();
        return $quote->hasData("riki_course_id") &&  !empty($quote->getData("riki_course_id"));
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
     * Get calendar
     *
     * @return array|bool
     */
    public function checkCalendar()
    {
        $quote = $this->getQuote();
        $address = $this->getAddress();
        if( !$quote->hasItems() || !$address) {
            return false;
        }

        $destination = [];
        $destination['country_code'] = 'JP';
        $regions = $this->_regionFactory->create();
        $destination['region_code'] = $regions->load($address->getRegionId())->getCode();
        $destination['postcode'] = $address->getPostcode();

        $listDeliveryTypeGroupByItem = $this->modelDeliveryDate->splitQuoteByDeliveryType($quote);
        $calendar = [];
        foreach ($listDeliveryTypeGroupByItem as $key => $deliveryItem)
        {
            if($key == \Riki\DeliveryType\Model\Delitype::COLD) {
                //get assignation warehouse for some item same delivery type
                $assignationGroupByDeliveryType = $this->modelDeliveryDate->calculateWarehouseGroupByItem($destination,$quote,$deliveryItem);

                $listType = [];
                $listType[] = \Riki\DeliveryType\Model\Delitype::COLD;

                $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                $listWh = $this->_getListWarehouse($listPlace);

                $dataCalendar = $this->getDeliveryCalendar($listWh,$listType,$destination['region_code']);
                $dataCalendar['timeslot'] =$this->modelDeliveryDate->getListTimeSlot();
                $dataCalendar['name'] = \Riki\DeliveryType\Model\Delitype::COLD;
                $calendar[] = $dataCalendar;

            } else if($key == \Riki\DeliveryType\Model\Delitype::CHILLED) {
                //get assignation warehouse for some item same delivery type
                $assignationGroupByDeliveryType = $this->modelDeliveryDate->calculateWarehouseGroupByItem($destination,$quote,$deliveryItem);

                $listType = [];
                $listType[] = \Riki\DeliveryType\Model\Delitype::CHILLED;

                $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                $listWh = $this->_getListWarehouse($listPlace);

                $dataCalendar = $this->getDeliveryCalendar($listWh,$listType,$destination['region_code']);
                $dataCalendar['timeslot'] =$this->modelDeliveryDate->getListTimeSlot();
                $dataCalendar['name'] = \Riki\DeliveryType\Model\Delitype::CHILLED;
                $calendar[] = $dataCalendar;

            } else if ($key == \Riki\DeliveryType\Model\Delitype::COSMETIC) {
                //get assignation warehouse for some item same delivery type
                $assignationGroupByDeliveryType = $this->modelDeliveryDate->calculateWarehouseGroupByItem($destination,$quote,$deliveryItem);

                $listType = [];
                $listType[] = \Riki\DeliveryType\Model\Delitype::COSMETIC;

                $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                $listWh = $this->_getListWarehouse($listPlace);

                $dataCalendar = $this->getDeliveryCalendar($listWh,$listType,$destination['region_code']);
                $dataCalendar['timeslot'] =$this->modelDeliveryDate->getListTimeSlot();
                $dataCalendar['name'] = \Riki\DeliveryType\Model\Delitype::COSMETIC;
                $calendar[] = $dataCalendar;

            } else {
                //get assignation warehouse for group item same delivery type
                $assignationGroupByDeliveryType = $this->modelDeliveryDate->calculateWarehouseGroupByItem($destination,$quote,$deliveryItem);
                $timeSlot = false;
                //get list delivery type
                if($assignationGroupByDeliveryType) {
                    $listPlace = explode(",", $assignationGroupByDeliveryType['place_ids']);
                    $listWh = $this->_getListWarehouse($listPlace);

                    $listType = $this->modelDeliveryDate->getDeliveryTypeFromListItem($deliveryItem);

                    $dataCalendar = $this->getDeliveryCalendar($listWh,$listType,$destination['region_code']);
                    $assignationGroupByDeliveryType['items'] = isset($assignationGroupByDeliveryType['items']) ? $assignationGroupByDeliveryType['items'] : [];

                    if(isset($assignationGroupByDeliveryType['items'])) {
                        $checkOnlyDm = $this->modelDeliveryDate->checkOnlyDirectMailCheckout($listType);
                        if(!$checkOnlyDm) {
                            $timeSlot = $this->modelDeliveryDate->getListTimeSlot();
                        }
                    }
                } else {
                    $dataCalendar = [];
                }
                
                $dataCalendar['timeslot'] = $timeSlot;
                $dataCalendar['name'] = $key;

                $calendar[] = $dataCalendar;
            }
        }

        return $calendar;
    }

    /**
     * Caculate list days will disable for calendar , group item have same delivery type
     *
     * @param $listWh
     * @param $listType
     * @param $regionCode
     * @return array
     */
    public function getDeliveryCalendar($listWh,$listType,$regionCode)
    {
        //caculate number next date
        $leadTimeCollection = $this->modelDeliveryDate->caculateDate($listWh,$listType,$regionCode);

        $numberNextDate = 0;
        $posCode = $listWh[0];

        if($leadTimeCollection) {
            $numberNextDate = $leadTimeCollection['shipping_lead_time'];
            $posCode = $leadTimeCollection['warehouse_id'];
        }

        $finalDelivery = $this->modelDeliveryDate->caculateFinalDay($numberNextDate,$posCode);

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
     * Can show calendar
     *
     * @return mixed
     */
    public function getIsShow()
    {
        return $this->getRequest()->getParam('is_show_calendar');
    }

    /**
     * @return mixed
     */
    public function getCalendarPeriod()
    {
        return $this->modelDeliveryDate->getCalendarPeriod();
    }

    /**
     * get course info
     *
     * @return \Magento\Framework\Data\Object
     */
    public function getCourseInfo()
    {
        $objCourseInfo = new \Magento\Framework\DataObject();
        $objCourseInfo->setData([
                'intervalFrequency' => '',
                'unitFrequency' => '',
                'isAllowChangeNextDD' => '',
            ]);

        $objQuote = $this->getQuote();

        if(empty($objQuote->getData("riki_course_id"))) {
            return $objCourseInfo;
        }

        $courseId = $objQuote->getData("riki_course_id");
        $objCourse = $this->_courseFactory->create()->load($courseId);

        $frequencyId = $objQuote->getData("riki_frequency_id");

        $objFrequency = $this->_frequencyFactory->create()->load($frequencyId);


        $objCourseInfo->setData('intervalFrequency', $objFrequency->getData("frequency_interval"));
        $objCourseInfo->setData('unitFrequency', $objFrequency->getData("frequency_unit"));
        $objCourseInfo->setData('isAllowChangeNextDD', $objCourse->isAllowChangeNextDeliveryDate());

        return $objCourseInfo;

    }

    /**
     * Get current date server
     *
     * @return string
     */
    public function getCurrentDateServer() {
        return $this->_tzHelper->date()->format("Y-m-d");
    }

    /**
     * Get Riki course id
     *
     * @return mixed
     */
    public function getRikiCourseId()
    {
        return $this->getQuote()->getData('riki_course_id');
    }

    /**
     * Get Subscription Type
     *
     * @param $courseId
     * @return mixed
     */
    public function getSubscriptionType($courseId)
    {
        return $this->getSubscriptionCourseModelFromCourseId($courseId)->getData('subscription_type');
    }

    /**
     * GetSubscriptionCourseModelFromCourseId
     *
     * @param $courseId
     * @return $this
     */
    public function getSubscriptionCourseModelFromCourseId($courseId)
    {
        return $this->_subscriptionCourseModel->load($courseId);
    }

    /**
     * Is Hanpukai
     *
     * @param $courseId
     * @return bool
     */
    public function isHanpukai($courseId)
    {
        if($this->getSubscriptionType($courseId) == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            return true;
        }
        return false;
    }

    /**
     * Is Allow Change First Delivery Date
     *
     * @param $courseId
     * @return mixed
     */
    public function isAllowChangeFirstDeliveryDate($courseId)
    {
        return $this->getSubscriptionCourseModelFromCourseId($courseId)->getData('hanpukai_delivery_date_allowed');
    }

    /**
     * Format Hanpukai Date
     *
     * @param $stringDate
     * @return string
     */
    public function formatHanpukaiDate($stringDate)
    {
        return $this->_dateTime->date('Y-m-d', strtotime($stringDate));
    }
 }
