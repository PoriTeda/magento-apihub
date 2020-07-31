<?php

namespace Riki\Sales\Block\Adminhtml\Order\Create;

class Delivery extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{
    protected $_shippingAddress;

    protected $_deliveryTypeAdminHelper;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_courseFactory;

    /**
     * @var \Riki\Subscription\Model\Frequency\FrequencyFactory
     */
    protected $_frequencyFactory;

    protected $_rikiSalesHelper;

    protected $_rikiSalesAdminHelper;
    protected $_dateTime;
    protected $_subscriptionCourseModel;

    protected $_shippingProviderHelper;

    protected $_shippingMethodForm;

    protected $_addressGroupDeliveryData = null;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        \Riki\Sales\Helper\Data $rikiSalesHelper,
        \Riki\Sales\Helper\Admin $rikiSalesAdminHelper,
        \Riki\Checkout\Model\ShippingAddress $shippingAddress,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\Timezone $tzHelper,
        \Riki\SubscriptionCourse\Model\Course $subscriptionCourseModel,
        \Riki\ShippingProvider\Helper\Data $shippingProviderHelper,
        \Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Method\Form $shippingMethodForm,
        array $data = []
    ){
        parent::__construct(
            $context,
            $sessionQuote,
            $orderCreate,
            $priceCurrency,
            $data
        );
        $this->_subscriptionCourseModel = $subscriptionCourseModel;
        $this->_dateTime = $dateTime;
        $this->_shippingAddress = $shippingAddress;
        $this->_deliveryTypeAdminHelper = $deliveryTypeAdminHelper;
        $this->_courseFactory = $courseFactory;
        $this->_frequencyFactory = $frequencyFactory;
        $this->_rikiSalesHelper = $rikiSalesHelper;
        $this->_rikiSalesAdminHelper = $rikiSalesAdminHelper;
        $this->_shippingProviderHelper = $shippingProviderHelper;
        $this->_shippingMethodForm = $shippingMethodForm;
    }

    /**
     * @return array|mixed
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Json_Exception
     */
    public function getAddressGroups(){

        if(is_null($this->_addressGroupDeliveryData)){
            if($this->_rikiSalesAdminHelper->isMultipleShippingAddressCart()){
                if($quoteData = $this->_generateQuoteDataForMultipleAddress()){
                    $data = $this->_shippingAddress->processQuoteDataForDelivery($this->getQuote(), $quoteData);
                    $result = \Zend_Json::decode($data);
                    $result = $this->prepareDeliveryItemsQty($result['addressDdateInfo']);
                }else
                    $result = [];
            }else{
                $result = [
                    'address_id'    =>  0,
                    'addressData'   =>  $this->_prepareAddressDataFromShippingAddress(),
                    'ddate_info'    =>  []
                ];
                $quote = $this->getQuote();

                $calendarInfo = $this->_deliveryTypeAdminHelper->getDeliveryInfoForCurrentSingleAddressQuote($quote);

                foreach($quote->getAllVisibleItems() as $item){
                    $deliveryType = $item->getDeliveryType();

                    if(isset($calendarInfo[$deliveryType])){
                        if(isset($result['ddate_info'][$deliveryType])){
                            $result['ddate_info'][$deliveryType]['cartItems'][] = [
                                'name'  =>  $item->getName(),
                                'product_id'    =>  $item->getProductId(),
                                'quote_item_id' =>  $item->getId(),
                                'qty' =>  $item->getQty()
                            ];
                        }else{
                            $result['ddate_info'][$deliveryType] = $calendarInfo[$deliveryType];
                            $result['ddate_info'][$deliveryType]['name'] = $deliveryType;
                            $code = $deliveryType;
                            if ($deliveryType == \Riki\DeliveryType\Model\Delitype::COOL
                                || $deliveryType == \Riki\DeliveryType\Model\Delitype::NORMAl
                                || $deliveryType == \Riki\DeliveryType\Model\Delitype::DM) {
                                $code = \Riki\DeliveryType\Model\Delitype::COOL_NORMAL_DM;
                            }
                            $result['ddate_info'][$deliveryType]['code'] = $code;
                            $result['ddate_info'][$deliveryType]['cartItems'] = [
                                [
                                    'name'  =>  $item->getName(),
                                    'product_id'    =>  $item->getProductId(),
                                    'quote_item_id' =>  $item->getId(),
                                    'qty' =>  $item->getQty()
                                ]
                            ];
                        }
                    }
                }

                $result = count($result['ddate_info'])? [$result] : [];
            }

            $this->_addressGroupDeliveryData = $this->prepareGroupDeliveryAddressData($result);
        }

        return $this->_addressGroupDeliveryData;
    }

    /**
     * modify data before use
     *
     * @param $result
     * @return mixed
     */
    public function prepareGroupDeliveryAddressData($result){
        return $result;
    }

    /**
     * @return array|bool
     */
    protected function _generateQuoteDataForMultipleAddress(){
        $result = ['cart'   =>  []];

        $quote = $this->getQuote();
        foreach($quote->getAllVisibleItems() as $item){

            if($item->getAddressId()){
                $result['cart'][$item->getId()] = [
                    'address'   =>  $item->getAddressId()
                ];
            }else{
                return false;
            }
        }

        if(count($result['cart']))
            return $result;
        else
            return false;
    }

    /**
     * @return array
     */
    protected function _prepareAddressDataFromShippingAddress(){

        $address = $this->getQuote()->getShippingAddress();

        $apartment = null;
        if($address->getCustomAttribute('apartment') != null){
            $apartment = $address->getCustomAttribute('apartment')->getValue();
        }else{
            $apartment = '';
        }

        $rikiNickName = null;
        if($address->getCustomAttribute('riki_nickname') != null){
            $rikiNickName = $address->getCustomAttribute('riki_nickname')->getValue();
        }else{
            $rikiNickName = '';
        }

        $firstnamekana = null;
        if($address->getCustomAttribute('firstnamekana') != null){
            $firstnamekana = $address->getCustomAttribute('firstnamekana')->getValue();
        }else{
            $firstnamekana = '';
        }

        $lastnamekana = null;
        if($address->getCustomAttribute('lastnamekana') != null){
            $lastnamekana = $address->getCustomAttribute('lastnamekana')->getValue();
        }else{
            $lastnamekana = '';
        }

        return [
            'firstname' => $address->getFirstname(),
            'lastname'  => $address->getLastname(),
            'street'    => $address->getStreet(),
            'city'      => $address->getCity(),
            'region'    => $address->getRegionCode() ,
            'postcode'  => $address->getPostcode(),
            'countryId' => $address->getCountryId(),
            'telephone' => $address->getTelephone(),
            'riki_nickname' => $rikiNickName,
            'apartment'    => $apartment,
            'firstname_kana' => $firstnamekana,
            'lastname_kana'  => $lastnamekana
        ];
    }

    /**
     * @param array $deliveryData
     * @return array
     */
    protected function prepareDeliveryItemsQty(array $deliveryData){
        foreach($deliveryData as    $index  => $deliveryItemData){
            foreach($deliveryItemData['ddate_info'] as $ddIndex =>  $ddData){

                $newCartItems = [];

                foreach($ddData['cartItems'] as $ddItemData){

                    if(!isset($newCartItems[$ddItemData['sku']]))
                        $newCartItems[$ddItemData['sku']] = $ddItemData;
                    else
                        $newCartItems[$ddItemData['sku']]['qty'] += $ddItemData['qty'];
                }

                $deliveryData[$index]['ddate_info'][$ddIndex]['cartItems'] = $newCartItems;
            }
        }

        return $deliveryData;
    }

    /**
     * @return array
     */
    public function getCurrentDeliveryInfoForEditOrder(){

        $result = [];

        if ($this->_getSession()->getOrder()->getId()) {
            $oldOrder = $this->_getSession()->getOrder();

            $itemIds = [];
            foreach ($oldOrder->getAllItems() as $item) {
                $itemIds[] = $item->getId();
            }

            $itemIdsToAddressIds = $this->_rikiSalesAdminHelper->getAddressHelper()->getAddressIdsByOrderItemIdsForEdit($itemIds);

            foreach($oldOrder->getAllVisibleItems() as $item){

                $deliveryType = $item->getDeliveryType();

                $addressId = isset($itemIdsToAddressIds[$item->getId()])? $itemIdsToAddressIds[$item->getId()] : 0;

                if(!isset($result[$addressId][$deliveryType])){
                    $result[$addressId][$deliveryType] = [
                        'delivery_date' =>  $item->getDeliveryDate(),
                        'next_delivery_date' =>  $item->getNextDeliveryDate(),
                        'delivery_time' =>  $item->getDeliveryTime(),
                        'time_slot_id' =>  $item->getDeliveryTimeslotId()
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * get course info
     *
     * @return \Magento\Framework\Data\Object
     */
    public function getCourseInfo() {
        $objCourseInfo = new \Magento\Framework\DataObject();
        $objCourseInfo->setData([
            'intervalFrequency' => '',
            'unitFrequency' => '',
            'isAllowChangeNextDD' => '',
            'nextDeliveryDateCalculationOption' => '',
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
        $objCourseInfo->setData(
            'nextDeliveryDateCalculationOption',
            $objCourse->getData('next_delivery_date_calculation_option')
        );

        return $objCourseInfo;

    }

    /**
     * Get current date server
     *
     * @return string
     */
    public function getCurrentDateServer() {
        return $this->_rikiSalesHelper->getCurrentDateServer();
    }

    /**
     * @param $addressId
     * @param $deliveryType
     * @return bool
     */
    public function isFreeDeliveryTypeGroup($addressId, $deliveryType){
        return $this->_rikiSalesAdminHelper->isFreeDeliveryTypeGroup($addressId, $deliveryType);
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

    /**
     * @return bool
     */
    public function getDisplayStatus(){
        return true;
    }

    /**
     * Prepare html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if($this->getDisplayStatus()){
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * Get payment method of current quote
     *
     * @return null|string
     */
    public function getPaymentMethod()
    {
        $quote = $this->getQuote();
        if (!$quote) {
            return null;
        }
        $payment = $quote->getPayment();
        if (!$payment) {
            return null;
        }
        return $payment->getMethod();
    }

    /**
     * @return array
     */
    public function getDetailShippingFee(){
        $result = $this->_shippingProviderHelper->parseShippingFee($this->getQuote());

        if(!$this->_rikiSalesAdminHelper->isMultipleShippingAddressCart()){
            foreach($this->getAddressGroups() as $addressId =>  $addressData){
                $result[$addressId] = array_shift($result);
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getShippingRates(){
        return $this->_shippingMethodForm->getShippingRates();
    }

    /**
     * @return mixed
     */
    public function getIsRateRequest(){
        return $this->_shippingMethodForm->getIsRateRequest();
    }

    /**
     * @return float|string
     */
    public function getDefaultDeliveryFee(){
        return $this->_shippingProviderHelper->getShippingPrice(0, $this->getCreateOrderModel()->getShippingAddress(), $this->getQuote()->getStore());
    }

    /**
     * Check show delivery message
     * If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
     * AND interval_unit="month"
     * AND not Stock Point
     *
     * @param string $unitFrequency
     * @param string $nextDeliveryDateCalculationOption
     *
     * @return boolean
     */
    public function isShowDeliveryMessage($unitFrequency, $nextDeliveryDateCalculationOption)
    {
        if ($nextDeliveryDateCalculationOption
            == \Riki\SubscriptionCourse\Model\Course::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK
            && $unitFrequency == 'month'
        ) {
            return true;
        }

        return false;
    }
}
