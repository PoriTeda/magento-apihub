<?php
namespace Riki\MachineApi\Model;

use Magento\TestFramework\Event\Magento;
use Riki\MachineApi\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Riki\DeliveryType\Model\Delitype as Dtype;

class PaymentInformationManagement implements PaymentInformationManagementInterface{

    /**
     * @var BillingAddressManagement
     */
    protected $billingAddressManagement;

    /**
     * @var PaymentMethodManagement
     */
    protected $paymentMethodManagement;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var PaymentDetailsFactory
     */
    protected $paymentDetailsFactory;

    /**
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $_quoteRepository;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /***
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $_orderCollection;

    /**
     * @var \Riki\MachineApi\Api\Data\OrderInterface
     */
    protected $_orderInterface;

    /**
     * @var \Riki\Customer\Model\ShoshaFactory
     */
    protected $shoshaFactory;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    //define value ambassador
    const AMB_TYPE = 1;

    protected $isAmbassador;
    /**
     * @var \Wyomind\AdvancedInventory\Model\Stock
     */
    protected $collectionStockWyomind;

    protected $paymentMethod;
    /**
     * @var \Zend_Validate_Date
     */
    protected $dateValidator;
    /**
     * @var \Riki\TimeSlots\Model\ResourceModel\TimeSlots\Collection
     */
    protected $timeSlotCollection;

    protected $timeSlotMachine;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Riki\MachineApi\Helper\Data
     */
    protected $machineHelper;

    public $quoteData;

    /**
     * @var \Riki\DeliveryType\Model\Config\DeliveryDateSelection
     */
    protected $deliveryDateSelectionConfig;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * PaymentInformationManagement constructor.
     * @param BillingAddressManagement $billingAddressManagement
     * @param PaymentMethodManagement $paymentMethodManagement
     * @param QuoteManagement $cartManagement
     * @param \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
     * @param \Magento\Sales\Model\OrderFactory $orderCollectionFactory
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Sales\Model\Order $orderCollection
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param \Riki\MachineApi\Api\Data\OrderInterface $orderInterface
     * @param \Riki\Customer\Model\ShoshaFactory $shoshaFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Wyomind\AdvancedInventory\Model\Stock $collectionStockWyomind
     * @param \Zend_Validate_Date $dateValidator
     * @param \Riki\TimeSlots\Model\ResourceModel\TimeSlots\Collection $timeSlotCollection
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\MachineApi\Helper\Data $machineHelper
     * @param \Riki\Catalog\Model\StockState $stockState
     * @param \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelectionConfig
     */
    public function __construct(
        \Riki\MachineApi\Model\BillingAddressManagement $billingAddressManagement,
        \Riki\MachineApi\Model\PaymentMethodManagement $paymentMethodManagement,
        \Riki\MachineApi\Model\QuoteManagement $cartManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Sales\Model\OrderFactory $orderCollectionFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Sales\Model\Order $orderCollection,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Riki\MachineApi\Api\Data\OrderInterface $orderInterface,
        \Riki\Customer\Model\ShoshaFactory $shoshaFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Wyomind\AdvancedInventory\Model\Stock  $collectionStockWyomind,
        \Zend_Validate_Date $dateValidator,
        \Riki\TimeSlots\Model\ResourceModel\TimeSlots\Collection $timeSlotCollection,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\MachineApi\Helper\Data $machineHelper,
        \Riki\Catalog\Model\StockState $stockState,
        \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelectionConfig
    ) {
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->cartManagement = $cartManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->_quoteRepository = $quoteRepository;
        $this->_orderCollection = $orderCollection;
        $this->_request = $request;
        $this->_orderInterface = $orderInterface;
        $this->shoshaFactory = $shoshaFactory;
        $this->customerFactory = $customerFactory;
        $this->collectionStockWyomind   = $collectionStockWyomind;
        $this->timeSlotCollection = $timeSlotCollection;
        $this->resourceConnection = $resourceConnection;
        $this->machineHelper = $machineHelper;
        $this->stockState = $stockState;
        $this->deliveryDateSelectionConfig = $deliveryDateSelectionConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        $mmData,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        try {

            //set param for machine api
            $this->_request->setParam('call_machine_api','call_machine_api');

            //load quote
            $this->getQuoteRepository($cartId);

            $this->paymentMethod = $paymentMethod->getMethod();
            $this->validateDataInput();

            //check exit mm_order_id
            $this->checkUnitMmorderID($mmData['mm_order_id']);

            //check cart exit
            $this->checkCartidExit($cartId);

            /**
             * Check COD if product only DM
             *
             */
            if ($this->paymentMethod == 'cashondelivery'){
                $this->checkDmOnly($cartId);
            }
            $this->validateQty($cartId,$mmData);

            $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);

            $cartManagement = $this->cartManagement;
            $cartManagement->quoteData = $this->quoteData;

            $orderID  = $cartManagement->placeOrder($cartId);
            $orderIncrementId = $this->saveOrderDetail($orderID,$mmData);

            $this->_orderInterface->setOrderId($orderIncrementId);
            return $this->_orderInterface;
        }
        catch(\Magento\Framework\Exception\CouldNotSaveException $exception ){
            throw $exception;
        }
    }

    public function checkCartidExit($cartId){
        $quote = $this->getQuoteRepository($cartId);
        if($quote){
            if($quote->getReservedOrderId() !=''){
                throw new StateException(
                    __('Sorry ! The cart is not exit.')
                );
            }
        }
        return null;
    }

    public function checkDmOnly($cartId)
    {
        $quote = $this->getQuoteRepository($cartId);
        $onlyDm = true;
        if ($quote) {
            foreach ($quote->getAllItems() as $item) {
                $Dtype = $item->getDeliveryType();
                if ($Dtype != Dtype::DM) {
                    return null;
                }
            }
            if ($onlyDm) {
                throw new LocalizedException(__("Please change payment method from COD to other Payment"));
            }
            return null;
        }
    }
    /**
     * {@inheritDoc}
     */
    public function savePaymentInformation(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $paymentMethodManagement = $this->paymentMethodManagement;
        $billing = $this->billingAddressManagement;
        $billing->quoteData = $this->quoteData;
        if (!$billingAddress) {
            $billing->assignAddressDataToQuote($cartId, $this->quoteData->getBillingAddress(), false);
        } else {
            $billing->assign($cartId, $billingAddress);
        }

        $paymentMethodManagement->quoteData = $billing->quoteData;

        $paymentMethodManagement->set($cartId,$paymentMethod);
        return true;
    }

    /**
     * Calculator commission
     *
     * @param $orderItem
     * @param $commissionPercent
     * @return float
     */
    public function calculateCommission($orderItem,$commissionPercent)
    {
        $rowTotal              = $orderItem->getData('row_total');
        $discountAmountExclTax = $orderItem->getDiscountAmountExclTax();
        $commissionAmount      = round(($rowTotal - $discountAmountExclTax) * ($commissionPercent / 100));

        return $commissionAmount;
    }

    /**
     * Set commission to order item
     *
     * @param $saleOrder
     * @param $businessCode
     * @return null
     */
    public function setCommissionToOrderItem($saleOrder,$businessCode)
    {
        if ($businessCode != null)
        {
            $shoSha = $this->shoshaFactory->create()->getCollection()
                ->addFieldToFilter('shosha_business_code',$businessCode)
                ->setPageSize(1)
                ->setCurPage(1);
            if($shoSha->getSize()>0)
            {
                $commissionPercent = $shoSha->getFirstItem()->getData('shosha_commission');
                if ($commissionPercent !=null && $commissionPercent > 0 )
                {
                    foreach ($saleOrder->getAllItems() as $orderItem )
                    {
                        $commissionAmount  = $this->calculateCommission($orderItem,$commissionPercent);
                        $orderItem->setCommissionAmount($commissionAmount);
                        //set distribution channel
                        if( $this->isAmbassador==self::AMB_TYPE )
                        {
                            $orderItem->setDistributionChannel('06');
                        } else {
                            $orderItem->setDistributionChannel(14);
                        }
                        $orderItem->save();
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get Customer
     *
     * @param $customerId
     * @return $this|null
     */
    public function getCustomer ($customerId)
    {
        $customer = $this->customerFactory->create()->load($customerId) ;
        if($customer)
        {
            return $customer;
        }
        return null;
    }

    /**
     * @param $orderId
     * @param $mmData
     * @return string
     */
    public function saveOrderDetail($orderId,$mmData){

        //update Reason code to order;
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderCollectionFactory->create()->load($orderId);
        if($order){
            //set value for fraud_score and fraud_status as defaut
            $order->setData('fraud_score',50);
            $order->setData('fraud_status',\Mirasvit\FraudCheck\Model\Score::STATUS_APPROVE);

            // Do not send email when order created for Machine maintenance
            $order->setEmailSent(true);
            $order->setSendEmail(true);

            //chane request 6170
            $order->setOrderChannel('machine_maintenance');
            $order->setReplacementReason(__('Machine Maintenance Exchange'));
            $order->setCreatedBy(__('Machine maintenance'));

            $this->paymentMethod = $order->getPayment()->getMethod();
            if ($this->paymentMethod == 'cashondelivery')
            {
                //order normal
                $order->setSubstitution(0);
                $order->setFreeOfCharge(0);

                //ticket 8904
                $order->setChargeType(\Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_NORMAL);
            } else {
                //Free of charge - Replacement
                $order->setSubstitution(1);
                $order->setFreeOfCharge(0);

                //ticket 8904
                $order->setChargeType(\Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_REPLACEMENT);
            }

            $customer = $this->getCustomer($order->getCustomerId());
            if($customer !=null)
            {
                //When an order is placed from Shosha customers (b2b_flag is true)
                //additional information should be stored in the order
                $b2bFlag = $customer->getData('b2b_flag') && $customer->getData('shosha_business_code');
                if ($b2bFlag)
                {
                    $this->isAmbassador = $customer->getAmbType();

                    //set business code
                    $businessCode  = $customer->getData('shosha_business_code');
                    $order->setShoshaBusinessCode($businessCode);
                }
            }

            if(isset($mmData['original_order'])){
                $order->setOriginalOrderId($mmData['original_order']);
            }
            if(isset($mmData['siebel_enquiry_id'])){
                $order->setSiebelEnquiryId($mmData['siebel_enquiry_id']);
            }
            if(isset($mmData['mm_order_id'])){
                $order->setMmOrderId($mmData['mm_order_id']);
            }
            if(isset($mmData['substitution'])){
                $order->setSubstitution($mmData['substitution']);
            }
            if(isset($mmData['mm_packing'])){
                $order->setMmPacking($mmData['mm_packing']);
            }
            if(isset($mmData['mm_cushioning'])){
                $order->setMmCushioning($mmData['mm_cushioning']);
            }
            if(isset($mmData['mm_broken_sku'])){
                $order->setMmBrokenSku($mmData['mm_broken_sku']);
            }
            if(isset($mmData['mm_broken_reason_code'])){
                $order->setMmBrokenReasonCode($mmData['mm_broken_reason_code']);
            }
            if(isset($mmData['mm_repair_company_name'])){
                $order->setMmRepairCompanyName($mmData['mm_repair_company_name']);
            }
            if(isset($mmData['mm_repair_company_postal_code'])){
                $order->setMmRepairCompanyPostalCode($mmData['mm_repair_company_postal_code']);
            }
            if(isset($mmData['mm_repair_company_prefecture'])){
                $order->setMmRepairCompanyPrefecture($mmData['mm_repair_company_prefecture']);
            }
            if(isset($mmData['mm_repair_company_address'])){
                $order->setMmRepairCompanyAddress($mmData['mm_repair_company_address']);
            }
            if(isset($mmData['mm_repair_phone_number'])){
                $order->setMmRepairPhoneNumber($mmData['mm_repair_phone_number']);
            }
            if(isset($mmData['delivery_date_period'])){
                $deliveryDatePeriod = str_replace('/','-',trim($mmData['delivery_date_period']));
                $order->setDeliveryDatePeriod($deliveryDatePeriod);
            }
            if(isset($mmData['delivery_time'])){
                $order->setDeliveryTime($mmData['delivery_time']);
            }
            if(isset($mmData['order_date'])){
                $orderDate = str_replace('/','-',trim($mmData['order_date']));
                $order->setOrderDate($orderDate);
            }
            if(isset($mmData['prior_phone_call_flg'])){
                $order->setPriorPhoneCallFlg($mmData['prior_phone_call_flg']);
            }
            if(isset($mmData['caution_for_couterior'])){
                $order->setCautionForCouterior($mmData['caution_for_couterior']);
            }
            $order->save();
        }
        return $order->getIncrementId();
    }

    /**
     * validate data input
     *
     * @param $type
     * @param $arrDataValidate
     * @throws InputException
     */

    public function dataValidate($type,$arrDataValidate){
        $data = $this->_request->getRequestData();
        foreach ($arrDataValidate as $attribute){
            if(isset($data[$type])) {
                if(isset($data[$type][$attribute])){
                    if($data[$type][$attribute] ==null){
                        throw InputException::requiredField($attribute);
                    }
                }else{
                    throw InputException::requiredField($attribute);
                }
            }else{
                throw InputException::requiredField($attribute);
            }
        }
    }


    /**
     * validate data request api
     */
    public function validateDataInput(){
        $data = $this->_request->getRequestData();

        $arrpaymentMethod = array(
            'method'
        );
        $this->dataValidate('paymentMethod',$arrpaymentMethod);

        $arrDataValidate =array(
            "mm_order_id",
            "order_date"
        );

        $this->dateValidator = new \Zend_Validate_Date();

        //check validate Delivery date
        if (isset($data['mm_data']) && isset($data['mm_data']['delivery_date_period']) && $data['mm_data']['delivery_date_period'] !=null )
        {
            $deliveryDatePeriod = str_replace('/','-',trim($data['mm_data']['delivery_date_period']));
            if(!$this->dateValidator->isValid($deliveryDatePeriod)){
                throw new AlreadyExistsException(__("Format of delivery date is wrong. Please enter the date in the format yyyy-MM-dd or yyyy/MM/dd ",array(array("delivery_date_period"=>$data['mm_data']['delivery_date_period']))));
            }
        }

        //check validate time order time
        if (isset($data['mm_data']) && isset($data['mm_data']['order_date']) && $data['mm_data']['order_date'] !=null )
        {
            $orderDate= str_replace('/','-',trim($data['mm_data']['order_date']));
            if(!$this->dateValidator->isValid($orderDate)){
                throw new AlreadyExistsException(__("Format of order date is wrong. Please enter the date in the format yyyy-MM-dd or yyyy/MM/dd",array(array("order_date"=>$data['mm_data']['order_date']))));
            }
        }

        if (isset($data['mm_data']) && isset($data['mm_data']['substitution']) && $data['mm_data']['substitution'] !=null )
        {
            $substitution = (int)trim($data['mm_data']['substitution']);
            if ($substitution != 0 && $substitution !=1)
            {
                throw new AlreadyExistsException(__("Format of substitution is wrong. Please enter numeric value of 0 to 1",array(array("substitution"=>$data['mm_data']['substitution']))));
            }
        }

        if (isset($data['mm_data']) && isset($data['mm_data']['delivery_time']) && $data['mm_data']['delivery_time'] !=null )
        {
            $validator = function($stringCompare):int {
                $ordCal = 0;
                $totalStringCompare = strlen($stringCompare);
                for( $i = 0 ; $i < $totalStringCompare ; $i++ ){
                    $ordCal += ord($stringCompare[$i]);
                }
                return $ordCal;
            };
            $deliveryTime = $data['mm_data']['delivery_time'];
            if( $validator($deliveryTime)  != $validator(intval($deliveryTime)) ){
                throw new AlreadyExistsException(__("Format of delivery time is wrong. Please enter the number",array(array("delivery_time"=>$data['mm_data']['delivery_time']))));
            }

            //check time slot
            $connection  = $this->resourceConnection->getConnection();
            $sql  = $connection->select()
                ->from([$connection->getTableName('riki_timeslots')])
                ->where("appointed_time_slot = (?) ",$deliveryTime);

            $timeSlot = $connection->fetchRow($sql);
            if (is_array($timeSlot)&& count ($timeSlot)>0){
                $this->timeSlotMachine =$timeSlot;
            }else{
                throw new AlreadyExistsException(__("Delivery time doesn't exit",array(array("delivery_time"=>$data['mm_data']['delivery_time']))));
            }
        }else if (!isset($data['mm_data']['delivery_time'])){
            throw new AlreadyExistsException(__("Missing delivery_time parameter"));
        }
        $this->dataValidate('mm_data',$arrDataValidate);
    }

    /**
     * Check exit Machine maintenance order id
     * @param $mmOrderId
     * @return bool
     * @throws AlreadyExistsException
     */
    public function checkUnitMmorderID($mmOrderId){
        $connection  = $this->resourceConnection->getConnection('sales');
        $sql  = $connection->select()
            ->from([$connection->getTableName('sales_order')])
            ->where("mm_order_id = (?) ",$mmOrderId);

        $mmData = $connection->fetchOne($sql);
        if($mmData){
            throw new AlreadyExistsException(__("Machine maintenance order id already exist",array(array("mm_order_id"=>$mmOrderId))));
        }
        return true;
    }

    /**
     * Get quote item by cart id
     *
     * @param $cartId
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     */
    public function getQuoteRepository($cartId)
    {
        if (!$this->quoteData instanceof \Magento\Quote\Model\Quote )
        {
            $this->quoteData = $this->_quoteRepository->get($cartId);
        }
        return $this->quoteData;
    }


    /**
     * Get validate qty
     *
     * @param $cartId
     * @param $mmData
     * @return null
     * @throws LocalizedException
     */
    public function validateQty($cartId,$mmData)
    {
        $quote = $this->getQuoteRepository($cartId);

        $deliveryDatePeriod = null;
        if (isset($mmData['delivery_date_period'])
            && $mmData['delivery_date_period'] != null
            && !$this->deliveryDateSelectionConfig->getDisableChangeDeliveryDateConfig()
        ) {
            $deliveryDatePeriod = str_replace('/', '-', trim($mmData['delivery_date_period']));
        }

        if ($this->deliveryDateSelectionConfig->getDisableChangeDeliveryDateConfig()) {
            $quote->setData('allow_choose_delivery_date', 0);
        }

        $deliveryTime        = null;
        $deliveryTimeFrom    = null;
        $deliveryTimeTo      = null;
        $deliveryTimeSlotId  = null;

        if (isset($mmData['delivery_time'])
            && $mmData['delivery_time'] != null
            && !$this->deliveryDateSelectionConfig->getDisableChangeDeliveryDateConfig()
        ) {
            /**
             * @var \Riki\TimeSlots\Model\ResourceModel\TimeSlots\Collection $timeSlotMachine
             */
            $timeSlotMachine = $this->timeSlotMachine;
            if ( $timeSlotMachine !=null ){
                $deliveryTime        = isset($timeSlotMachine['slot_name']) ? $timeSlotMachine['slot_name'] : null;
                $deliveryTimeFrom    = isset($timeSlotMachine['from']) ? $timeSlotMachine['from'] : null;
                $deliveryTimeTo      = isset($timeSlotMachine['to']) ? $timeSlotMachine['to'] : null;
                $deliveryTimeSlotId  = isset($timeSlotMachine['id']) ? $timeSlotMachine['id'] : null;
            }
        }

        $paymentMethod = $this->paymentMethod;

        /* @var \Magento\Quote\Model\Quote\Item $item */
        foreach($quote->getAllItems() as $item )
        {
            $canAssigned = $this->stockState->canAssigned(
                $item->getProduct(),
                $item->getQty(),
                [$this->machineHelper->getMachineDefaultPlace()]
            );

            /*no back order, out of stock*/
            if (!$canAssigned) {
                throw new LocalizedException(__('We don\'t have as many quantity as you requested'));
            }

            $product = $item->getProduct();
            if ($paymentMethod == 'cashondelivery')
            {
                //order item normal
                $item->setBookingWbs($product->getData('booking_item_wbs'));
                $item->setBookingAccount($product->getData('booking_item_account'));
                $item->setBookingCenter($product->getData('booking_profit_center'));
                $item->setFreeOfCharge(0);
                $item->setFocWbs($product->getData('booking_item_wbs'));
            } else {
                //Free of charge - Replacement
                $item->setBookingWbs($product->getData('booking_free_wbs'));
                $item->setBookingAccount($product->getData('booking_machine_mt_account'));
                $item->setBookingCenter($product->getData('booking_machine_mt_center'));
                $item->setFreeOfCharge(1);
                $item->setFocWbs($product->getData('booking_free_wbs'));
            }

            //ticket 7229
            if ($deliveryDatePeriod!=null && $item->getDeliveryDate() ==null){
                $item->setDeliveryDate($deliveryDatePeriod);
            }

            if ($deliveryTime !=null){
                $item->setDeliveryTime($deliveryTime);
            }

            if($deliveryTimeFrom !=null){
                $item->setDeliveryTimeslotFrom($deliveryTimeFrom);
            }

            if($deliveryTimeTo !=null){
                $item->setDeliveryTimeslotTo($deliveryTimeTo);
            }

            if($deliveryTimeSlotId !=null){
                $item->setDeliveryTimeslotId($deliveryTimeSlotId);
            }

            /* collect price before submit order */
            $originalPrice = $product->getPrice();
            $item->setBaseOriginalPrice($originalPrice);


            $item->save();
        }

        return null;
    }
}
