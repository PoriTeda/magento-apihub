<?php
namespace Riki\EmailMarketing\Helper;

use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Framework\App\Helper\Context;

class SplitDeliveryEmail extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Sales\Model\Order\AddressFactory
     */
    protected $_addressFactory;
    /**
     * @var AddressConfig
     */
    protected $_addressConfig;
    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $_addressHelper;

    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $_deliveryDate;
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;
    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $_rikiSalesHelper;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_courseFactory;
    /**
     * @var \Riki\Subscription\Model\Frequency\FrequencyFactory
     */
    protected $_frequencyFactory;
    /**
     * @var \Riki\ShippingProvider\Helper\Data
     */
    protected $_shippingProviderHelper;
    /**
     * @var \Riki\DeliveryType\Helper\Admin
     */
    protected $_deliveryTypeAdminHelper;

    protected $addressRepository;

    /**
     * @var array
     */
    protected $_addressIdToObject ;

    protected $_orderItemIdToAddressId;

    protected $_addressDeliveryData;

    protected $_order;


    /**
     * SplitDeliveryEmail constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Sales\Model\Order\AddressFactory $addressFactory,
        \Riki\Sales\Helper\Address $addressHelper,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $addressRepository,
        AddressConfig $addressConfig
    )
    {
        parent::__construct($context);
        $this->_addressFactory = $addressFactory;
        $this->_addressHelper = $addressHelper;
        $this->_deliveryDate = $deliveryDate;
        $this->_regionFactory = $regionFactory;
        $this->_deliveryTypeAdminHelper = $deliveryTypeAdminHelper;
        $this->addressRepository = $addressRepository;
        
    }

    /**
     * Reset data before
     *
     */
    public function initData(){
        $this->_addressDeliveryData = null;
        $this->_orderItemIdToAddressId = null;
        $this->_addressIdToObject = [];
    }

    /**
     * Get address info by id
     *
     * @param $addressId
     * @return \Magento\Sales\Api\Data\OrderAddressInterface
     */
    public function getShippingAddressById($addressId)
    {
        return $this->addressRepository->get($addressId);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $addressId
     * @return array
     */
    public function getSplitAddressInfo(\Magento\Sales\Model\Order $order,$addressId)
    {
        if ($addressId==0){
            //single address
            $shippingAddress = $order->getShippingAddress();
        }else{
            //multiple address
            $shippingAddress = $this->getShippingAddressById($addressId);
        }

        $dataAddress = [
            'lastName'    =>'',
            'firstName'   =>'',
            'phone'       =>'',
            'postCode'    =>'',
            'addressInfo' =>''
        ];

        if ($shippingAddress){
            $addressInfo = __($shippingAddress->getRegion()) . ' ' . $shippingAddress->getStreetLine(1) . ' ' . $shippingAddress->getData('apartment');
            $dataAddress = [
                'lastName'    =>$shippingAddress->getLastName(),
                'firstName'   =>$shippingAddress->getFirstName(),
                'phone'       =>$shippingAddress->getTelephone(),
                'postCode'    =>$shippingAddress->getPostcode(),
                'addressInfo' =>$addressInfo
            ];
        }

        return $dataAddress;
    }

    public function getOrder(){
        return $this->_order;
    }

    /**
     * Get address groups
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getAddressGroups(\Magento\Sales\Model\Order $order){
        $this->_order = $order;

        if(is_null($this->_addressDeliveryData)){
            $itemIdsToAddressIds = $this->getAddressIdsForEdit();
            $addressGroups = [];
            /** @var \Magento\Sales\Model\Order\Item $item */
            foreach($this->getOrder()->getAllItems() as $item){
                if(!$item->getPrizeId() && intval($item->getPriceInclTax()) > 0 ) // not free product
                {
                $deliveryType = $this->_deliveryTypeAdminHelper->prepareDeliveryType($item->getDeliveryType());
                $addressId = isset($itemIdsToAddressIds[$item->getId()])? $itemIdsToAddressIds[$item->getId()] : 0;
                if(!isset($addressGroups[$addressId])){
                    $addressGroups[$addressId] = [
                        'delivery'  =>  []
                    ];
                }

                if(!isset($addressGroups[$addressId]['delivery'][$deliveryType])){
                    $addressGroups[$addressId]['delivery'][$deliveryType] = [
                        'delivery_date'      => $item->getDeliveryDate(),
                        'next_delivery_date' => $item->getNextDeliveryDate(),
                        'delivery_time'      => $item->getDeliveryTime(),
                        'time_slot_id'       => $item->getDeliveryTimeslotId(),
                        'delivery_type'      => $deliveryType,
                        'delivery_type_name' => $deliveryType,
                        'delivery_type_list'    =>  [],
                        'items'              => [],
                        'item_ids'           => []
                    ];
                }

                $addressGroups[$addressId]['delivery'][$deliveryType]['item_ids'][] = $item->getId();
                $addressGroups[$addressId]['delivery'][$deliveryType]['item_ids_object'][] = $item;
                $addressGroups[$addressId]['delivery'][$deliveryType]['delivery_type_list'][] = $item->getDeliveryType();
                $sku = $item->getSku();


                    if(!isset($addressGroups[$addressId]['delivery'][$deliveryType]['items'][$sku]))
                        $addressGroups[$addressId]['delivery'][$deliveryType]['items'][$sku] = [
                            'sku'   =>  $item->getSku(),
                            'product_id'    =>  $item->getProductId(),
                            'name'  =>  $item->getName(),
                            'qty'   =>  0 + $item->getQtyOrdered()
                        ];
                    else
                        $addressGroups[$addressId]['delivery'][$deliveryType]['items'][$sku]['qty'] += $item->getQtyOrdered();
                }

            }

            $this->_addressDeliveryData = $addressGroups;
            $this->_addressDeliveryData = $this->_getLimitDeliveryDate($order);
            $this->_addressDeliveryData = $this->prepareGroupDeliveryAddressData($this->_addressDeliveryData);
        }


        return $this->_addressDeliveryData;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddressIdsForEdit(){
        if(is_null($this->_orderItemIdToAddressId)){
            $itemIds = [];
            foreach($this->getOrder()->getAllItems() as $item){
                $itemIds[] = $item->getId();
            }
            $this->_orderItemIdToAddressId =  $this->_addressHelper->getAddressIdsByOrderItemIdsForEdit($itemIds);
        }

        return $this->_orderItemIdToAddressId;
    }

    /**
     * @param $addressId
     * @return mixed
     */
    protected function _getAddressObjById($addressId){
        if(!isset($this->_addressIdToObject[$addressId])){
            if($addressId){ // multiple address
                $this->_addressIdToObject[$addressId] = $this->_addressFactory->create()->load($addressId);
            }else{ //single address
                $this->_addressIdToObject[$addressId] = $this->getOrder()->getShippingAddress();
            }
        }

        return $this->_addressIdToObject[$addressId];
    }

    /**
     * Get limit delivery date
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getLimitDeliveryDate(\Magento\Sales\Model\Order $order){

        $storeId = $this->getOrder()->getStoreId();

        $addressGroups = $this->getAddressGroups($order);

        foreach($addressGroups as $addressId    =>  $addressGroup){

            $addressObj = $this->_getAddressObjById($addressId);

            if($addressObj instanceof \Magento\Sales\Model\Order\Address && $addressObj->getId()){

                $region = $this->_regionFactory->create()->load($addressObj->getRegionId());

                $destination = array(
                    "country_code" => $addressObj->getCountryId(),
                    "region_code"  => $region instanceof \Magento\Directory\Model\Region? $region->getCode() : '',
                    "postcode"     => $addressObj->getPostcode(),
                );
            }else{
                $destination = array(
                    "country_code" => '',
                    "region_code"  => '',
                    "postcode"     => '',
                );
            }

            foreach($addressGroup['delivery'] as $deliveryType  =>  $deliveryTypeData){
                $addressGroups[$addressId]['delivery'][$deliveryType]['date_info'] = $this->getLimitDeliveryDateDataByOrderItems(
                    $destination,
                    $storeId,
                    $deliveryTypeData['item_ids'],
                    $deliveryType
                );
            }
        }

        return $addressGroups;
    }


    /**
     * @param $destination
     * @param $storeId
     * @param $orderItemIds
     * @param $deliveryType
     * @return array
     */
    protected function getLimitDeliveryDateDataByOrderItems($destination, $storeId, $orderItemIds, $deliveryType){
        $collectionData = $this->_deliveryDate->getAssignationByOrderItem($orderItemIds);

        //get assignation warehouse for some item same delivery type
        $assignationGroupByDeliveryType = $this->_deliveryDate->calculateWarehouseGroupByCollection(
            $destination, $collectionData, $storeId
        );

        $calendarData = $this->_deliveryTypeAdminHelper->getCalendarInfoByDeliveryTypeData($deliveryType, $assignationGroupByDeliveryType, $destination['region_code'], true);

        $calendarData['assignation'] = isset($assignationGroupByDeliveryType['items'])? $assignationGroupByDeliveryType['items'] : [];

        return $calendarData;
    }

    /**
     * modify data before use
     *
     * @param $result
     * @return mixed
     */
    public function prepareGroupDeliveryAddressData($result){
        foreach($result as $addressId    =>  $addressGroup){
            foreach($addressGroup['delivery'] as $deliveryType  =>  $deliveryTypeData){
                if($deliveryType == \Riki\DeliveryType\Model\Delitype::COOL_NORMAL_DM){
                    $result[$addressId]['delivery'][$deliveryType]['delivery_type_name'] = $this->_deliveryDate->getNameGroup($deliveryTypeData['delivery_type_list']);
                }
            }
        }
        return $result;
    }


}