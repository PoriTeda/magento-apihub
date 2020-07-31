<?php

namespace Riki\Subscription\Model\Emulator\AutomaticallyShipment;

use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Sales\Model\ResourceModel\Sales\Grid\OrderStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class CreateShipment extends \Riki\AutomaticallyShipment\Model\CreateShipment
{
    /**
     * @var \Riki\Subscription\Model\Emulator\Order\ItemRepository
     */
    protected $emulatorOrderItemRepository;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $subscriptionConnection;

    public function __construct(
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Magento\Framework\Registry $registry,
        \Wyomind\AdvancedInventory\Model\AssignationFactory $modelAssignationFactory,
        \Magento\Sales\Model\Order\Item $modelItem,
        \Riki\ShipmentExporter\Logger\LoggerShipCreator $logger,
        \Riki\ShipmentExporter\Helper\Data $shipmentExporterDataHelper,
        \Riki\Customer\Helper\Data $rikiCustHelper,
        \Riki\AutomaticallyShipment\Helper\Data $sapIntegrationHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemFactory,
        \Riki\Checkout\Model\ResourceModel\Order\Address\Item\CollectionFactory $multiAddress,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\Shipment\Helper\ShipmentHistory $shipmentHistory,
        OrderItemRepositoryInterface $orderItemRepository,
        \Riki\ShippingProvider\Helper\Data $shippingProviderHelper,
        \Riki\Shipment\Helper\Data $shipmentData,
        \Riki\Subscription\Model\Emulator\Order\ShipmentLoader $emulatorShipmentLoader,
        \Riki\Subscription\Model\Emulator\Order\AddressRepository $emulatorOrderAddressRepository,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Address\Item\CollectionFactory $collectionAddressItemFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\Subscription\Model\Emulator\Order\AddressFactory $emulatorOrderAddressFactory,
        \Riki\Subscription\Model\Emulator\Order\ItemRepository $emulatorOrderItemRepository,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\SapIntegration\Model\ShipmentSapExportedFactory $shipmentSapExportedFactory,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
    ) {
        parent::__construct(
            $shipmentLoader,
            $dbTransaction,
            $pointOfSaleFactory,
            $registry,
            $modelAssignationFactory,
            $modelItem,
            $logger,
            $shipmentExporterDataHelper,
            $rikiCustHelper,
            $sapIntegrationHelper,
            $productFactory,
            $orderItemFactory,
            $multiAddress,
            $dateTime,
            $timezone,
            $shipmentHistory,
            $orderItemRepository,
            $shippingProviderHelper,
            $shipmentData,
            $profileRepository,
            $shipmentSapExportedFactory,
            $subscriptionPageHelper
        );
        $this->shipmentLoader = $emulatorShipmentLoader;
        $this->emulatorOrderAddressRepository = $emulatorOrderAddressRepository;
        $this->collectionAddressItemFactory = $collectionAddressItemFactory;
        $this->countryFactory = $countryFactory;
        $this->_connection = $resourceConnection->getConnection('sales');
        $this->_orderAddressFactory = $emulatorOrderAddressFactory;
        $this->emulatorOrderItemRepository = $emulatorOrderItemRepository;
        $this->subscriptionConnection = $resourceConnection->getConnection('subscription');
    }

    /**
     * Create Shipment for Order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     * @throws \Exception
     */
    public function createShipment(\Magento\Sales\Model\Order $order, $cronType = '')
    {
        /*cannot create shipment if this order cannot ship*/
        if (!$order->canShip()) {
            return false;
        }
        /*get order assignation info*/
        $assignation = $this->getOrderAssignation($order);
        if (!$assignation) {
            return false;
        }

        $assignedItems = $assignation['items'];

        $originDate = $this->timeZone->formatDateTime($this->dateTime->gmtDate(), 2);
        $needDate = $this->dateTime->gmtDate('Y-m-d H:i:s', $originDate);
        $addressIds = $this->getMultiAddressItems($order);

        $isAmbSales = $this->rikiCustHelper->isAmbSalesCustomer($order->getData("customer_id"));
        $listDeliveryTypeGroupByItemRaw = $this->splitOrderItemByDeliveryType($order, $addressIds);

        $posIds = explode(",", $assignation['place_ids']);
        //Create shipment for warehouse , split item by delivery type
        $paymentMethod = $order->getPayment()->getMethod();

        $shoppingPointAmount = (float)$order->getUsedPointAmount();
        $baseShoppingPointAmount = (float)$order->getBaseUsedPointAmount();

        $shipmentsData = [];
        $maxTotalShipmentIndex = 0;
        $maxTotalShipment = 0;
        $maxTotalShipmentDeliveryIndex = [];
        $maxTotalShipmentDelivery = [];
        $shipmentIndex = 0;

        $shipmentsDataToSort = [];
        $shipmentsTotal = [];

        $shippingFees = $this->shippingProviderHelper->parseShippingFeeByAddressDeliveryType($order);
        $orderAddressToCustomerAddress = $this->getOrderAddressToCustomerAddressesByOrder($order);
        /**
         * cvs_collection_date is a value come from shipment importer 1507
         */
        if (!empty($order->getData('cvs_collection_date'))) {
            $shipmentCollectionDate = $order->getData('cvs_collection_date');
        } else {
            $shipmentCollectionDate = false;
        }

        foreach ($listDeliveryTypeGroupByItemRaw as $addressKey => $addressGroupItems) {
            foreach ($addressGroupItems as $deliveryType => $orderItemIds) {
                foreach ($posIds as $posId) {
                    $shipmentTotalInfo = $this->calculateTotalInfo($order, $orderItemIds, $assignedItems, $posId);

                    //make data for shipment create
                    $arrItems = $shipmentTotalInfo['item_qty'];

                    $totalShipment = $shipmentTotalInfo['total'];
                    $baseTotalShipment = $shipmentTotalInfo['base_total'];
                    $discountShipment = $shipmentTotalInfo['discount'];
                    $baseDiscountShipment = $shipmentTotalInfo['base_discount'];
                    $giftWrappingAmount = $shipmentTotalInfo['gw_total'];
                    $baseGiftWrappingAmount = $shipmentTotalInfo['base_gw_total'];
                    $giftWrappingTaxAmount = $shipmentTotalInfo['gw_tax'];
                    $baseGiftWrappingTaxAmount = $shipmentTotalInfo['base_gw_tax'];
                    $taxAmount = $shipmentTotalInfo['tax'];
                    $qtyAssigned = $shipmentTotalInfo['qty'];

                    //make data for shipment create
                    if (!$arrItems) {
                        continue;
                    }

                    // sort shipment by total
                    $rowTotal = $totalShipment - $discountShipment;

                    if ($order->getIsMultipleShipping()) {
                        $addressId = isset($orderAddressToCustomerAddress[$addressKey]) ?
                            $orderAddressToCustomerAddress[$addressKey] : 0;
                    } else {
                        $addressId = 0;
                    }

                    $deliveryKey = $deliveryType;

                    if (in_array($deliveryKey, [
                        \Riki\DeliveryType\Model\Delitype::COOL,
                        \Riki\DeliveryType\Model\Delitype::NORMAl,
                        \Riki\DeliveryType\Model\Delitype::DM
                    ])) {
                        $deliveryKey = \Riki\DeliveryType\Model\Delitype::COOL_NORMAL_DM;
                    }

                    $shipmentKey = $deliveryKey . '-' . $addressId;

                    if (!isset($maxTotalShipmentDelivery[$shipmentKey]) ||
                        $rowTotal > $maxTotalShipmentDelivery[$shipmentKey]
                    ) {
                        $maxTotalShipmentDeliveryIndex[$shipmentKey] = $shipmentIndex;
                        $maxTotalShipmentDelivery[$shipmentKey] = $rowTotal;
                    }

                    if ($rowTotal >= $maxTotalShipment) {
                        $maxTotalShipment = $rowTotal;
                        $maxTotalShipmentIndex = $shipmentIndex;
                    }

                    $shipmentsDataToSort[] = [
                        'index' =>  $shipmentIndex,
                        'total' =>  $rowTotal
                    ];

                    $shipmentsTotal[] = $rowTotal;

                    $shippingFeeByDelivery = $this->preparedShipmentShippingFee(
                        $order,
                        $shippingFees,
                        $shipmentIndex,
                        $addressId,
                        $deliveryKey
                    );
                    /////////
                    $shipmentsData[$shipmentIndex] = [
                        'qty_assigned' => $qtyAssigned,
                        'items' =>   $arrItems,
                        'post_id'   =>  $posId,
                        'address_key'   =>  $addressKey,
                        'delivery_type'   =>  strtolower($deliveryType),
                        'total'   =>  $totalShipment,
                        'base_total'   =>  $baseTotalShipment,
                        'discount'   =>  $discountShipment,
                        'base_discount'   =>  $baseDiscountShipment,
                        'shipping_fee'   =>  $shippingFeeByDelivery,
                        'base_shipping_fee'   =>  $shippingFeeByDelivery,
                        'payment_fee'   =>  $order->getFee(),
                        'base_payment_fee'   =>  $order->getBaseFee(),
                        'gw_price'   =>  $giftWrappingAmount,
                        'gw_base_price'   =>  $baseGiftWrappingAmount,
                        'gw_tax_amount'   =>  $giftWrappingTaxAmount,
                        'gw_base_tax_amount'   =>  $baseGiftWrappingTaxAmount,
                        'tax_amount'    =>  $taxAmount,
                        'subscription_profile_id' => $order->getData('subscription_profile_id'),
                        'shipping_address_id' => $addressKey,
                        'shipping_address_name' => $this->getNewShippingName($addressKey),
                        'shipment_collection_date' => $shipmentCollectionDate,
                        'stock_point_delivery_bucket_id' => $order->getData('stock_point_delivery_bucket_id')
                    ];

                    $shipmentIndex++;
                }
            }
        }

        array_multisort($shipmentsTotal, SORT_ASC, $shipmentsDataToSort);
        foreach ($shipmentsDataToSort as $shipmentDataToSort) {
            if ($shoppingPointAmount > 0 && $baseShoppingPointAmount > 0) {
                $shipmentItemData = $shipmentsData[$shipmentDataToSort['index']];

                $rowFinalTotalWithoutPoint = $shipmentItemData['total']
                    - $shipmentItemData['discount']
                    + $shipmentItemData['gw_price']
                    + $shipmentItemData['gw_tax_amount'];

                if (array_search($shipmentDataToSort['index'], $maxTotalShipmentDeliveryIndex) !== false) {
                    $rowFinalTotalWithoutPoint += $shipmentItemData['shipping_fee'];
                }

                if ($shipmentDataToSort['index'] == $maxTotalShipmentIndex) {
                    $rowFinalTotalWithoutPoint += $shipmentItemData['payment_fee'];
                }

                $pointAmount = min($shoppingPointAmount, $rowFinalTotalWithoutPoint);

                $baseRowFinalTotalWithoutPoint = $shipmentItemData['base_total']
                    - $shipmentItemData['base_discount']
                    + $shipmentItemData['gw_base_price']
                    + $shipmentItemData['gw_base_tax_amount'];

                if (array_search($shipmentDataToSort['index'], $maxTotalShipmentDeliveryIndex) !== false) {
                    $baseRowFinalTotalWithoutPoint += $shipmentItemData['base_shipping_fee'];
                }

                if ($shipmentDataToSort['index'] == $maxTotalShipmentIndex) {
                    $baseRowFinalTotalWithoutPoint += $shipmentItemData['base_payment_fee'];
                }

                $basePointAmount = min($baseShoppingPointAmount, $baseRowFinalTotalWithoutPoint);

                $shipmentsData[$shipmentDataToSort['index']]['shopping_point_amount'] = $pointAmount;
                $shipmentsData[$shipmentDataToSort['index']]['base_shopping_point_amount'] = $basePointAmount;

                $shoppingPointAmount -= $pointAmount;
                $baseShoppingPointAmount -= $basePointAmount;
            } else {
                break;
            }
        }

        $aShipmentData = [];
        foreach ($shipmentsData as $index => $shipmentData) {
            if ((int)($shipmentData['qty_assigned']) > 0) {
                $aShipmentData[] = $this->_createSingleShipment(
                    $order->getId(),
                    $shipmentData,
                    $paymentMethod,
                    $isAmbSales,
                    in_array($index, $maxTotalShipmentDeliveryIndex),
                    $index == $maxTotalShipmentIndex
                );
            }
        }

        //mark as shipment created
        try {
            //change status shipment for Order
            $order->setShipmentStatus(
                ShipmentStatus::SHIPMENT_STATUS_CREATED
            );
            $order->setData('shipment_status', ShipmentStatus::SHIPMENT_STATUS_CREATED);
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $order->setStatus(OrderStatus::STATUS_ORDER_IN_PROCESSING);
            $order->setIsNotified(false);
            $order->addStatusHistoryComment(
                __('Shipments created'),
                false
            );
            $order->setShipmentCreated(1)->save();
        } catch (\Exception $e) {
            $this->logger->info(($e->getMessage().$cronType));
        }

        return $aShipmentData;
    }

    /**
     * @param $orderId
     * @param $data
     * @param $paymentMethod
     * @param $isAmbSales
     * @param bool|false $isMaxTotalShipmentDelivery
     * @param bool|false $isMaxTotalShipment
     * @return $this
     * @throws \Exception
     */
    protected function _createSingleShipment(
        $orderId,
        $data,
        $paymentMethod,
        $isAmbSales,
        $isMaxTotalShipmentDelivery = false,
        $isMaxTotalShipment = false
    ) {
    
        try {
            //unset current shipment
            if ($this->registry->registry('current_shipment')) {
                $this->registry->unregister('current_shipment');
            }
            $this->shipmentLoader->setOrderId($orderId);
            $this->shipmentLoader->setShipmentId(null);
            $this->shipmentLoader->setShipment($data);
            $shipment = $this->shipmentLoader->load();
            if ($shipment instanceof \Magento\Sales\Model\Order\Shipment) {
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);
                $shipment->getOrder()->setIsNotified(false);

                $wareHouse = $this->pointOfSaleFactory->create()->load($data['post_id'])->getStoreCode();
                if ($wareHouse) {
                    $shipment->setWarehouse($wareHouse);
                }

                //set export date
                $itemRes = $this->getExportDate(array_keys($data['items']));
                $exportDate = $itemRes[0];
                $deliveryDate = $itemRes[1];
                $deliveryTime = $itemRes[2];

                if ($isMaxTotalShipment) {
                    $shipment->setBasePaymentFee($data['base_payment_fee']);
                    $shipment->setPaymentFee($data['payment_fee']);
                }

                if ($isMaxTotalShipmentDelivery) {
                    $shipment->setBaseShipmentFee($data['base_shipping_fee']);
                    $shipment->setShipmentFee($data['shipping_fee']);
                } else {
                    $shipment->setBaseShipmentFee(0);
                    $shipment->setShipmentFee(0);
                }

                $shipment->setTotalQty($data['qty_assigned']);
                $shipment->setAmountTotal($data['total']);
                $shipment->setBaseAmountTotal($data['base_total']);
                $shipment->setDiscountAmount($data['discount']);
                $shipment->setBaseDiscountAmount($data['base_discount']);
                $shipment->setGwPrice($data['gw_price']);
                $shipment->setGwBasePrice($data['gw_base_price']);
                $shipment->setGwTaxAmount($data['gw_tax_amount']);
                $shipment->setGwBaseTaxAmount($data['gw_base_tax_amount']);
                $shipment->setTaxAmount($data['tax_amount']);
                $shipment->setBaseTaxAmount($data['tax_amount']);

                $shipment->setDeliveryType($data['delivery_type']);

                //update grand total
                $grandTotal = $this->getFinalTotal($shipment);
                $shipment->setGrandTotal($grandTotal);

                if (isset($data['shopping_point_amount'])) {
                    $shipment->setShoppingPointAmount($data['shopping_point_amount']);
                }
                if (isset($data['base_shopping_point_amount'])) {
                    $shipment->setBaseShoppingPointAmount($data['base_shopping_point_amount']);
                }
                $address = $this->rebuildAddress($shipment);
                $shipment->setData('billing_address', $address[0]);
                $shipment->setData('shipping_address', $address[1]);
                $shipment->setData('shipping_name', $address[2]);
                $shipment->setData('shipping_name_kana', $address[3]);
                $shipment->setData('shipping_address_name', $data['shipping_address_name']);
                $shipment->setData('shipping_address_id', $data['shipping_address_id']);
                $shipment->setData('shipping_address_newid', $data['shipping_address_id']);
                $shipment->setExportDate($exportDate);
                $shipment->setDeliveryDate($deliveryDate);
                $shipment->setDeliveryTime($deliveryTime);
                $shipment->setData('shipment_status', ShipmentStatus::SHIPMENT_STATUS_CREATED);
                $shipment->setShipmentDate($this->dateTime->gmtDate('Y-m-d H:i:s'));
                $shipment->setData('stock_point_delivery_bucket_id', $data['stock_point_delivery_bucket_id']);
                /*set shipment data for cvspayment case*/
                if ($paymentMethod=="cvspayment") {
                    /*change payment status to payment collected and update collected date(payment_date)*/
                    $shipment->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);

                    /*System date when we receive the payment collected message*/
                    $shipment->setPaymentDate($this->dateTime->date('Y-m-d'));

                    $collectionDate = !empty($data['shipment_collection_date']) ?
                        $data['shipment_collection_date'] : false;

                    if (empty($collectionDate)) {
                        $collectionDate = $this->timeZone->date()->format('Y-m-d');
                    }

                    /*The actual Payment collection date - actual date from import file - 1507*/
                    $shipment->setCollectionDate($collectionDate);
                }

                /* check Zsim shipment */
                if ($this->shipmentExporterDataHelper->checkZSIM($shipment)) {
                    $shipment->setData('shipment_status', ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED);
                    $shipment->setShipZsim(1);
                }

                /* Set Custom data to shipment item */
                $this->saveCustomDataToItemShipment($shipment, $isAmbSales);
            }
            return $shipment;
        } catch (\Exception $e) {
            throw $e;
        }

        return null;
    }

    /**
     * Get order item by item id
     *
     * @param $itemId
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     */
    public function getOrderItemById($itemId)
    {
        return $this->emulatorOrderItemRepository->get($itemId);
    }

    /**
     * @param $shipment
     * @return array
     */
    public function rebuildAddress($shipment)
    {
        $items = $shipment->getAllItems();
        $orderIds = [];
        foreach ($items as $item) {
            $orderIds[] = $item->getOrderItemId();
        }
        $billingId = $shipment->getBillingAddressId();
        $shippingId = $shipment->getShippingAddressId();
        $billingObject = $this->emulatorOrderAddressRepository->get($billingId);
        $shippingCollection = $this->collectionAddressItemFactory->create()
            ->addFieldToFilter('order_item_id', ['in'=>$orderIds]);

        if ($shippingCollection->getSize()) {
            $shippingId = $shippingCollection->getFirstItem()->getOrderAddressId();
        }
        $shippingObject = $this->emulatorOrderAddressRepository->get($shippingId);
        $countryModel = $this->countryFactory->create();

        //combine new Billing Address
        $billingRegion = $billingObject->getRegion();
        $billingCity = $billingObject->getCity();
        $billingCountryID = $billingObject->getCountryId();
        $billingCountryName = $countryModel->loadByCode($billingCountryID)->getName();
        $billingStreet = implode(' ', $billingObject->getStreet());
        $billingPostCode = $billingObject->getPostcode();
        $billingAddress = $billingPostCode;
        $billingAddress .= ' '.$billingCountryName. ' '. $billingRegion. ' '.$billingCity;
        $billingAddress .= ' '.$billingStreet;

        //combine new Shipping Address
        $shippingRegion = $shippingObject->getRegion();
        $shippingCity = $billingObject->getCity();
        $shippingCountryID = $shippingObject->getCountryId();
        $shippingCountryName = $countryModel->loadByCode($shippingCountryID)->getName();
        $shippingStreet = implode(' ', $shippingObject->getStreet());
        $shippingPostCode = $shippingObject->getPostcode();
        $shippingAddress = $shippingPostCode;
        $shippingAddress .= ' '.$shippingCountryName. ' '. $shippingRegion. ' '.$shippingCity;
        $shippingAddress .= ' '.$shippingStreet;
        $shippingName = $shippingObject->getLastname(). ' '. $shippingObject->getFirstname();
        $shippingNameKana = $shippingObject->getLastnamekana(). ' '. $shippingObject->getFirstnamekana();

        return [$billingAddress,$shippingAddress,$shippingName,$shippingNameKana,$shippingObject->getEntityId()];
    }//end function

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getOrderAddressToCustomerAddressesByOrder(\Magento\Sales\Model\Order $order)
    {
        $itemIds = [];
        foreach ($order->getAllItems() as $item) {
            $itemIds[] = $item->getId();
        }
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            $select = $this->subscriptionConnection->select()->from(
                \Riki\Subscription\Model\Emulator\Config::getOrderAddressTmpTableName(),
                ['entity_id', 'customer_address_id']
            )->join(
                \Riki\Subscription\Model\Emulator\Config::getOrderAddressItemTmpTableName(),
                \Riki\Subscription\Model\Emulator\Config::getOrderAddressTmpTableName().
                '.entity_id='.\Riki\Subscription\Model\Emulator\Config::getOrderAddressItemTmpTableName().
                '.order_address_id'
            )->where(
                \Riki\Subscription\Model\Emulator\Config::getOrderAddressItemTmpTableName().'.order_item_id IN(?)',
                $itemIds
            );
        }
        return $this->subscriptionConnection->fetchPairs($select);
    }

    /**
     * @param $addressID
     * @return string
     */
    public function getNewShippingName($addressID)
    {

        $addressObject = $this->_orderAddressFactory->create()->load($addressID);
        if ($addressObject) {
            return $addressObject->getLastname(). ' '.$addressObject->getFirstname();
        }
        return '';
    }
}
