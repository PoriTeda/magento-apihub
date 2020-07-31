<?php
namespace Riki\Sales\Helper;

use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Riki\Customer\Model\Address\AddressType;

class Address extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ADDRESS_MULTI_SHIPPING_TYPE = 'riki_ship';

    /**
     * DB connection.
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    protected $_orderAddressFactory;

    protected $_objectCopyService;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $_dataObjectHelper;

    protected $_orderFactory;

    /**
     * Filter builder
     *
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * Search criteria builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Address service
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressService;

    /**
     * @var $_orderAddressItemFactory \Riki\Checkout\Model\Order\Address\Item
     */
    protected $_orderAddressItemFactory;

    /**
     * @var \Riki\Checkout\Model\ResourceModel\Order\Address\Item
     */
    protected $_orderAddressItemResource;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Address\CollectionFactory
     */
    protected $_customerAddressCollectionFactory;

    /** @var \Riki\Sales\Model\OrderCutoffDate  */
    protected $_cutOffDateModel;

    protected $_customerAddresses;

    /** @var \Magento\Customer\Helper\Address  */
    protected $_customerAddressHelper;

    /** @var \Magento\Customer\Model\Address\Mapper  */
    protected $_addressMapper;

    /**
     * @var \Riki\Customer\Helper\Region $_rikiRegionHelper
     */
    protected $_rikiRegionHelper;

    /** @var OrderAddressRepositoryInterface  */
    protected $salesOrderAddressRepository;

    /** @var \Magento\Directory\Model\RegionFactory  */
    protected $regionFactory;

    /** @var \Riki\Customer\Helper\Data  */
    protected $rikiCustomerHelper;

    /** @var \Riki\Customer\Helper\Address  */
    protected $rikiCustomerAddressHelper;

    protected $_customerIdToDefaultShippingAddressId = [];

    protected $orderAddressesByOrder = [];

    protected $ordersShippingAddressMetaData = [];

    /**
     * Address constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\AddressFactory $addressFactory
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressService
     * @param \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $customerAddressCollectionFactory
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Riki\Checkout\Model\Order\Address\ItemFactory $orderAddressItemFactory
     * @param \Riki\Checkout\Model\ResourceModel\Order\Address\Item $orderAddressItemResource
     * @param \Riki\Sales\Model\OrderCutoffDate $cutoffDate
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Riki\Customer\Helper\Region $rikiRegionHelper
     * @param OrderAddressRepositoryInterface $orderAddressRepository
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Riki\Customer\Helper\Data $rikiCustomerHelper
     * @param \Riki\Customer\Helper\Address $rikiCustomerAddressHelper
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\AddressFactory $addressFactory,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Customer\Api\AddressRepositoryInterface $addressService,
        \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $customerAddressCollectionFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Riki\Checkout\Model\Order\Address\ItemFactory $orderAddressItemFactory,
        \Riki\Checkout\Model\ResourceModel\Order\Address\Item $orderAddressItemResource,
        \Riki\Sales\Model\OrderCutoffDate $cutoffDate,
        \Magento\Customer\Helper\Address $addressHelper,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Riki\Customer\Helper\Region $rikiRegionHelper,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Riki\Customer\Helper\Data $rikiCustomerHelper,
        \Riki\Customer\Helper\Address $rikiCustomerAddressHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        parent::__construct($context);
        $this->_connection = $resourceConnection->getConnection('sales');
        $this->_orderAddressFactory = $addressFactory;
        $this->_orderFactory = $orderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $criteriaBuilder;
        $this->addressService = $addressService;
        $this->_objectCopyService = $objectCopyService;
        $this->_dataObjectHelper = $dataObjectHelper;
        $this->_orderAddressItemFactory = $orderAddressItemFactory;
        $this->_orderAddressItemResource = $orderAddressItemResource;
        $this->_customerAddressCollectionFactory = $customerAddressCollectionFactory;
        $this->_cutOffDateModel = $cutoffDate;
        $this->_customerAddressHelper = $addressHelper;
        $this->_addressMapper = $addressMapper;
        $this->_rikiRegionHelper = $rikiRegionHelper;
        $this->salesOrderAddressRepository = $orderAddressRepository;
        $this->regionFactory = $regionFactory;
        $this->rikiCustomerHelper = $rikiCustomerHelper;
        $this->rikiCustomerAddressHelper = $rikiCustomerAddressHelper;
    }

    /**
     * @return OrderAddressRepositoryInterface
     */
    public function getSalesOrderAddressRepository()
    {
        return $this->salesOrderAddressRepository;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getOrderShippingAddressesByOrder(\Magento\Sales\Model\Order $order)
    {
        if(!isset($this->orderAddressesByOrder[$order->getId()])){

            $orderFilter = $this->filterBuilder
                ->setField('parent_id')
                ->setValue($order->getId())
                ->setConditionType('eq')
                ->create();

            $shippingAddressFilter = $this->filterBuilder
                ->setField('address_type')
                ->setValue([
                    \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_SHIPPING,
                    self::ADDRESS_MULTI_SHIPPING_TYPE
                ])
                ->setConditionType('IN')
                ->create();

            $this->searchCriteriaBuilder->addFilters([$orderFilter]);
            $this->searchCriteriaBuilder->addFilters([$shippingAddressFilter]);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $result = $this->salesOrderAddressRepository->getList($searchCriteria);

            $orderAddressesData = $result->getItems();

            $this->orderAddressesByOrder[$order->getId()] = $orderAddressesData;
        }

        return $this->orderAddressesByOrder[$order->getId()];
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getOrderAddressForEachOrderItem(\Magento\Sales\Model\Order $order)
    {
        $orderItemIdsToOrderAddresses = [];

        $orderAddresses = $this->getOrderShippingAddressesByOrder($order);

        foreach ($orderAddresses as $key => $orderAddress) {
            $origRegionCode = $this->regionFactory->create()->load($orderAddress->getRegionId())->getCode();
            $orderAddress->setRegionCode($origRegionCode);
        }

        $orderItemIds = $order->getItemsCollection()->getAllIds();

        $orderAddressIds = $this->getAddressIdsByOrderItemIds($orderItemIds);

        foreach ($orderItemIds as $orderItemId) {
            if (!isset($orderAddressIds[$orderItemId])) {
                /** @var \Magento\Sales\Model\Order\Item $orderItem */
                $orderItem = $order->getItemsCollection()->getItemById($orderItemId);

                if (($parentItemId = $orderItem->getParentItemId())
                    && isset($orderAddressIds[$orderItem->getParentItemId()])
                ) {
                    $orderAddressIds[$orderItemId] = $orderAddressIds[$parentItemId];
                } else {
                    $orderAddressIds[$orderItemId] = $order->getShippingAddressId();
                }
            }
        }

        foreach ($orderAddressIds as $orderItemId => $orderAddressId) {
            foreach ($orderAddresses as $orderAddress) {
                if ($orderAddressId == $orderAddress->getEntityId()) {
                    $orderItemIdsToOrderAddresses[$orderItemId] = $orderAddress;
                }
            }
        }

        return $orderItemIdsToOrderAddresses;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function groupOrderItemsByOrderAddress(\Magento\Sales\Model\Order $order) {

        $result = [];

        $orderItemsToOrderAddresses = $this->getOrderAddressForEachOrderItem($order);

        /**
         * @var  $orderItemId
         * @var \Magento\Sales\Api\Data\OrderAddressInterface $orderAddress
         */
        foreach ($orderItemsToOrderAddresses as $orderItemId    =>  $orderAddress) {

            $orderAddressId = $orderAddress->getEntityId();

            if (!isset($result[$orderAddressId])) {
                $result[$orderAddressId] = [
                    'address'   =>  $orderAddress,
                    'item_ids'  =>  []
                ];
            }

            $result[$orderAddressId]['item_ids'][] = $orderItemId;
        }

        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Sales\Api\Data\OrderAddressInterface|null
     */
    public function getShippingAddressDataByOrder(\Magento\Sales\Model\Order $order){

        if (!isset($this->ordersShippingAddressMetaData[$order->getId()])) {

            $shippingAddress = $order->getShippingAddress();

            if (!$shippingAddress || !$shippingAddress->getId()) {
                $shippingAddressId = $order->getShippingAddressId();

                try{
                    $shippingAddress = $this->salesOrderAddressRepository->get($shippingAddressId);
                }catch (\Exception $e){
                    $this->_logger->critical($e);
                }
            }

            $this->ordersShippingAddressMetaData[$order->getId()] = $shippingAddress;
        }

        return $this->ordersShippingAddressMetaData[$order->getId()];
    }

    /**
     * @param \Magento\Sales\Model\Order\Item|int $item
     * @return $this|bool
     */
    public function getOrderAddressByOrderItem($item){

        if($item instanceof \Magento\Sales\Model\Order\Item){
            $itemId = $item->getId();
        }else{
            $itemId = (int)$item;
        }

        $select = $this->_connection->select()->from(
            'order_address_item',
            ['order_address_id']
        )->where(
            'order_address_item.order_item_id=?',
            $itemId
        );

        $addressId = $this->_connection->fetchOne($select);

        if($addressId){
            return $this->_orderAddressFactory->create()->load($addressId);
        }

        return false;
    }

    /**
     * @param $ids
     * @return array
     */
    public function getAddressIdsByOrderItemIds($ids){

        $select = $this->_connection->select()->from(
            'order_address_item',
            ['order_item_id', 'order_address_id']
        )->where(
            'order_address_item.order_item_id IN(?)',
            $ids
        );

        return $this->_connection->fetchPairs($select);
    }

    /**
     * @param $id
     * @return string
     */
    public function getCustomerAddressIdByOrderItemId($id){
        $select = $this->_connection->select()->from(
            'sales_order_address',
            ['customer_address_id']
        )->join(
            'order_address_item',
            'sales_order_address.entity_id=order_address_item.order_address_id'
        )->where(
            'order_address_item.order_item_id = ?',
            $id
        );

        return $this->_connection->fetchOne($select);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getOrderAddressToCustomerAddressesByOrder(\Magento\Sales\Model\Order $order){
        $itemIds = [];

        foreach($order->getAllItems() as $item){
            $itemIds[] = $item->getId();
        }

        $select = $this->_connection->select()->from(
            'sales_order_address',
            ['entity_id', 'customer_address_id']
        )->join(
            'order_address_item',
            'sales_order_address.entity_id=order_address_item.order_address_id'
        )->where(
            'order_address_item.order_item_id IN(?)',
            $itemIds
        );

        return $this->_connection->fetchPairs($select);
    }

    /**
     * @param $ids
     * @return array
     */
    public function getAddressIdsByOrderItemIdsForEdit($ids){
        $data = $this->getAddressIdsByOrderItemIds($ids);

        $result = [];
        if(count($data)){
            $result = $data;
        }else{
            foreach($ids as $id){
                $result[$id] = 0;
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderAddressInterface|\Magento\Sales\Model\Order\Address $address
     * @return bool
     */
    public function isValidUpdateDataShippingAddress($address){

        if($address->getAddressType() != \Magento\Sales\Model\Order\Address::TYPE_SHIPPING)
            return true;

        $order = $address->getOrder();

        $paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();

        switch($paymentMethodCode){
            case \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE :
                $valid = $this->compareAddresses($address, $order->getBillingAddress());
                break;
            default:
                $valid = true;
        }

        return $valid;
    }

    /**
     * @param \Magento\Sales\Model\Order|int $order
     * @return bool
     */
    public function canEditAddress($order){
        if(!$order instanceof \Magento\Sales\Model\Order){
            $order = $this->_orderFactory->create()->load((int)$order);
            if(!$order->getId())
                return false;
        }
        //if($order->hasShipments() || $order->getIsMultipleShipping())
        if($order->hasShipments())
            return false;
        return true;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderAddressInterface|\Magento\Sales\Model\Order\Address $address1
     * @param \Magento\Sales\Api\Data\OrderAddressInterface|\Magento\Sales\Model\Order\Address $address2
     * @return bool
     */
    public function compareAddresses($address1, $address2){

        $fields = [
            'region_id',
            'fax',
            'region',
            'postcode',
            'lastname',
            'street',
            'city',
            'email',
            'telephone',
            'country_id',
            'firstname',
            'address_type',
            'middlename',
            'company',
            'riki_nickname',
            'firstnamekana',
            'lastnamekana',
            'apartment'
        ];

        foreach($fields as $field){
            if($address1->getData($field) != $address2->getData($field))
                return false;
        }

        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $data
     * @param null $updatedBy
     * @return $this
     * @throws \Exception
     */
    public function updateOrderShippingAddresses(\Magento\Sales\Model\Order $order, $data, $updatedBy = null){

        $connection = $order->getResource()->getConnection();

        try {

            $connection->beginTransaction();

            $validAddressIds = [];
            $validAddresses = [];
            $addressCollection = $this->getAddressObjectListByCustomerId($order->getCustomerId());

            $orderItems = $order->getAllVisibleItems();

            $validItems = [];
            $validItemIds = [];

            foreach($orderItems as $item){
                $validItems[$item->getId()] = $item;
                $validItemIds[] = $item->getId();
            }

            foreach($addressCollection as $address){
                $validAddressIds[] = $address->getId();
                $validAddresses[$address->getId()] = $address;
            }

            $updateAddressOrderItems = [];

            foreach($data as $itemId    =>  $addressIdItems){

                if(!in_array($itemId, $validItemIds))
                    throw new \Magento\Framework\Exception\LocalizedException(__('The request data is invalid'));

                foreach($addressIdItems as $addressId){
                    if(in_array($addressId, $validAddressIds)){
                        if(!isset($updateAddressOrderItems[$addressId]))
                            $updateAddressOrderItems[$addressId] = [];

                        $updateAddressOrderItems[$addressId][] = $itemId;
                    }else{
                        throw new \Magento\Framework\Exception\LocalizedException(__('Please select valid addresses'));
                    }
                }
            }

            $this->_orderAddressItemResource->deleteItemByOrderItemsId(array_keys($data));

            $oldAddressCollection = $order->getAddresses();

            $needToDeletedAddressIds = [];

            foreach($updateAddressOrderItems as $addressId  =>  $itemIds){
                try{
                    // create new order address
                    $orderAddress = $this->convertCustomerAddressToOrderAddress(
                        $validAddresses[$addressId],
                        [
                            'email'    =>  $order->getCustomerEmail(),
                            'riki_type_address' =>  \Riki\Customer\Model\Address\AddressType::HOME,
                            'address_type'  =>  \Magento\Sales\Model\Order\Address::TYPE_SHIPPING
                        ]
                    );
                    $orderAddress->setOrder($order);
                    $orderAddress->setCustomerAddressId($addressId);
                    $orderAddress->save();

                    foreach($itemIds as $itemId){
                        // create new order address order item
                        $orderAddressItem = $this->_orderAddressItemFactory->create();
                        $orderAddressItem->setQty(1);
                        $orderAddressItem->importOrderItem($validItems[$itemId]);
                        $orderAddressItem->setAddress($orderAddress);
                        $orderAddressItem->save();

                        $validItems[$itemId]->setAddressId($addressId);
                    }

                    $needToDeletedAddressIds[] = $addressId;
                }catch (\Exception $e){
                    $this->_logger->critical($e);
                }
            }

            // clear old order shipping address
            if(count($needToDeletedAddressIds)){

                $unableDeletedAddressesId = $this->getUnableDeletedAddressesIdByOrder($order);

                /** @var \Magento\Sales\Model\Order\Address $address */
                foreach($oldAddressCollection as $address){
                    if(
                        in_array($address->getId(), $needToDeletedAddressIds) &&
                        !in_array($address->getId(), $unableDeletedAddressesId) &&
                        $address->getId() != $order->getShippigAddressId()
                    ){
                        try{
                            $address->delete();
                        }catch (\Exception $e){
                            $this->_logger->critical($e);
                        }
                    }
                }
            }

            if($this->needToUpdateDeliveryInfoAfterChangeShippingAddress($order)){
                try{
                    //clear order item delivery info
                    foreach($orderItems as $item){
                        $item->setDeliveryDate(null);
                        $item->setDeliveryNextDeliveryDate(null);
                        $item->setDeliveryTime(null);
                        $item->setDeliveryTimeslotId(null);
                        $item->setDeliveryTimeslotFrom(null);
                        $item->setDeliveryTimeslotTo(null);
                        $item->save();
                    }

                    $this->_cutOffDateModel->calculateCutoffDate($order);
                }catch (\Exception $e){
                    $this->_logger->error(__('Update shipping address for order #%1 has get error: %2', $order->getIncrementId(), $e->getMessage()));
                }
            }

            if ($updatedBy) {
                $order->addStatusHistoryComment(__('Delivery information was updated by %1', $updatedBy));
                $order->save();
            }

            $connection->commit();

        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getUnableDeletedAddressesIdByOrder(\Magento\Sales\Model\Order $order){
        $result = [];

        if($order->getShippigAddressId())
            $result[] = $order->getShippigAddressId();

        return $result;
    }

    /**
     * @return bool
     */
    public function needToUpdateDeliveryInfoAfterChangeShippingAddress(\Magento\Sales\Model\Order $order){
        return true;
    }

    /**
     * @param \Magento\Customer\Model\Address $address
     * @param array $data
     * @return \Magento\Sales\Model\Order\Address
     */
    public function convertCustomerAddressToOrderAddress(\Magento\Customer\Model\Address $address, $data = []){
        $orderAddress = $this->_orderAddressFactory->create();

        $orderAddressData = $this->_objectCopyService->getDataFromFieldset(
            'sales_convert_quote_address',
            'to_order_address',
            $address
        );

        $this->_dataObjectHelper->populateWithArray(
            $orderAddress,
            array_merge($orderAddressData, $data),
            '\Magento\Sales\Api\Data\OrderAddressInterface'
        );

        $orderAddress->setFirstnamekana($address->getFirstnamekana());
        $orderAddress->setLastnamekana($address->getLastnamekana());
        $orderAddress->setRikiNickname($address->getRikiNickname());
        $orderAddress->setRikiTypeAddress($address->getRikiTypeAddress());
        $orderAddress->setApartment($address->getApartment());
        return $orderAddress;
    }

    /**
     * @param $customerId
     * @return $this
     */
    public function getAddressObjectListByCustomerId($customerId){
        return $this->_customerAddressCollectionFactory->create()->addFieldToSelect('*')
            ->setCustomerFilter([$customerId]);
    }

    /**
     * @param $customerId
     * @return \Magento\Customer\Api\Data\AddressInterface[]
     */
    public function getAddressListByCustomerId($customerId){

        if(!isset($this->_customerAddresses[$customerId])){
            $filter = $this->filterBuilder
                ->setField('parent_id')
                ->setValue($customerId)
                ->setConditionType('eq')
                ->create();
            $this->searchCriteriaBuilder->addFilters([$filter]);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $result = $this->addressService->getList($searchCriteria);
            $this->_customerAddresses[$customerId] = $result->getItems();
        }

        return $this->_customerAddresses[$customerId];
    }

    /**
     * @param $customer
     * @return mixed
     */
    public function getDefaultShippingAddress($customer){

        $customerId = $customer->getId();

        if(!isset($this->_customerIdToDefaultShippingAddressId[$customerId])){

            $defaultShippingAddressId = $customer->getDefaultShipping();

            if(!$defaultShippingAddressId){
                /** @var \Magento\Customer\Model\ResourceModel\Address\Collection $addressCollection */
                $addressCollection = $this->getAddressObjectListByCustomerId($customerId);
                $addressCollection->setPageSize(1);

                $defaultShippingAddressId = $addressCollection->getFirstItem()->getId();
            }

            $this->_customerIdToDefaultShippingAddressId[$customerId] = $defaultShippingAddressId;

        }

        return $this->_customerIdToDefaultShippingAddressId[$customerId];
    }

    /**
     * @param $addressId
     * @param $customerId
     * @return bool
     */
    public function isValidCustomerAddress($addressId, $customerId){
        $validAddresses = $this->getAddressListByCustomerId($customerId);

        foreach($validAddresses as $address){
            if($addressId == $address->getId())
                return true;
        }

        return false;
    }

    /**
     * @return \Riki\Sales\Model\OrderCutoffDate
     */
    public function getCutOffDateModel(){
        return $this->_cutOffDateModel;
    }

    /**
     * @param $orderAddressId
     * @return array
     */
    public function getOrderItemIdByOrderAddressId($orderAddressId){
        $select = $this->_connection->select()->from(
            'order_address_item',
            ['order_item_id']
        )->join(
            'sales_order_address',
            'sales_order_address.entity_id=order_address_item.order_address_id'
        )->where(
            'sales_order_address.entity_id = ?',
            $orderAddressId
        );

        return $this->_connection->fetchAll($select);
    }

    /**
     * @param $customerAddressId
     * @return array
     */
    public function getOrderAddressIdsByCustomerAddressId($customerAddressId){
        $select = $this->_connection->select()->from(
            'sales_order_address',
            ['entity_id']
        )->where(
            'sales_order_address.customer_address_id = ?',
            $customerAddressId
        );

        return $this->_connection->fetchCol($select);
    }

    /**
     * Represent customer address in 'online' format.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    public function getAddressAsOneLineString(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        return $this->formatCustomerAddressToString($address, 'oneline');
    }

    /**
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param string $format
     * @return string
     */
    public function formatCustomerAddressToString(\Magento\Customer\Api\Data\AddressInterface $address, $format = 'html'){

        return $this->rikiCustomerAddressHelper->formatCustomerAddressToString($address, $format);
    }

    /**
     * @param $id
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    public function getCustomerAddressDataById($id){

        try{
            return $this->addressService->getById($id);

        }catch (\Exception $e){
            $this->_logger->critical($e);
        }

        return null;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Customer\Api\Data\AddressInterface $customerAddress
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function changeShippingAddressForSingleOrder(\Magento\Sales\Model\Order $order, \Magento\Customer\Api\Data\AddressInterface $customerAddress){
        try{
            $shippingAddress = $order->getShippingAddress();
            $customerAddressId = $customerAddress->getId();
            if(
                $shippingAddress &&
                $shippingAddress->getCustomerAddressId() != $customerAddressId
            ){
                $customerAddressData = $this->_addressMapper->toFlatArray($customerAddress);
                if($customerAddressData['region_id']){
                    $customerAddressData['region'] = $this->_rikiRegionHelper->getJapanRegion($customerAddressData['region_id']);
                }
                $shippingAddress->addData($customerAddressData);
                $shippingAddress->setCustomerAddressId($customerAddressId);
                $shippingAddress->save();
                $this->_eventManager->dispatch(
                    'admin_sales_order_address_update',
                    [
                        'order_id' => $order->getId(),
                        'address_id'    =>  $shippingAddress->getId()
                    ]
                );
            }
        }catch (\Exception $e){
            $this->_logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(__('Process error, please try again.'));
        }
        return $this;
    }

    /**
     * @param $addressID
     * @return string
     */
    public function getNewShippingName($addressID)
    {
        $addressObject = $this->_orderAddressFactory->create()->load($addressID);
        if($addressObject)
        {
            return $addressObject->getLastname(). ' '.$addressObject->getFirstname();
        }
        return '';
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $customerAddressId
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initShippingAddressFromCustomerAddress(\Magento\Sales\Model\Order $order, $customerAddressId){
        if (!$this->isValidCustomerAddress($customerAddressId, $order->getCustomerId())) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Request data is invalid.'));
        }

        $customerAddress = $this->getCustomerAddressDataById($customerAddressId);

        if (!$customerAddress) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Request data is invalid.'));
        }

        if ($order->getPayment()->getMethod() == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD) {
            $addressTypeAttr = $customerAddress->getCustomAttribute('riki_type_address');
            if ($addressTypeAttr) {
                $addressType = $addressTypeAttr->getValue();
                $validType = [\Riki\Customer\Model\Address\AddressType::HOME];

                if ($this->rikiCustomerHelper->isAmbSalesCustomer($order->getCustomerId())) {
                    $validType[] = \Riki\Customer\Model\Address\AddressType::OFFICE;
                }

                if (!in_array($addressType, $validType)) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Please select a Home/Company address.'));
                }
            }
        }
        return $customerAddress;
    }
    /**
     * @param $customerId
     * @return mixed
     */
    public function getDefaultHomeQuoteAddressByCustomerId($customerId)
    {
        $homeAddressData = null;
        if (!isset($this->customerIdToAddressHome[$customerId])) {
            $homeAddressData = $this->rikiCustomerAddressHelper->getAddressListByCustomerId(
                $customerId,
                AddressType::HOME
            );
            return $homeAddressData;
        }
    }
}
