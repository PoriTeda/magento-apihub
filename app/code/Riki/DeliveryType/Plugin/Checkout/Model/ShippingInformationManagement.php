<?php

namespace Riki\DeliveryType\Plugin\Checkout\Model;

use Riki\DeliveryType\Model\Delitype;

class ShippingInformationManagement
{

    /** @var \Magento\Quote\Api\CartRepositoryInterface  */
    protected $quoteRepository;

    /** @var \Riki\DeliveryType\Model\DeliveryDate  */
    protected $deliveryDate;

    /** @var \Riki\DeliveryType\Helper\Admin  */
    protected $deliveryAdminHelper;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime  */
    protected $date;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /** @var \Magento\Framework\App\Request\Http  */
    protected $_request;

    /**
     * ShippingInformationManagement constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Riki\DeliveryType\Helper\Admin $deliveryAdminHelper
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Riki\DeliveryType\Helper\Admin $deliveryAdminHelper
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_request = $request;
        $this->quoteRepository = $cartRepository;
        $this->date = $date;
        $this->deliveryDate = $deliveryAdminHelper->getDeliveryTypeModel();
        $this->deliveryAdminHelper = $deliveryAdminHelper;
    }

    /**
     * check request from web api
     *
     * @return bool
     */
    public function checkRequestWebApi(){
        $pathInfo =  $this->_request->getRequestUri();
        $pattern ='#V1/mm/carts/\d{1,}/shipping-information#';
        if(preg_match($pattern,$pathInfo,$match)){
            return true;
        }
        return false;
        
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        if(!$this->checkRequestWebApi()){
            $extAttributes = $addressInformation->getExtensionAttributes();
            if(!empty($extAttributes))
            {
                $deliveryDate = $extAttributes->getDeliveryDate();
                $this->_checkoutSession->setDeliveryDateTmp($deliveryDate);
            }
            $this->saveDeliveryData($cartId, $addressInformation);
        }
    }

    /**
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return $this
     */
    protected function saveDeliveryData(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    )
    {

        $extAttributes = $addressInformation->getExtensionAttributes();
        if(empty($extAttributes))
        {
            return $this;
        }

        try {
            $deliveryInfo = $extAttributes->getDeliveryDate();
            $deliveryInfo = \Zend_Json::decode($deliveryInfo);
        } catch (\Exception $e) {
            return $this;
        }

        $deliveryInfoByTypes = [];

        foreach ($deliveryInfo as $deliveryInfoByType) {
            if (isset($deliveryInfoByType['deliveryName'])) {
                $deliveryInfoByTypes[$deliveryInfoByType['deliveryName']] = $deliveryInfoByType;
            }
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        $destination = array(
            "country_code" => $addressInformation->getShippingAddress()->getCountryId(),
            "region_code"  => $addressInformation->getShippingAddress()->getRegionCode(),
            "postcode"     => $addressInformation->getShippingAddress()->getPostcode(),
        );

        $validDeliveryData = $this->deliveryAdminHelper->getDeliveryInfoForQuoteWithSpecificAddress($quote, $destination);

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {

            $deliveryType = $quoteItem->getData('delivery_type');

            $deliveryItemData = [
                'delivery_date' =>  null,
                'delivery_time' =>  null,
                'delivery_timeslot_id' =>  null,
                'delivery_timeslot_from' =>  null,
                'delivery_timeslot_to' =>  null
            ];

            if (isset($validDeliveryData[$deliveryType])) {

                if ($selectedDate = $this->prepareDeliveryDate($quote, $deliveryInfoByTypes, $deliveryType, $validDeliveryData)) {
                    $deliveryItemData['delivery_date'] = $selectedDate;
                }
            }

            if (
                isset($deliveryInfoByTypes[$deliveryType]) &&
                isset($validDeliveryData[$deliveryType])
            ) {

                $deliveryInfoByType = $deliveryInfoByTypes[$deliveryType];

                if (isset($deliveryInfoByType['deliveryTime']) && $deliveryInfoByType['deliveryTime']) {
                    $timeSlot = $this->deliveryDate->getTimeSlotInfo($deliveryInfoByType['deliveryTime']);
                    if($timeSlot){

                        $deliveryItemData['delivery_time']   =   $timeSlot->getData("slot_name");
                        $deliveryItemData['delivery_timeslot_id']   =   $timeSlot->getData("id");
                        $deliveryItemData['delivery_timeslot_from']   =   $timeSlot->getData("from");
                        $deliveryItemData['delivery_timeslot_to']   =   $timeSlot->getData("to");
                    }
                }

                if (count($deliveryItemData)) {
                    $quoteItem->addData($deliveryItemData);
                }
            }
        }

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $selectedData
     * @param $deliveryType
     * @param array $validDeliveryData
     * @return bool|string
     */
    protected function prepareDeliveryDate(\Magento\Quote\Model\Quote $quote, $selectedData, $deliveryType, array $validDeliveryData)
    {

        if ($invalidDatesNum = count($validDeliveryData[$deliveryType]['deliverydate'])) {
            $maxInvalidDate = $validDeliveryData[$deliveryType]['deliverydate'][$invalidDatesNum - 1];
            $minValidDate =  $this->date->date('Y-m-d', $this->date->timestamp($maxInvalidDate) + 24 * 60 * 60);

            if (
                isset($selectedData[$deliveryType]['deliveryDate']) &&
                $selectedData[$deliveryType]['deliveryDate']
            ) {
                $selectedDate = $selectedData[$deliveryType]['deliveryDate'];

                $selectedDate = $this->date->date('Y-m-d', $selectedDate);

                if ($this->date->timestamp($selectedDate) >= $this->date->timestamp($minValidDate)) {
                    return $selectedDate;
                }
            }

            if ($quote->getData('riki_course_id')) {
                return $minValidDate;
            }
        }

        return false;
    }
}