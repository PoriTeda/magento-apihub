<?php
/**
 * Shipment Creator
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\AutomaticallyShipment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\AutomaticallyShipment\Model;

use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Sales\Model\ResourceModel\Sales\Grid\OrderStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Shipment\Helper\ShipmentHistory;
use Riki\Shipment\Helper\Data as ShipmentHelper;
use Riki\DeliveryType\Model\Delitype;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use \Riki\SubscriptionCourse\Model\Course\Type as SubCourseType;

/**
 * Class CreateShipment
 *
 * @category  RIKI
 * @package   Riki\AutomaticallyShipment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CreateShipment
{
    const PREPARING_FOR_SHIPPING = 'preparing_for_shipping';

    const UNIT_CASE_CS = 'CS';

    const FREE_PAYMENT = 'free';
    /**
     * @var \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader
     */
    protected $shipmentLoader;
    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $dbTransaction;

    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $pointOfSaleFactory;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var null|\Wyomind\AdvancedInventory\Model\AssignationFactory
     */
    protected $modelAssignationFactory = null;
    /**
     * @var \Magento\Sales\Model\Order\Item
     */
    protected $modelItem;
    /**
     * @var \Riki\Sales\Logger\LoggerSales
     */
    protected $logger;
    /**
     * @var \Riki\ShipmentExporter\Helper\Data
     */
    protected $shipmentExporterDataHelper;
    /**
     * @var \Riki\Customer\Helper\Data
     */
    protected $rikiCustHelper;
    /**
     * @var \Riki\SapIntegration\Helper\Data
     */
    protected $sapIntegrationHelper;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $orderItemFactory;
    /**
     * @var \Riki\Checkout\Model\ResourceModel\Order\Address\Item\Collection
     */
    protected $multiAddress;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var ShipmentHistory
     */
    protected $shipmentHistory;
    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;
    /**
     * @var ShipmentHelper
     */
    protected $shipmentHelper;
    /**
     * @var \Riki\ShippingProvider\Helper\Data
     */
    protected $shippingProviderHelper;

    protected $order;

    protected $subscriptionRepository;

    /**
     * @var \Riki\SapIntegration\Model\ShipmentSapExportedFactory
     */
    protected $shipmentSapExportedFactory;

    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subscriptionPageHelper;

    /**
     * CreateShipment constructor.
     * @param \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Wyomind\AdvancedInventory\Model\AssignationFactory $modelAssignationFactory
     * @param \Magento\Sales\Model\Order\Item $modelItem
     * @param \Riki\ShipmentExporter\Logger\LoggerShipCreator $logger
     * @param \Riki\ShipmentExporter\Helper\Data $shipmentExporterDataHelper
     * @param \Riki\Customer\Helper\Data $rikiCustHelper
     * @param \Riki\AutomaticallyShipment\Helper\Data $sapIntegrationHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemFactory
     * @param \Riki\Checkout\Model\ResourceModel\Order\Address\Item\CollectionFactory $multiAddress
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param ShipmentHistory $shipmentHistory
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param \Riki\ShippingProvider\Helper\Data $shippingProviderHelper
     * @param ShipmentHelper $data
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\SapIntegration\Model\ShipmentSapExportedFactory $shipmentSapExportedFactory
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     */
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
        ShipmentHistory $shipmentHistory,
        OrderItemRepositoryInterface $orderItemRepository,
        \Riki\ShippingProvider\Helper\Data $shippingProviderHelper,
        ShipmentHelper $data,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\SapIntegration\Model\ShipmentSapExportedFactory $shipmentSapExportedFactory,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
    ) {
        $this->shipmentLoader = $shipmentLoader;
        $this->dbTransaction = $dbTransaction;
        $this->pointOfSaleFactory = $pointOfSaleFactory;
        $this->registry = $registry;
        $this->modelAssignationFactory = $modelAssignationFactory;
        $this->modelItem = $modelItem;
        $this->logger = $logger;
        $this->shipmentExporterDataHelper = $shipmentExporterDataHelper;
        $this->rikiCustHelper = $rikiCustHelper;
        $this->sapIntegrationHelper = $sapIntegrationHelper;
        $this->productFactory = $productFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->multiAddress = $multiAddress;
        $this->dateTime = $dateTime;
        $this->timeZone = $timezone;
        $this->shipmentHistory = $shipmentHistory;
        $this->orderItemRepository = $orderItemRepository;
        $this->shipmentHelper = $data;
        $this->shippingProviderHelper = $shippingProviderHelper;
        $this->subscriptionRepository = $profileRepository;
        $this->shipmentSapExportedFactory = $shipmentSapExportedFactory;
        $this->subscriptionPageHelper = $subscriptionPageHelper;
    }

    /**
     * Get Assignation data
     *
     * @param $entityId
     * @return mixed
     */
    public function getAssignation($entityId)
    {
        $assignModel = $this->modelAssignationFactory->create();
        $assignation = $assignModel->run($entityId);
        return $assignation;
    }

    /**
     * Get exactly order assignation data to create shipment
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool|array
     */
    public function getOrderAssignation(\Magento\Sales\Model\Order $order)
    {
        try {
            if (!empty($order->getAssignation())) {
                /*get order assignation data*/
                $assignationNew= \Zend_Json::decode($order->getAssignation(), \Zend_Json::TYPE_ARRAY);

                /*get bundle item of this order*/
                $bundles = $this->getBundleItems($order);

                /*rebuild assignation again for bundle item*/
                $assignation = $this->reBuildAssignation($assignationNew, $bundles);

                /* make sure assignation data have place_ids( warehouse id) and items(items assign to warehouse) key */
                if (!empty($assignation['place_ids']) && !empty($assignation['items'])) {
                    return $assignation;
                }
            }
        } catch (\Exception $e) {
            /*current case: cannot decode assignation data*/
            $this->logger->critical($e->getMessage());
        }
        return false;
    }

    /**
     * Create Shipment for Order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $cronType
     *
     * @return bool|array
     * @throws \Exception
     */
    public function createShipment(\Magento\Sales\Model\Order $order, $cronType = '')
    {
        $orderNumber = $order->getIncrementId();
        /*cannot create shipment if this order cannot ship*/
        if (!$order->canShip() ||
            $order->getShipmentsCollection()->getSize()
        ) {
            $this->logger->info('Order : ' . $orderNumber . ' can not create shipment');
            return false;
        }

        $this->order = $order;

        // Prepare shipment data before save into DB
        $preparedShipmentsData = $this->preparedShipmentsData($order);

        $isAmbSales = $this->rikiCustHelper->isAmbSalesCustomer($order->getData('customer_id'));
        $hasShipment = false;
        $totalShipmentDeliveryFee = 0;
        if ($preparedShipmentsData) {
            try {
                $this->dbTransaction->beginTransaction();

                /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                foreach ($preparedShipmentsData as $shipment) {
                    $result = $this->saveCustomDataToItemShipment($shipment, $isAmbSales);

                    $totalShipmentDeliveryFee += $shipment->getShipmentFee();

                    if ($result) {
                        $hasShipment = true;
                    }
                }

                if ($totalShipmentDeliveryFee != $order->getShippingInclTax()) {
                    $this->logger->info(
                        'Order #' . $order->getIncrementId().
                        ' has incorrect delivery fee shipment. Shipping fee by address data: '.
                        $order->getData('shipping_fee_by_address').
                        '. Order shipping fee: ' .
                        $order->getShippingInclTax()
                    );
                }

                if ($hasShipment) {
                    /**
                     * do_not_change_order_status is a flag to hold change order status process after create shipment success
                     * set at shipment importer 1507
                     */
                    \Magento\Framework\App\ObjectManager::getInstance()->get("Nestle\Debugging\Helper\DebuggingHelper")
                        ->inClass($this)
                        ->addMessage("order number: " . $orderNumber)
                        ->addMessage("flag change order status: " . $order->getData('do_not_change_order_status'))
                        ->logBacktrace()
                        ->save("MGU-640");
                    if (empty($order->getData('do_not_change_order_status')) ||
                        $order->getData('do_not_change_order_status') != 1) {
                        $this->updateOrderCreateShipmentSuccess($order, $cronType);
                    }
                }
                $this->dbTransaction->commit();
            } catch (\Exception $e) {
                $this->dbTransaction->rollback();
                throw $e;
            }
        }

        return $hasShipment;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $shippingFees
     * @param $shipmentIndex
     * @param $addressId
     * @param $deliveryType
     * @return float|int|null
     */
    protected function preparedShipmentShippingFee(
        \Magento\Sales\Model\Order $order,
        $shippingFees,
        $shipmentIndex,
        $addressId,
        $deliveryType
    ) {

        if (isset($shippingFees[$addressId][$deliveryType]) && $order->getShippingInclTax() > 0) {
            return (float)$shippingFees[$addressId][$deliveryType];
        }

        if ($order->getIsMultipleShipping()) {
            if (count($shippingFees) <= 0) {
                if ($shipmentIndex == 0) {
                    return (float)$order->getShippingInclTax();
                }
            }

            return 0;
        }

        return (float)$order->getShippingInclTax();
    }

    /**
     * @param int $orderId
     * @param array $data
     * @param string $paymentMethod
     * @param bool $isMaxTotalShipmentDelivery
     * @param bool $isMaxTotalShipment
     * @return null|\Magento\Sales\Model\Order\Shipment
     */
    protected function createSingleShipment(
        $orderId,
        $data,
        $paymentMethod,
        $isMaxTotalShipmentDelivery = false,
        $isMaxTotalShipment = false
    ) {
        try {
            /* unset current shipment */
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

                $wareHouse = $this->pointOfSaleFactory->create()->load($data['post_id']);
                if ($wareHouse instanceof \Wyomind\PointOfSale\Model\PointOfSale) {
                    $shipment->setWarehouse($wareHouse->getStoreCode());
                }

                //set export date
                list($exportDate, $deliveryDate, $deliveryTime) = $this->getExportDate(array_keys($data['items']));

                if ($isMaxTotalShipment) {
                    $shipment->setBasePaymentFee($data['base_payment_fee']);
                    $shipment->setPaymentFee($data['payment_fee']);
                }

                if ($isMaxTotalShipmentDelivery) {
                    $shipment->setBaseShipmentFee($data['base_shipping_fee']);
                    $shipment->setShipmentFee($data['shipping_fee']);
                }
                $shipment = $this->fillShipmentData(
                    $shipment,
                    $data,
                    $paymentMethod,
                    $exportDate,
                    $deliveryDate,
                    $deliveryTime
                );
                /* Set Custom data to shipment item */
                return $shipment;
            }
        } catch (\Exception $e) {
            $this->logger->error('Create shipment cron - Create single shipment: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get final total
     * @param $shipment
     * @return mixed
     */
    protected function getFinalTotal($shipment)
    {
        return $shipment->getAmountTotal()
        + $shipment->getShipmentFee()
        + $shipment->getPaymentFee()
        + $shipment->getGwPrice()
        + $shipment->getGwTaxAmount()
        - $shipment->getShoppingPointAmount()
        - $shipment->getDiscountAmount();
    }
    /**
     * Get export date
     *
     * @param $orderItems
     * @return bool|string
     */
    public function getExportDate($orderItems)
    {
        $exportDate = date('Y-m-d');
        $deliveryDate = '';
        $deliveryTime = '';
        if ($orderItems) {
            $itemsCollection = $this->orderItemFactory->create();
            $itemsCollection->addFieldToFilter('item_id', ['in' => $orderItems]);
            if ($itemsCollection->getSize()) {
                foreach ($itemsCollection->getItems() as $item) {
                    $exportDate = $item->getExportDate();
                    if ($item->getDeliveryDate()) {
                        $deliveryDate = $item->getDeliveryDate();
                    }
                    if ($item->getDeliveryTime()) {
                        $deliveryTime = $item->getDeliveryTime();
                    }
                }
            }
        }
        return [$exportDate, $deliveryDate, $deliveryTime];
    }

    /**
     * Save custom data to shipment item and update WBS here.
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $isAmbSales
     * @return bool
     */
    public function saveCustomDataToItemShipment(
        \Magento\Sales\Model\Order\Shipment $shipment,
        $isAmbSales
    ) {
        try {
            $shipment->save();
            $this->generateSapExportedData($shipment);
            $this->changeShipmentItemData($shipment, $isAmbSales);
            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return false;
    }

    /**
     * Get shipment booking wbs
     *      from order item data
     *
     * @param $order
     * @return string
     */
    public function getShipmentBookingWbs($order)
    {
        $bookingWbs = [];

        /*get order item*/
        $orderItems = $order->getAllItems();

        foreach ($orderItems as $orderItem) {
            if (!empty($orderItem->getData('booking_wbs'))) {
                array_push($bookingWbs, $orderItem->getData('booking_wbs'));
            }
        }

        if (!empty($bookingWbs)) {
            return implode(",", $bookingWbs);
        }

        return '';
    }

    /**
     * Change shipment item data afte save shipment success
     *      distribution_channel
     *      sap_trans_id
     *
     * @param $shipment
     * @param $isAmbSales
     */
    public function changeShipmentItemData($shipment, $isAmbSales)
    {
        $shipmentItems = $shipment->getAllItems();
        $shipmentIncrementId = $shipment->getIncrementId();
        $i = 0;
        foreach ($shipmentItems as $item) {
            $i++;
            $salesOrgLabel = '';
            if (! $this->sapIntegrationHelper->isUse2017Settings()) {
                $salesOrgLabel = $item->getData("sales_organization");
            }
            $distributionChannel = $this->sapIntegrationHelper->getDistributionChannel($isAmbSales, $salesOrgLabel);
            // Set distribution channel
            $item->setData("distribution_channel", $distributionChannel);
            // Set Sap Trans Id
            if ($i < 10) {
                $sapTransId = $shipmentIncrementId . '0' . $i;
            } else {
                $sapTransId = $shipmentIncrementId . $i;
            }

            $item->setSapTransId($sapTransId);
            try {
                $item->save();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
    /**
     * Split Order Item
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $addressItemIds
     * @return array
     */
    public function splitOrderItemByDeliveryType($order, $addressItemIds = [])
    {
        $result = [];

        $coolNormalDmTypeItems = [];

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getParentItemId()) { // do not process bundle parent item
                continue;
            }

            $itemId = $orderItem->getId();

            $addressId = $addressItemIds[$itemId];
            $deliveryType = $orderItem->getDeliveryType();

            if (in_array($deliveryType, [Delitype::COOL, Delitype::NORMAl, Delitype::DM])) {
                if (!isset($coolNormalDmTypeItems[$addressId])) {
                    $coolNormalDmTypeItems[$addressId] = [];
                }
                $coolNormalDmTypeItems[$addressId][] = $deliveryType;

                $deliveryType = Delitype::COOL_NORMAL_DM;
            }
            if (!isset($result[$addressId])) {
                $result[$addressId] = [];
            }

            if (!isset($result[$addressId][$deliveryType])) {
                $result[$addressId][$deliveryType] = [];
            }

            $result[$addressId][$deliveryType][] = $itemId;
        }

        foreach ($result as $addressId => $addressGroupData) {
            if (isset($coolNormalDmTypeItems[$addressId])) {
                $result[$addressId][$this->groupDeliveryKey($coolNormalDmTypeItems[$addressId])]=
                    $result[$addressId][Delitype::COOL_NORMAL_DM];
                unset($result[$addressId][Delitype::COOL_NORMAL_DM]);
            }
        }

        return $result;
    }
    /**
     * @param $objProduct
     * @return array
     */
    protected function getMapSalesOrganization($objProduct)
    {
        $arrOption = $objProduct->getResource()
            ->getAttribute('sales_organization')
            ->getSource()->getAllOptions(false, true);

        if (empty($arrOption)) {
            return [];
        }

        $arrReturn = [];

        foreach ($arrOption as $i => $arr) {
            $arrReturn[$arr['value']] = $arr['label'];
        }

        return $arrReturn;
    }

    /**
     * Get Multi Address Items
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getMultiAddressItems(\Magento\Sales\Model\Order $order)
    {
        $_items = $order->getAllItems();
        $itemsIds = [];
        $addressIds = [];
        foreach ($_items as $_item) {
            $itemsIds[] = $_item->getId();
        }
        if ($itemsIds) {
            $addressCollection = $this->multiAddress->create();
            $addressCollection->addFieldToFilter('order_item_id', ['in'=> $itemsIds]);
            if ($addressCollection->getSize()) {
                foreach ($addressCollection as $_address) {
                    if ($_address->getOrderAddressId()) {
                        $addressIds[$_address->getOrderItemId()] = $_address->getOrderAddressId();
                    }
                }
            }
        }
        $itemAddressIds = [];
        foreach ($itemsIds as $_itemId) {
            if (array_key_exists($_itemId, $addressIds)) {
                $itemAddressIds[$_itemId] = $addressIds[$_itemId];
            } else {
                $itemAddressIds[$_itemId] = $order->getShippingAddress()->getId();
            }
        }
        return $itemAddressIds;
    }

    /**
     * @param $item
     * @return mixed
     */
    protected function validItem($item)
    {
        $arrayKeys =
            [
                'base_price',
                'price',
                'gw_price',
                'gw_base_price',
                'gw_tax_amount',
                'gw_base_tax_amount',
                'base_discount',
                'discount',
                'tax_amount',
            ];
        foreach ($arrayKeys as $key) {
            if (!array_key_exists($key, $item)) {
                $item[$key] = 0;
            }
        }
        return $item;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function getBundleItems(\Magento\Sales\Model\Order $order)
    {
        $items = $order->getAllItems();
        $bundles = [];
        $bundlesData = [];
        foreach ($items as $_item) {
            $itemId = $_item->getId();
            $parentItemId = $_item->getParentItemId();
            $productType = $_item->getProductType();
            if ($productType=="bundle") {
                $bundlesData[$_item->getId()] = $_item;
            }
            if ($productType=="simple" && $parentItemId) {
                /** @var \Magento\Sales\Model\Order\Item $bdata */
                $bdata = $bundlesData[$parentItemId];
                $bundles[$itemId] = [
                    'itemid' => $parentItemId,
                    'product_id' => $bdata->getProductId(),
                    'base_price' => $bdata->getBasePriceInclTax(),
                    'price' => $bdata->getPriceInclTax(),
                    'gw_price' => $bdata->getGwPrice(),
                    'gw_base_price' => $bdata->getData('gw_base_price'),
                    'gw_tax_amount' => $bdata->getGwTaxAmount(),
                    'gw_base_tax_amount' => $bdata->getData('gw_base_tax_amount'),
                    'base_discount' => $bdata->getData('base_discount_amount'),
                    'discount' => $bdata->getData('discount_amount'),
                    'tax_amount' => $bdata->getData('tax_amount'),
                    'qty_ordered'    =>  $bdata->getData('qty_ordered')
                ];
            }
        }

        return $bundles;
    }

    /**
     * @param $assignation
     * @param $bundles
     * @return array
     */
    protected function reBuildAssignation($assignation, $bundles)
    {
        if (!$bundles) {
            return $assignation;
        }
        $newassignation = [];
        if ($assignation['items']) {
            foreach ($assignation['items'] as $itemKey => $itemData) {
                if (array_key_exists($itemKey, $bundles)) {
                    $parentItemData = $bundles[$itemKey];
                    $itemData['product_id'] = $parentItemData['product_id'];
                    $itemData['base_price'] = $parentItemData['base_price'];
                    $itemData['price'] = $parentItemData['price'];
                    $itemData['gw_price'] = $parentItemData['gw_price'];
                    $itemData['gw_base_price'] = $parentItemData['gw_base_price'];
                    $itemData['gw_tax_amount'] = $parentItemData['gw_tax_amount'];
                    $itemData['gw_base_tax_amount'] = $parentItemData['gw_base_tax_amount'];
                    $itemData['base_discount'] = $parentItemData['base_discount'];
                    $itemData['discount'] = $parentItemData['discount'];

                    // Calculate tax amount for bundle item
                    // To fix bug: Tax amount of order data and shipping data is different
                    $itemData['tax_amount'] = $parentItemData['tax_amount'] / $parentItemData['qty_ordered'];

                    // use parent qty instead child qty
                    if (isset($parentItemData['qty_ordered'])) {
                        foreach ($itemData['pos'] as $posId => $posData) {
                            $itemData['pos'][$posId]['qty_assigned'] = $parentItemData['qty_ordered'];
                        }
                    }
                    $newassignation[$parentItemData['itemid']] = $itemData;
                } else {
                    $newassignation[$itemKey] = $itemData;
                }
            }
            $assignation['items']= $newassignation;
        }
        return $assignation;
    }

    /**
     * @param $arrDeliveryCode
     * @return string
     */
    public function groupDeliveryKey($arrDeliveryCode)
    {
        $hasCool = false;
        $hasNormal = false;
        $hasDm = false;

        foreach ($arrDeliveryCode as $code) {
            switch ($code) {
                case Delitype::COOL:
                    $hasCool = true;
                    break;
                case Delitype::NORMAl:
                    $hasNormal = true;
                    break;
                case Delitype::DM:
                    $hasDm = true;
                    break;
                default:
                    break;
            }
        }

        if ($hasCool) {
            return Delitype::COOL;
        }

        if ($hasNormal) {
            return Delitype::NORMAl;
        }

        if ($hasDm) {
            return Delitype::DM;
        }

        return '';
    }

    /**
     * @param $arrDeliveryCode
     * @return string
     */
    public function groupL2Key($arrDeliveryCode)
    {
        $a1 = [Delitype::COOL,Delitype::NORMAl];
        $a2 = [Delitype::COOL,Delitype::DM];
        $a3 = [Delitype::DM,Delitype::NORMAl];
        if (array_diff($a1, $arrDeliveryCode)) {
            return Delitype::COOL;
        }
        if (array_diff($a2, $arrDeliveryCode)) {
            return Delitype::COOL;
        }
        if (array_diff($a3, $arrDeliveryCode)) {
            return Delitype::NORMAl;
        }
    }

    /**
     * Update order after create shipment success
     *      Change order status to in processing
     *      set shipment status to created
     *
     * @param \Magento\Sales\Model\Order $order
     * @param $cronType
     */
    public function updateOrderCreateShipmentSuccess(\Magento\Sales\Model\Order $order, $cronType)
    {
        // Change order shipment status
        $order->setShipmentStatus($this->getDefaultShipmentStatus());

        /*change order shipment created to 1 , flag to check this order is created shipment*/
        $order->setShipmentCreated(1);

        /*order state*/
        $order->setState(
            \Magento\Sales\Model\Order::STATE_PROCESSING
        );

        /*change order status to in processing*/
        $order->setStatus(
            OrderStatus::STATUS_ORDER_IN_PROCESSING
        );

        /*add status history and do not push notification*/
        $order->setIsNotified(false);
        $order->addStatusHistoryComment(
            __('Shipments created by %1', $cronType),
            false
        );
        $order->setData(
            'published_message',
            \Riki\Shipment\Model\Order\ShipmentBuilder\Creator::SHIPMENT_CREATOR_HANDLED
        );
        try {
            $order->save();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $orderItemIds
     * @param $assignedItems
     * @param $posId
     * @return array
     */
    protected function calculateTotalInfo(
        \Magento\Sales\Model\Order $order,
        array $orderItemIds,
        $assignedItems,
        $posId
    ) {
        $arrItems = [];
        $orderItems = [];

        $totalShipment = 0;
        $baseTotalShipment = 0;
        $discountShipment = 0;
        $baseDiscountShipment = 0;
        $giftWrappingAmount = 0;
        $baseGiftWrappingAmount = 0;
        $giftWrappingTaxAmount = 0;
        $baseGiftWrappingTaxAmount = 0;
        $taxAmount = 0;
        $qtyAssigned = 0;
        $qtyCaseAssigned = 0;

        foreach ($orderItemIds as $orderItemId) {
            if (isset($assignedItems[$orderItemId])) {
                $val = $this->validItem($assignedItems[$orderItemId]);

                if (isset($val['pos'][$posId])) {
                    if (!$this->isChildrenOrderItem($orderItemId, $order)) {
                        $orderItemObject = $this->getOrderItemById($orderItemId);
                        $qtyInfo = $val['pos'][$posId];
                        $casesQty = $qtyInfo['qty_assigned'] / $this->getUnitQtyByOrderItemId($orderItemId, $order);
                        $qtyAssigned +=$qtyInfo['qty_assigned'];
                        $qtyCaseAssigned += $casesQty;
                        $arrItems[$orderItemId] = $qtyInfo['qty_assigned'];
                        $orderItems[] = $orderItemId;
                        $totalShipment += $val["price"] * $qtyInfo['qty_assigned'];
                        $baseTotalShipment += $val["base_price"] * $qtyInfo['qty_assigned'];
                        $amountRate= 1;
                        if ($orderItemObject->getUnitCase()==self::UNIT_CASE_CS) {
                            $amountRate = $orderItemObject->getUnitQty();
                        }
                        if ($orderItemObject->getProductType()==\Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                            $discountShipment += $val["discount"] ;
                            $baseDiscountShipment += $val["base_discount"];
                        } else {
                            $discountShipment += $val["discount"]* $amountRate * $casesQty;
                            $baseDiscountShipment += $val["base_discount"]* $amountRate * $casesQty;
                        }
                        $giftWrappingAmount += $val["gw_price"] * $casesQty;
                        $baseGiftWrappingAmount += $val["gw_base_price"] * $casesQty;
                        $giftWrappingTaxAmount += $val["gw_tax_amount"] * $casesQty;
                        $baseGiftWrappingTaxAmount += $val["gw_base_tax_amount"] * $casesQty;
                        $taxAmount += $val["tax_amount"] * $qtyInfo['qty_assigned'];
                    }
                }
            }
        }

        return [
            'qty'   =>  $qtyAssigned,
            'qty_case'   =>  $qtyCaseAssigned,
            'item_qty'   =>  $arrItems,
            'item_ids'   =>  $orderItems,
            'total'   =>  $totalShipment,
            'base_total'   =>  $baseTotalShipment,
            'discount'   =>  $discountShipment,
            'base_discount'   =>  $baseDiscountShipment,
            'gw_total'   =>  $giftWrappingAmount,
            'base_gw_total'   =>  $baseGiftWrappingAmount,
            'gw_tax'   =>  $giftWrappingTaxAmount,
            'base_gw_tax'   =>  $baseGiftWrappingTaxAmount,
            'tax'   =>  $taxAmount
        ];
    }

    /**
     * Get order item by item id
     *
     * @param $itemId
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     */
    public function getOrderItemById($itemId)
    {
        return $this->orderItemRepository->get($itemId);
    }

    /**
     * @param $orderItemId
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function isChildrenOrderItem($orderItemId, \Magento\Sales\Model\Order $order)
    {
        foreach ($order->getAllItems() as $item) {
            if ($item->getId() == $orderItemId &&
                $item->getParentItemId()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $orderItemId
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function getUnitQtyByOrderItemId($orderItemId, \Magento\Sales\Model\Order $order)
    {
        foreach ($order->getAllItems() as $item) {
            if ($item->getId() == $orderItemId) {
                if ($item->getUnitQty() &&
                    $item->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\UnitEc::UNIT_CASE
                ) {
                    return max(1, (int)$item->getUnitQty());
                }

                return 1;
            }
        }
        return 1;
    }

    /**
     * @param $order
     * @param $shipItemId
     * @param $totalQtyShipped
     * @param $listDeliveryTypeGroupByItemRaw
     * @param $posIds
     * @param $addressIds
     */
    public function checkDuplicateShipment(
        $order,
        $shipItemId,
        $totalQtyShipped,
        $listDeliveryTypeGroupByItemRaw,
        $posIds,
        $addressIds
    ) {
        foreach ($order->getAllItems() as $checkShipItem) {
            if ($checkShipItem->getId() == $shipItemId &&
                !$checkShipItem->getParentItemId() &&
                $totalQtyShipped[$shipItemId] > $checkShipItem->getQtyOrdered()
            ) {
                $this->logger->info('DUPLICATE shipment for ORDER ID #' . $order->getIncrementId());
                $this->logger->info(json_encode($listDeliveryTypeGroupByItemRaw));
                $this->logger->info(json_encode($posIds));
                $this->logger->info(json_encode($addressIds));
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $data
     * @param $paymentMethod
     * @param $exportDate
     * @param $deliveryDate
     * @param $deliveryTime
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function fillShipmentData(
        \Magento\Sales\Model\Order\Shipment $shipment,
        $data,
        $paymentMethod,
        $exportDate,
        $deliveryDate,
        $deliveryTime
    ) {
        $shipment->setTotalQty($data['qty_assigned']);
        $shipment->setTotalCaseQty($data['qty_case_assigned']);
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

        if (isset($data['shopping_point_amount'])) {
            $shipment->setShoppingPointAmount($data['shopping_point_amount']);
        }
        if (isset($data['base_shopping_point_amount'])) {
            $shipment->setBaseShoppingPointAmount($data['base_shopping_point_amount']);
        }

        //update grand total
        $grandTotal = $this->getFinalTotal($shipment);
        $shipment->setGrandTotal($grandTotal);
        $address = $this->shipmentHelper->rebuildAddress($shipment);
        $shipment->setData('billing_address', $address[0]);
        $shipment->setData('shipping_address', $address[1]);
        $shipment->setData('shipping_name', $address[2]);
        $shipment->setData('shipping_name_kana', $address[3]);
        $shipment->setData('shipping_address_name', $data['shipping_address_name']);
        $shipment->setData('shipping_address_id', $data['shipping_address_id']);
        $shipment->setData('shipping_address_newid', $data['shipping_address_id']);
        $shipment->setExportDate($exportDate);
        $this->logger->info(__(
            'Set delivery date %1 for a shipment of the order #%2',
            $deliveryDate,
            $shipment->getOrderId()
        ));
        $shipment->setDeliveryDate($deliveryDate);
        $shipment->setDeliveryTime($deliveryTime);
        $shipment->setData('shipment_status', $data['shipment_status']);
        $shipment->setData('is_exported', $data['is_exported']);
        $shipment->setShipmentDate($this->dateTime->gmtDate('Y-m-d H:i:s'));
        $shipment->setData('stock_point_delivery_bucket_id', $data['stock_point_delivery_bucket_id']);
        //set b2bFlag
        $b2bFlag = $this->rikiCustHelper->getB2bFlagValue($address[5]);
        $shipment->setData('customer_b2b_flag', $b2bFlag);
        if (empty($collectionDate)) {
            $collectionDate = $this->timeZone->date()->format('Y-m-d');
        }
        /*set shipment data for cvspayment case*/
        if ($paymentMethod == \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE) {
            /*change payment status to payment collected and update collected date(payment_date)*/
            $shipment->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
            /*System date when we receive the payment collected message*/
            $shipment->setPaymentDate($this->dateTime->date('Y-m-d'));
            $collectionDate = !empty($data['shipment_collection_date']) ?
                $data['shipment_collection_date']:
                false;
            /*The actual Payment collection date - actual date from import file - 1507*/
            $shipment->setCollectionDate($collectionDate);
        } elseif ($paymentMethod == self::FREE_PAYMENT) {
            /*payment status = not applicable for free of charge order*/
            if ($this->order
                && $this->order->getChargeType() != \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_NORMAL
            ) {
                $shipment->setPaymentStatus(
                    \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_NOT_APPLICABLE
                );
            }
        }
        /* check Zsim shipment */
        if ($this->shipmentExporterDataHelper->checkZSIM($shipment)) {
            $shipment->setData('shipment_status', ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED);
            $shipment->setShipZsim(1);
        }
        return $shipment;
    }

    /**
     * Remove chirashi item if has chirashi and including case products only
     * @param array $shipmentData
     * @return array
     */
    private function removeChirashiIncludingCaseProductsOnly($shipmentData)
    {
        $shipmentItems = $shipmentData['items'];
        $chirashiItemIds = [];
        $countProductCase = 0;
        $productPiece = 0;
        foreach ($shipmentItems as $itemId => $qty) {
            try {
                $orderItem = $this->orderItemRepository->get($itemId);
            } catch (NoSuchEntityException $e) {
                $orderItem = '';
                $this->logger->info(__('The order item ID %1 does not exist.', $itemId));
            }
            if (!$orderItem) {
                continue;
            }
            if ($orderItem->getData('chirashi')) {
                $chirashiItemIds[] = $itemId;
            } else {
                //do not count bundle product
                if ((!$orderItem->getParentItemId() && $orderItem->getProductType()=="simple")
                    || $orderItem->getParentItemId()) {
                    //count product case
                    if ($orderItem->getData('unit_case') ==
                        \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE
                    ) {
                        $countProductCase++;
                    }
                    // count product piece
                    if ($orderItem->getData('unit_case') ==
                        \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE
                    ) {
                        $productPiece++;
                    }
                }
            }
        }
        if (!empty($chirashiItemIds) && $countProductCase && !$productPiece) {
            //remove chirashi from shipment items
            foreach ($chirashiItemIds as $chirashiItemId) {
                if (isset($shipmentData['items'][$chirashiItemId])) {
                    unset($shipmentData['items'][$chirashiItemId]);
                }
            }
        }
        return $shipmentData;
    }

    /**
     * generate Sap Exported Data for shipment after created success
     *
     * @param $shipment
     */
    protected function generateSapExportedData($shipment)
    {
        /*reject simulate flow*/
        if ($shipment instanceof \Riki\Subscription\Model\Emulator\Order\Shipment) {
            return;
        }

        /** @var \Riki\SapIntegration\Model\ShipmentSapExported $shipmentSapExported */
        $shipmentSapExported = $this->shipmentSapExportedFactory->create();
        $shipmentSapExported->setShipmentEntityId(
            $shipment->getId()
        )->setShipmentIncrementId(
            $shipment->getIncrementId()
        )->setOrderId(
            $shipment->getOrderId()
        )->isObjectNew(true);
        try {
            $shipmentSapExported->save();
        } catch (\Exception $e) {
            $this->logger->info('Cannot generate Sap exported data for shipment #'. $shipment->getIncrementId());
            $this->logger->critical('Shipment #'.$shipment->getIncrementId().' error: '. $e->getMessage());
        }
    }

    /**
     * Prepared Shipments data
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool|array
     * @throws
     */
    public function preparedShipmentsData($order)
    {
        /*get order assignation info*/
        $assignation = $this->getOrderAssignation($order);
        if (!$assignation) {
            $this->logger->info('Order : ' . $order->getIncrementId() . ' have not product assignation');
            return false;
        }

        $assignedItems = $assignation['items'];
        $addressIds = $this->getMultiAddressItems($order);

        $listDeliveryTypeGroupByItemRaw = $this->splitOrderItemByDeliveryType($order, $addressIds);

        $posIds = explode(',', $assignation['place_ids']);
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
        $orderAddressToCustomerAddress = $this->sapIntegrationHelper->getOrderAddressToCustomerAddressesByOrder($order);
        // check duplicate shipment issue
        $totalQtyShipped = [];

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
                    $qtyCaseAssigned = $shipmentTotalInfo['qty_case'];
                    //make data for shipment create
                    if (!$arrItems) {
                        continue;
                    }
                    //check shipment duplicate issue
                    foreach ($arrItems as $shipItemId => $shipItemQty) {
                        if (!isset($totalQtyShipped[$shipItemId])) {
                            $totalQtyShipped[$shipItemId] = 0;
                        }
                        $totalQtyShipped[$shipItemId] += $shipItemQty;
                        //check duplicate shipment
                        $this->checkDuplicateShipment(
                            $order,
                            $shipItemId,
                            $totalQtyShipped,
                            $listDeliveryTypeGroupByItemRaw,
                            $posIds,
                            $addressIds
                        );
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

                    if (in_array($deliveryKey, [Delitype::COOL, Delitype::NORMAl, Delitype::DM])) {
                        $deliveryKey = Delitype::COOL_NORMAL_DM;
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
                        'index' => $shipmentIndex,
                        'total' => $rowTotal
                    ];
                    $shipmentsTotal[] = $rowTotal;
                    $shippingFeeByDelivery = $this->preparedShipmentShippingFee(
                        $order,
                        $shippingFees,
                        $shipmentIndex,
                        $addressId,
                        $deliveryKey
                    );
                    $subscriptionProfileId = $order->getData('subscription_profile_id');
                    $isExported = 0;
                    $defaultShipmentStatus = ShipmentStatus::SHIPMENT_STATUS_CREATED;
                    if ($subscriptionProfileId) {
                        try {
                            $subscriptionProfile = $this->subscriptionRepository->get($subscriptionProfileId);
                            if (($subscriptionProfile->getDisengagementReason() && $subscriptionProfile->getDisengagementReason() != 27 && $subscriptionProfile->getDisengagementUser() != 'RMM-377') // Only if subscription was not disengaged by merge profile reason)
                                || $this->isOrderGeneratedFromMonthlyFeeProfile()) {
                                $isExported = 1;
                            }
                        } catch (\Exception $e) {
                            $this->logger->info(
                                $subscriptionProfileId.
                                ' does not exist - Shipment creator for disengage subscription'
                            );
                        }
                    }
                    $shipmentsData[$shipmentIndex] = [
                        'qty_assigned' => $qtyAssigned,
                        'qty_case_assigned' => $qtyCaseAssigned,
                        'items' => $arrItems,
                        'post_id' => $posId,
                        'address_key' => $addressKey,
                        'delivery_type' => strtolower($deliveryType),
                        'total' => $totalShipment,
                        'base_total' => $baseTotalShipment,
                        'discount' => $discountShipment,
                        'base_discount' => $baseDiscountShipment,
                        'shipping_fee' => $shippingFeeByDelivery,
                        'base_shipping_fee' => $shippingFeeByDelivery,
                        'payment_fee' => $order->getFee(),
                        'base_payment_fee' => $order->getBaseFee(),
                        'gw_price' => $giftWrappingAmount,
                        'gw_base_price' => $baseGiftWrappingAmount,
                        'gw_tax_amount' => $giftWrappingTaxAmount,
                        'gw_base_tax_amount' => $baseGiftWrappingTaxAmount,
                        'tax_amount' => $taxAmount,
                        'subscription_profile_id' => $order->getData('subscription_profile_id'),
                        'shipping_address_id' => $addressKey,
                        'shipping_address_name' => $this->sapIntegrationHelper->getNewShippingName($addressKey),
                        'shipment_collection_date' => $shipmentCollectionDate,
                        'is_exported' => $isExported,
                        'shipment_status' => $this->getDefaultShipmentStatus(),
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

        $preparedShipmentsData = [];
        foreach ($shipmentsData as $index => $shipmentData) {
            if ((int)($shipmentData['qty_assigned']) > 0) {
                $shipmentData = $this->removeChirashiIncludingCaseProductsOnly($shipmentData);
                $preparedShipmentsData[] = $this->createSingleShipment(
                    $order->getId(),
                    $shipmentData,
                    $paymentMethod,
                    in_array($index, $maxTotalShipmentDeliveryIndex),
                    $index == $maxTotalShipmentIndex
                );
            }
        }

        return $preparedShipmentsData;
    }

    /**
     * Get default status for shipment
     * If order is generated from monthly_fee profile, shipment status will be exported
     * @return string
     */
    public function getDefaultShipmentStatus()
    {
        return $this->isOrderGeneratedFromMonthlyFeeProfile()
            ? shipmentStatus::SHIPMENT_STATUS_EXPORTED
            : shipmentStatus::SHIPMENT_STATUS_CREATED;
    }

    /**
     * Check if order is generated from monthly fee profile
     * @return bool
     */
    public function isOrderGeneratedFromMonthlyFeeProfile()
    {
        $result = false;
        $subscriptionType = '';

        if ($this->order) {
            $subscriptionProfileId = $this->order->getData('subscription_profile_id');
            // Get subscription type
            if ($subscriptionProfileId) {
                try {
                    $subscriptionProfile = $this->subscriptionRepository->get($subscriptionProfileId);
                    $courseId = $subscriptionProfile->getCourseId();
                    $subscriptionType = $this->subscriptionPageHelper->getSubscriptionType($courseId);
                } catch (\Exception $e) {
                    $this->logger->info(
                        $subscriptionProfileId .
                        ' does not exist - Shipment creator'
                    );
                }
            }
        }

        if ($subscriptionType == SubCourseType::TYPE_MONTHLY_FEE) {
            $result = true;
        }

        return $result;
    }
}