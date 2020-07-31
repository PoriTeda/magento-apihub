<?php
namespace Riki\Sales\Plugin\Sales\Model\AdminOrder;

use Riki\SubscriptionCourse\Model\CourseFactory;

class Create
{
    protected $_quoteAddresses;

    protected $_customerAddressFactory;

    protected $_quoteAddressFactory;

    protected $_quoteSession;

    /**
     * @var $_quoteAddressInterface \Magento\Quote\Api\Data\AddressInterface
     */
    protected $_quoteAddressInterface;

    /**
     * @var $addressItemRelationshipProcess \Riki\Checkout\Api\Data\AddressItemRelationshipInterface
     */
    protected $_addressItemRelationshipProcessor;

    /**
     * @var $_customerAddressRepositoryInterface \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_customerAddressRepositoryInterface;

    protected $_deliveryTypeAdminHelper;

    protected $_catalogHelper;

    protected $_timeSlotHelper;

    protected $_salesAdminHelper;

    protected $_salesAddressHelper;

    /**
     * @var \Riki\MachineApi\Helper\Machine
     */
    protected $machineHelper;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    public function __construct(
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        \Riki\MachineApi\Helper\Machine $machineHelper,
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Riki\TimeSlots\Helper\Data $timeSlotHelper,
        \Riki\Sales\Helper\Admin $salesAdminHelper,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Backend\Model\Session\Quote $quoteSession
    ) {
        $this->_customerAddressFactory = $addressFactory;
        $this->_quoteSession = $quoteSession;
        $this->_quoteAddressFactory = $quoteAddressFactory;
        $this->_customerAddressRepositoryInterface = $addressRepositoryInterface;
        $this->_deliveryTypeAdminHelper = $deliveryTypeAdminHelper;
        $this->_catalogHelper = $catalogHelper;
        $this->machineHelper = $machineHelper;
        $this->courseFactory = $courseFactory;
        $this->_timeSlotHelper = $timeSlotHelper;
        $this->_salesAdminHelper = $salesAdminHelper;
        $this->_salesAddressHelper = $salesAdminHelper->getAddressHelper();
    }

    public function aroundImportPostData(
        $subject,
        \Closure $proceed,
        $data
    ) {
        $result = $proceed($data);

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $result->getQuote();

        // set default shipping address for quote item
        if(isset($data['collect_delivery_information'])){
            foreach($quote->getAllItems() as $quoteItem){
                if(!$quoteItem->getAddressId()){
                    $quoteItem->setAddressId($this->_salesAddressHelper->getDefaultShippingAddress($quote->getCustomer()));
                }
            }
        }

        if(isset($data['allowed_earned_point'])){
            $quote->setAllowedEarnedPoint(1);
        }else{
            $quote->setAllowedEarnedPoint(0);
        }
        if(isset($data['order_channel'])){
            $quote->setOrderChannel($data['order_channel']);
        }
        if(isset($data['campaign_id'])){
            $quote->setCampaignId($data['campaign_id']);
        }
        if(isset($data['charge_type'])){

            $this->_salesAdminHelper->resetQuoteAdditionalData();

            switch($data['charge_type']){
                case \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_NORMAL:
                    $this->_processNormalOrderData();
                    break;
                case \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_REPLACEMENT:
                    $this->_processReplacementOrderData($data);
                    break;
                case \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_FREE_SAMPLE:
                    $this->_processFreeSampleOrderData($data);
                    break;
                default:
                    throw new \Magento\Framework\Exception\LocalizedException(__('Order type is invalid'));
            }

            $quote->setChargeType($data['charge_type']);
        }

        if(isset($data['account']) && isset($data['account']['lastnamekana'])){
            $quote->setData('customer_lastnamekana',$data['account']['lastnamekana']);
            $subject->setData('customer_lastnamekana',$data['account']['lastnamekana']);
        }
        else
        if($quote->getData('customer_lastnamekana')){
            $subject->setData('customer_lastnamekana',$quote->getData('customer_lastnamekana'));
        }

        if(isset($data['account']) && isset($data['account']['firstnamekana'])){
            $quote->setData('customer_firstnamekana',$data['account']['firstnamekana']);
            $subject->setData('customer_firstnamekana',$data['account']['firstnamekana']);
        }
        else
        if($quote->getData('customer_lastnamekana')){
            $subject->setData('customer_lastnamekana',$quote->getData('customer_lastnamekana'));
        }

        if(isset($data['delivery_date']) || isset($data['delivery_timeslot'])){
            foreach($quote->getAllItems() as $item){

                $this->setDeliveryInfoForQuoteItem($item, $data);
            }
        }

        if (
            isset($data['free_shipping_fee_wbs']) &&
            !$this->_salesAdminHelper->isFreeOfChargeOrder() &&
            $this->_quoteSession->getFreeShippingFlag()
        ) {
            $quote->setData('free_shipping_fee_wbs', $data['free_shipping_fee_wbs']);
        } else {
            $quote->unsetData('free_shipping_fee_wbs');
        }

        return $result;
    }

    /**
     * @param $item
     * @param $data
     * @return $this
     */
    protected function setDeliveryInfoForQuoteItem($item, $data){
        $deliveryType = $item->getDeliveryType();

        if($this->_salesAdminHelper->isMultipleShippingAddressCart()){
            $addressId = $item->getAddressId();
            if(isset($data['delivery_date'][$addressId][$deliveryType])){
                $item->setDeliveryDate($data['delivery_date'][$addressId][$deliveryType]);
            } elseif (in_array($deliveryType, ['cool','normal','direct_mail']) && isset($data['delivery_date'][$addressId]['CoolNormalDm'])) {
                $item->setDeliveryDate($data['delivery_date'][$addressId]['CoolNormalDm']);
            }
            if(isset($data['next_delivery_date'][$addressId][$deliveryType])){
                $item->setNextDeliveryDate($data['next_delivery_date'][$addressId][$deliveryType]);
            } elseif (in_array($deliveryType, ['cool','normal','direct_mail']) && isset($data['next_delivery_date'][$addressId]['CoolNormalDm'])) {
                $item->setNextDeliveryDate($data['next_delivery_date'][$addressId]['CoolNormalDm']);
            }
            if(isset($data['delivery_timeslot'][$addressId][$deliveryType])){
                $this->_setDeliveryTimeInfoForQuoteItem($item, $data['delivery_timeslot'][$addressId][$deliveryType]);
            } elseif (in_array($deliveryType, ['cool','normal','direct_mail']) && isset($data['delivery_timeslot'][$addressId]['CoolNormalDm'])) {
                $this->_setDeliveryTimeInfoForQuoteItem($item, $data['delivery_timeslot'][$addressId]['CoolNormalDm']);
            }
        }else{
            if(isset($data['delivery_date'])){
                foreach($data['delivery_date'] as $addressId    =>  $info){
                    if(isset($info[$deliveryType])){
                        $item->setDeliveryDate($data['delivery_date'][$addressId][$deliveryType]);
                    } elseif (in_array($deliveryType, ['cool','normal','direct_mail']) && isset($info['CoolNormalDm'])) {
                        $item->setDeliveryDate($data['delivery_date'][$addressId]['CoolNormalDm']);
                    }
                }
            }

            if(isset($data['next_delivery_date'])){
                foreach($data['next_delivery_date'] as $addressId    =>  $info){
                    if(isset($info[$deliveryType])){
                        $item->setNextDeliveryDate($data['next_delivery_date'][$addressId][$deliveryType]);
                    } elseif (in_array($deliveryType, ['cool','normal','direct_mail']) && isset($info['CoolNormalDm'])) {
                        $item->setNextDeliveryDate($data['next_delivery_date'][$addressId]['CoolNormalDm']);
                    }
                }
            }

            if(isset($data['delivery_timeslot'])){
                foreach($data['delivery_timeslot'] as $addressId    =>  $info){
                    if(isset($info[$deliveryType])){
                        $this->_setDeliveryTimeInfoForQuoteItem($item, $data['delivery_timeslot'][$addressId][$deliveryType]);
                    } elseif (in_array($deliveryType, ['cool','normal','direct_mail']) && isset($info['CoolNormalDm'])) {
                        $this->_setDeliveryTimeInfoForQuoteItem($item, $data['delivery_timeslot'][$addressId]['CoolNormalDm']);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _processNormalOrderData(){
        $quote = $this->_quoteSession->getQuote();

        foreach($quote->getAllItems() as $item){
            $product = $item->getProduct();
            $item->setBookingWbs($product->getBookingItemWbs());
            $item->setBookingAccount($product->getBookingItemAccount());
            $item->setBookingCenter($product->getBookingProfitCenter());
        }

        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    protected function _processReplacementOrderData($data){
        $quote = $this->_quoteSession->getQuote();

        $attributes = [
            'original_order_id',
            'siebel_enquiry_id',
            'replacement_reason'
        ];

        foreach($attributes as $attribute){
            if(isset($data[$attribute]))
                $quote->setData($attribute, $data[$attribute]);
        }

        $quote->setSubstitution(1);
        $quote->setFreeOfCharge(0);

        $this->_quoteSession->setOriginalOrderId($quote->getOriginalOrderId());

        foreach($quote->getAllItems() as $item){
            $product = $item->getProduct();
            $item->setFreeOfCharge(1);
            $item->setFocWbs($product->getBookingFreeWbs());
            $item->setBookingAccount($product->getBookingMachineMtAccount());
            $item->setBookingCenter($product->getBookingMachineMtCenter());
        }

        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    protected function _processFreeSampleOrderData($data){
        $quote = $this->_quoteSession->getQuote();
        $quote->setFreeOfCharge(1);

        if(isset($data['free_samples_wbs']))
            $quote->setFreeSamplesWbs($data['free_samples_wbs']);

        foreach($quote->getAllItems() as $item){
            $item->setFreeOfCharge(1);
            $item->setFocWbs($quote->getFreeSamplesWbs());
        }

        return $this;
    }

    /**
     * @param $item
     * @param $timeSlotId
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _setDeliveryTimeInfoForQuoteItem($item, $timeSlotId){
        $timeSlotModel = $this->_timeSlotHelper->_getTimeSlotFromCollectionById($timeSlotId);
        if(!is_null($timeSlotModel)){
            $item->setDeliveryTime($timeSlotModel->getSlotName());
            $item->setDeliveryTimeslotId($timeSlotModel->getId());
            $item->setDeliveryTimeslotFrom($timeSlotModel->getFrom());
            $item->setDeliveryTimeslotTo($timeSlotModel->getTo());
        }

        return $this;
    }

    /**
     * Validate and remove machine invalid after remove main item
     *
     * @param \Magento\Sales\Model\AdminOrder\Create $subject
     * @return mixed
     */
    public function afterUpdateQuoteItems(
        \Magento\Sales\Model\AdminOrder\Create $subject,
        \Riki\Sales\Model\AdminOrder\Create $result
    ) {
        $quote = $subject->getQuote();
        $courseId = $quote->getRikiCourseId();
        if (!$courseId) {
            return $result;
        }
        $course = $this->courseFactory->create()->load($courseId);
        if (!$course->getId()) {
            return $result;
        }
        if ($course->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
            $this->machineHelper->removeMachineInvalid($quote);
        }
        return $result;
    }
}
