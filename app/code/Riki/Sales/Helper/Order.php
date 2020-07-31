<?php

namespace Riki\Sales\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatusResourceModel;
use Magento\Sales\Model\Order as MageOrder;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class Order extends AbstractHelper
{
    const CREATED_FROM_FO = 'Web Order';
    const BUNDLE_SHIPMENT_TYPE_TOGETHER = 0;
    const BUNDLE_SHIPMENT_TYPE_SEPARATELY = 1;
    const CUSTOMER_AMBASSADOR_MEMBERSHIP = 'Ambassador';
    const RIKI_TYPE_SPOT = 'SPOT';
    const RIKI_TYPE_SUBSCRIPTION = 'SUBSCRIPTION';
    const RIKI_TYPE_HANPUKAI = 'HANPUKAI';
    const RIKI_TYPE_DELAY_PAYMENT = 'DELAY PAYMENT';
    const TAX_8_PERCENT = 8;
    const TAX_10_PERCENT = 10;
    const XML_PATH_STOCK_POINT_GET_DELIVERY_STATUS_URL = 'subscriptioncourse/stockpoint/url_api_delivery_status_stock_point';
    const FRAUD_LOGIC_CODE = 'fraud suspected-logic';
    const FRAUD_SEGMENT_CODE = 'fraud suspected-segment';

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $_orderItemRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchBuilder;

    /**
     * @var \Wyomind\AdvancedInventory\Model\Stock
     */
    protected $stock;

    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_rewardPointHelper;

    /**
     * @var \Riki\Loyalty\Helper\Email
     */
    protected $_rewardPointEmail;

    /**
     * @var \Riki\Fraud\Model\ScoreFactory
     */
    protected $_scoreFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    protected $_connectionHelper;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $_shipmentRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Riki\Customer\Helper\Membership
     */
    protected $_customerMembershipHelper;

    /**
     * @var
     */
    protected $_totals;

    /**
     * @var \Riki\Customer\Model\CustomerRepository $_rikiCustomerRepository
     */
    protected $_rikiCustomerRepository;

    protected $_orderAddressRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Riki\StockPoint\Model\Api\BuildStockPointPostData
     */
    protected $apiBuildStockPointPostData;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper
     */
    protected $bundleItemsHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json|null
     */
    private $serializer;

    /**
     * Order constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $itemRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Wyomind\AdvancedInventory\Model\Stock $stock
     * @param \Riki\Loyalty\Helper\Data $rewardPointHelper
     * @param \Riki\Loyalty\Helper\Email $rewardPointEmail
     * @param \Riki\Fraud\Model\ScoreFactory $scoreFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $_productRepository
     * @param ConnectionHelper $connectionHelper
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Customer\Helper\Membership $membershipHelper
     * @param \Riki\Customer\Model\CustomerRepository $_rikiCustomerRepository
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Api\OrderItemRepositoryInterface $itemRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Wyomind\AdvancedInventory\Model\Stock $stock,
        \Riki\Loyalty\Helper\Data $rewardPointHelper,
        \Riki\Loyalty\Helper\Email $rewardPointEmail,
        \Riki\Fraud\Model\ScoreFactory $scoreFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $_productRepository,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Customer\Helper\Membership $membershipHelper,
        \Riki\Customer\Model\CustomerRepository $_rikiCustomerRepository,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Magento\Framework\Registry $registry,
        \Riki\StockPoint\Model\Api\BuildStockPointPostData $buildStockPointPostData,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        parent::__construct($context);
        $this->_orderItemRepository = $itemRepository;
        $this->_searchBuilder = $searchCriteriaBuilder;
        $this->_orderRepository = $orderRepository;
        $this->stock = $stock;
        $this->_rewardPointHelper = $rewardPointHelper;
        $this->_rewardPointEmail = $rewardPointEmail;
        $this->_scoreFactory = $scoreFactory;
        $this->_productRepository = $_productRepository;
        $this->_connectionHelper = $connectionHelper;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_customerRepository = $customerRepository;
        $this->_customerMembershipHelper = $membershipHelper;
        $this->_rikiCustomerRepository = $_rikiCustomerRepository;
        $this->_orderAddressRepository = $orderAddressRepository;
        $this->_registry = $registry;
        $this->apiBuildStockPointPostData = $buildStockPointPostData;
        $this->scopeConfig = $context->getScopeConfig();
        $this->bundleItemsHelper = $bundleItemsHelper;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    /**
     * Get order item object by order item id
     *
     * @param int $orderItemId
     * @return bool|\Magento\Sales\Api\Data\OrderItemInterface
     */
    public function getOrderItemById($orderItemId)
    {
        try {
            return $this->_orderItemRepository->get($orderItemId);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get order item and indexing by id
     *
     * @param array $itemIds
     * @return array
     */
    public function getOrderItemByIds(array $itemIds)
    {
        $filter = $this->_searchBuilder->addFilter('item_id', $itemIds, 'in');
        $orderItems = $this->_orderItemRepository->getList($filter->create());
        $result = [];
        if ($orderItems->getTotalCount()) {
            foreach ($orderItems->getItems() as $item) {
                $result[$item->getItemId()] = $item;
            }
        }
        return $result;
    }

    /**
     * Get list order and return id indexing
     *
     * @param array $orderIds
     * @return array
     */
    public function getOrderByIds(array $orderIds)
    {
        $filter = $this->_searchBuilder->addFilter('entity_id', $orderIds, 'in');
        $orders = $this->_orderRepository->getList($filter->create());
        $result = [];
        if ($orders->getTotalCount()) {
            foreach ($orders->getItems() as $order) {
                $result[$order->getEntityId()] = $order;
            }
        }
        return $result;
    }

    /**
     * Get children item list of item which id is $itemId
     *
     * @param $itemId
     * @return bool|\Magento\Sales\Api\Data\OrderItemInterface[]
     */
    public function getChildrenItemById($itemId)
    {
        $filter = $this->_searchBuilder->addFilter('parent_item_id', $itemId);

        $orderItems = $this->_orderItemRepository->getList($filter->create());

        if ($orderItems->getTotalCount()) {
            return $orderItems->getItems();
        }

        return false;
    }

    /**
     * @param $quote
     * @return array
     */
    public function checkBackOrder($quote)
    {
        $items = $quote->getAllItems();
        $data = [];
        $data['back_order'] = false;

        foreach ($items as $item) {
            $productId = $item->getProductId();
            $isBackOrder = $this->stock->getStockBackOrderByProductId($productId);
            foreach ($isBackOrder as $key => $listOrder) {
                if ($listOrder['backorder_allowed']) {
                    $data['back_order'] = true;
                    $data['dd_allowed'] = $listOrder['backorder_delivery_date_allowed'];
                    return $data;
                }
            }
        }
        return $data;
    }

    /**
     * check order item is free item
     *
     * @param int $orderItemId
     * @return bool
     */
    public function isFreeItemByOrderItemId($orderItemId)
    {
        $orderItem = $this->getOrderItemById($orderItemId);
        return $this->isFreeItem($orderItem);
    }

    /**
     * check order item is free item
     *
     * @param $orderItem
     * @return bool
     */
    public function isFreeItem($orderItem)
    {
        if ($orderItem && !floatval($orderItem->getPrice())) {
            if ($orderItem->getData('free_of_charge')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get order item wbs to export to SAP
     *
     * @param $orderItem
     * @return bool|mixed|null
     */
    public function getOrderItemWbsForSap($orderItem)
    {
        if ($this->isFreeItem($orderItem)) {
            return $orderItem->getData('foc_wbs');
        } else {
            return false;
        }
    }

    /**
     * Return the total amount after discount, exclude tax
     *
     * @param $item
     * @param $itemQty
     * @return string
     */
    public function getOrderItemBaseTotalAmount($item, $itemQty)
    {
        /*total price for not bundle product*/
        if ($item->getProductType() != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            $totalAmount = $this->getOrderItemBaseTotalAmountForSingleItem($item, $itemQty);
        } else {
            $totalAmount = $this->getOrderItemBaseTotalAmountForBundleItem($item, $itemQty);
        }

        return number_format($totalAmount, 0, '', '');
    }

    /**
     * Return the total amount after discount for order item is bundle product, exclude tax
     *
     * @param $item
     * @param $itemQty
     * @return int|mixed
     */
    public function getOrderItemBaseTotalAmountForBundleItem($item, $itemQty)
    {
        $filter = $this->_searchBuilder->addFilter('parent_item_id', $item->getId());
        /*get children item of this item*/
        $childrenItem = $this->_orderItemRepository->getList($filter->create());

        $totalAmount = 0;
        if ($childrenItem->getTotalCount()) {
            foreach ($childrenItem->getItems() as $child) {
                $totalAmount += $this->getOrderItemBaseTotalAmountForSingleItem($child, $itemQty);
            }
        } else {
            $totalAmount = $this->getOrderItemBaseTotalAmountForSingleItem($item, $itemQty);
        }

        return $totalAmount;
    }

    /**
     * Return the total amount after discount for order item is single product, exclude tax
     *
     * @param $item
     * @param $itemQty
     * @return mixed
     */
    public function getOrderItemBaseTotalAmountForSingleItem($item, $itemQty)
    {
        /*product base price - excl tax*/
        $basePrice = $this->getOrderItemBasePrice($item);

        /*product base discount - per qty - exclude tax*/
        $baseDiscountPerQty = $this->getOrderItemBaseDiscountPerQty($item);

        /*base price after discount */
        $basePriceAfterDiscount = $basePrice - $baseDiscountPerQty;

        if ($basePriceAfterDiscount > 0) {
            return $basePriceAfterDiscount * $itemQty;
        }

        return 0;
    }

    /**
     * Get tax amount for each order item by qty
     *
     * @param $item
     * @param $qty
     * @return int|string
     */
    public function getTaxAmountForEachOrderItemByQty($item, $qty)
    {
        /*tax amount for this item with all qty*/
        $taxAmountPerQty = $this->getOrderItemBaseTaxPerQty($item);

        if ($taxAmountPerQty < 0) {
            $taxAmountPerQty = 0;
        }

        if ($qty >= $item->getData('qty_ordered')) {
            $qty = $item->getData('qty_ordered');
        }

        if ($qty > 0) {
            return number_format($taxAmountPerQty * $qty, 0, '', '');
        }

        return 0;
    }

    /**
     * Get item base price, exclude tax
     *
     * @param $item
     * @return int
     */
    public function getOrderItemBasePrice($item)
    {
        $basePrice = $item->getBasePrice();

        if ($item->getParentItemId() && $basePrice == 0) {
            $parentItem = $this->getOrderItemById($item->getParentItemId());
            if ($parentItem->getBasePrice() != 0) {
                $basePrice = $this->getOrderItemBasePriceForBundleChildrenItem($item);
            }
        }

        return $basePrice;
    }

    /**
     * Get item base price, exclude tax
     *
     * @param $item
     * @return int
     */
    public function getOrderItemBaseDiscountPerQty($item)
    {
        if (!empty($item->getParentItemId())) {
            /*get parent item*/
            $parentItem = $this->getOrderItemById($item->getParentItemId());

            if ($parentItem) {
                /*discount amount for bundle item per qty*/
                $discountAmountPerQty = $this->getOrderItemBaseDiscountPerQtyForSingleItem($parentItem);

                if ($discountAmountPerQty > 0) {
                    /*parent item base price*/
                    $parentItemBasePrice = $parentItem->getPrice();

                    if ($parentItemBasePrice) {
                        /*current item base price*/
                        $itemBasePrice = $this->getOrderItemBasePriceForBundleChildrenItem($item);

                        if ($itemBasePrice > 0) {
                            return $itemBasePrice * $discountAmountPerQty / $parentItemBasePrice;
                        }
                    }
                }
            }

            return 0;
        } else {
            return $this->getOrderItemBaseDiscountPerQtyForSingleItem($item);
        }
    }

    /**
     * Get Order Item Base Discount Per Qty For Single Item, exclude tax
     *
     * @param $item
     * @return float
     */
    public function getOrderItemBaseDiscountPerQtyForSingleItem($item)
    {
        if ($item->getQtyOrdered() && $item->getData('discount_amount_excl_tax') > 0) {
            return $item->getData('discount_amount_excl_tax') / ($item->getQtyOrdered());
        }

        return 0;
    }

    /**
     * Get item base tax per qty
     *
     * @param $item
     * @return int
     */
    public function getOrderItemBaseTaxPerQty($item)
    {
        if (!empty($item->getParentItemId())) {
            /*get parent item*/
            $parentItem = $this->getOrderItemById($item->getParentItemId());

            if ($parentItem) {
                /*taxt amount for bundle item per qty*/
                $taxAmountPerQty = $this->getOrderItemBaseTaxPerQtyForSingleItem($parentItem);

                if ($taxAmountPerQty > 0) {
                    /*parent item base price*/
                    $parentItemBasePrice = $parentItem->getPrice();

                    if ($parentItemBasePrice) {
                        /*current item base price*/
                        $itemBasePrice = $this->getOrderItemBasePriceForBundleChildrenItem($item);

                        if ($itemBasePrice > 0) {
                            return $itemBasePrice * $taxAmountPerQty / $parentItemBasePrice;
                        }
                    }
                }
            }

            return 0;
        } else {
            return $this->getOrderItemBaseTaxPerQtyForSingleItem($item);
        }
    }

    /**
     * Get Order Item Base tax Per Qty For Single Item
     *
     * @param $item
     * @return float
     */
    public function getOrderItemBaseTaxPerQtyForSingleItem($item)
    {
        if ($item->getQtyOrdered() && $item->getData('tax_riki') > 0) {
            return $item->getData('tax_riki') / $item->getQtyOrdered();
        }

        return 0;
    }

    /**
     * Get item base price for item which is bundle item
     *
     * @param $item
     * @return int
     */
    public function getOrderItemBasePriceForBundleItem($item)
    {
        /*get bundle option*/
        $bundleOption = $item->getProductOptionByCode('bundle_options');

        $price = 0;

        if (!empty($bundleOption) && is_array($bundleOption)) {
            foreach ($bundleOption as $bo) {
                $optionData = $bo['value'];
                if (!empty($optionData)) {
                    if (!empty($optionData[0]['price'])) {
                        /*total count bundle child price*/
                        $price += $optionData[0]['price'];
                    }
                }
            }
        }

        return $price;
    }

    /**
     * Get item base price for item which is children of bundle item
     *
     * @param $item
     * @return int
     */
    public function getOrderItemBasePriceForBundleChildrenItem($item)
    {
        $bundleOption = $item->getProductOptionByCode('bundle_selection_attributes');
        $bundleOption = $this->serializer->unserialize($bundleOption);

        if (isset($bundleOption['price'])) {
            if (!empty($bundleOption['qty'])) {
                return $bundleOption['price'] / $bundleOption['qty'];
            } else {
                return $bundleOption['price'];
            }
        } else {
            return 0;
        }
    }

    /**
     * Get order item qty for shipment qty
     *
     * @param $item
     * @param $shipmentQty
     * @return float
     */
    public function getOrderItemQtyForShipmentQty($item, $shipmentQty)
    {
        if (!empty($item->getParentItemId())) {
            /*parent item*/
            $parentItem = $this->getOrderItemById($item->getParentItemId());

            if ($parentItem) {
                /*parent item qty*/
                $parentItemQty = $parentItem->getQtyOrdered();

                /*item qty*/
                $itemQty = $item->getQtyOrdered();

                if ($parentItemQty) {
                    return $itemQty * $shipmentQty / $parentItemQty;
                }
            }
        }

        return $shipmentQty;
    }

    /**
     * Check order created from BO
     *
     * @param $order
     * @return bool
     */
    public function isOrderCreatedFromBO($order)
    {
        if ($order && $order->getCreatedBy() != self::CREATED_FROM_FO) {
            return true;
        }

        return false;
    }

    /**
     * check order is free of charge order
     *
     * @param $order
     * @return bool
     */
    public function isFreeOfChargeOrder($order)
    {
        /*get order charge type*/
        $orderChargeType = $order->getChargeType();
        if ($orderChargeType == \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_FREE_SAMPLE
            || $orderChargeType == \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_REPLACEMENT
        ) {
            return true;
        }

        return false;
    }

    /**
     * get order payment method
     *
     * @param $order
     * @return bool
     */
    public function getOrderPaymentMethod($order)
    {
        if ($order && $order->getPayment()) {
            return $order->getPayment()->getMethod();
        }

        return false;
    }

    /**
     * check order is created with payment method is invoicedbasepayment
     *
     * @param $orderId
     * @return bool
     */
    public function isInvoicedOrderById($orderId)
    {
        /*get order data*/
        $order = $this->_orderRepository->get($orderId);
        /*get order payment method*/
        $orderPaymentMethod = $this->getOrderPaymentMethod($order);

        if ($orderPaymentMethod
            && $orderPaymentMethod == \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE
        ) {
            return true;
        }
        return false;
    }

    /**
     * check order is crd review order (need approved by call center)
     *
     * @param $order
     * @return boolean
     */
    public function isCrdReviewOrder($order)
    {
        /*free of charge order, created by call center*/
        if ($this->isFreeOfChargeOrder($order)) {
            return true;
        }

        /*free surcharge order created by call center*/
        if ($order->getIsFreePaymentChargeByAdmin()) {
            return true;
        }

        /*free shipping order created by call center*/
        if ($order->getIsFreeShippingByAdmin()) {
            return true;
        }

        /*this order need approved to earn point by call center*/
//        if ($this->isWaitingPointApprovalOrder($order)) {
//            return true;
//        }

        return false;
    }

    /**
     * check order is that need to approved to earn point by call center
     *
     * @param $order
     * @return bool
     */
    public function isWaitingPointApprovalOrder($order)
    {
        return $this->_rewardPointHelper->waitingPointApprove($order);
    }

    /**
     * Send request email to approve earn point for call center
     *
     * @param $order
     */
    public function requestApprovalEarnPoint($order)
    {
        if ($this->isWaitingPointApprovalOrder($order)) {
            $order->setData('point_pending_status', $order->getStatus());
            $this->_rewardPointEmail->requestApproval($order);
        }
    }

    /**
     * Check order is fraud order
     *
     * @param $order
     * @return bool
     */
    public function isFraudOrder($order)
    {
        if ($order->getData('is_generate') == 1) {
            return;
        }
        $fraudModel = $this->_scoreFactory->create();
        return $fraudModel->isFraudOrder($order);
    }

    /**
     * Check bundle item shipment type
     *      1 -> shipment type is Separately -> return true
     *      0 -> shipment type is Together -> return false
     * @param $item
     * @return bool
     */
    public function isBundleItemShipSeparately($item)
    {
        /*get bundle option shipment type*/
        $shipmentType = $item->getProductOptionByCode('shipment_type');

        if (!empty($shipmentType) && $shipmentType == self::BUNDLE_SHIPMENT_TYPE_SEPARATELY) {
            return true;
        }

        return false;
    }

    /**
     *  Check can export shipment by product itemId
     * @param $itemId
     * @return bool
     */
    public function checkSapInterfaceExcluded($itemId)
    {
        $productItem = $this->_productRepository->getById($itemId);

        if ($productItem) {
            if ($productItem->getData('sap_interface_excluded') != 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get product data for order item
     *
     * @param $orderItemData
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductDataForOrderItem($orderItemData)
    {
        try {
            return $this->_productRepository->getById(
                $orderItemData['product_id'],
                false,
                $orderItemData['store_id']
            );
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }

        return false;
    }

    /**
     * Get product Gps price by product id
     *
     * @param $productId
     * @param $storeId
     * @return bool
     */
    public function getProductGpsPriceById($productId, $storeId)
    {
        try {
            $productItem = $this->_productRepository->getById($productId, false, $storeId);

            if ($productItem) {
                if ($productItem->getData('gps_price_ec')) {
                    return $productItem->getData('gps_price_ec');
                }
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }

        return false;
    }

    /**
     * Get item Gps price by item id
     *
     * @param $itemId
     * @return bool
     */
    public function getItemGpsPriceById($itemId)
    {
        $orderItem = $this->getOrderItemById($itemId);

        if ($orderItem) {
            return $this->getProductGpsPriceById($orderItem->getProductId(), $orderItem->getStoreId());
        }

        return false;
    }

    /**
     * Get commission amount for return item
     *
     * @param $rmaItem
     * @param $orderItem
     * @return int
     */
    public function getCommissionAmountForReturnItem($rmaItem, $orderItem)
    {
        if (!empty($orderItem->getParentItemId())) {
            /*get parent item*/
            $parentItem = $this->getOrderItemById($orderItem->getParentItemId());

            if ($parentItem) {
                /*commission amount of parent item*/
                $parentCommissionAmount = $parentItem->getData('commission_amount');

                /*row total of parent item*/
                $parentRowTotalAmount = $parentItem->getData('row_total');

                /*discount amount of parent item*/
                $parentDiscountExclTax = $parentItem->getData('discount_amount_excl_tax');

                if ($parentCommissionAmount > 0 && $parentRowTotalAmount > 0) {
                    return $this->getCommissionAmountForItemByAmount(
                        $rmaItem->getData('return_amount_excl_tax'),
                        $parentCommissionAmount,
                        $parentRowTotalAmount,
                        $parentDiscountExclTax
                    );
                }
            }
            return 0;
        } else {
            return $this->getCommissionAmountForReturnSingleItem($rmaItem, $orderItem);
        }
    }

    /**
     * Get commission amount
     *
     * @param $rmaItem
     * @param $orderItem
     * @return float|int
     */
    public function getCommissionAmountForReturnSingleItem($rmaItem, $orderItem)
    {
        return $this->getCommissionAmountForItemByAmount(
            $rmaItem->getData('return_amount_excl_tax'),
            $orderItem->getData('commission_amount'),
            $orderItem->getData('row_total'),
            $orderItem->getData('discount_amount_excl_tax')
        );
    }

    public function getCommissionAmountForShipmentItem($shipmentTotal, $orderItem)
    {
        if (!empty($orderItem->getParentItemId())) {
            /*get parent item*/
            $parentItem = $this->getOrderItemById($orderItem->getParentItemId());

            if ($parentItem) {
                /*commission amount of parent item*/
                $parentCommissionAmount = $parentItem->getData('commission_amount');

                /*row total of parent item*/
                $parentRowTotalAmount = $parentItem->getData('row_total');

                /*discount amount of parent item*/
                $parentDiscountExclTax = $parentItem->getData('discount_amount_excl_tax');

                if ($parentCommissionAmount > 0 && $parentRowTotalAmount > 0) {
                    return $this->getCommissionAmountForItemByAmount(
                        $shipmentTotal,
                        $parentCommissionAmount,
                        $parentRowTotalAmount,
                        $parentDiscountExclTax
                    );
                }
            }
            return 0;
        } else {
            return $this->getCommissionAmountForItemByAmount(
                $shipmentTotal,
                $orderItem->getData('commission_amount'),
                $orderItem->getData('row_total'),
                $orderItem->getData('discount_amount_excl_tax')
            );
        }
    }

    /**
     * Get commission amount for item by amount
     *
     * @param $amount
     * @param $commissionAmount
     * @param $itemAmount
     * @param $discountAmountExclTax
     * @return float|int
     */
    public function getCommissionAmountForItemByAmount($amount, $commissionAmount, $itemAmount, $discountAmountExclTax)
    {
        /*item amount after discount*/
        $itemAmount -= $discountAmountExclTax;

        if ($commissionAmount > 0 && $itemAmount > 0) {
            return round($amount * $commissionAmount / $itemAmount);
        }

        return 0;
    }

    /**
     * Get commission amount of this order item which can be returned
     *
     * @param $rma
     * @param $orderItem
     * @return int
     */
    public function getRemainingOrderItemCommissionAmountForReturn($rma, $orderItem)
    {
        if ($orderItem->getData('commission_amount') > 0) {
            /*list of order item*/
            $orderItemId = [];

            if ($orderItem->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $orderItemId = $this->getListChildrenItemIdByParentId($orderItem->getId());
            } else {
                array_push($orderItemId, $orderItem->getId());
            }

            if ($orderItemId) {
                /*get commission amount has been returned for order item list*/
                $returnedCommissionAmount = $this->getReturnedCommissionAmountForOrderItemList($orderItemId, $rma);

                if ($returnedCommissionAmount < $orderItem->getData('commission_amount')) {
                    return $orderItem->getData('commission_amount') - $returnedCommissionAmount;
                }
            }
        }

        return 0;
    }

    /**
     * Get list children item id by parent id
     *
     * @param $parentItemId
     * @return array
     */
    public function getListChildrenItemIdByParentId($parentItemId)
    {
        /*get sales connection*/
        $salesConnection = $this->_connectionHelper->getSalesConnection();

        /*order item table*/
        $orderItemTable = $salesConnection->getTableName('sales_order_item');

        /*get list children item query*/
        $queryBuilder = $salesConnection->select(['item_id'])->from(
            $orderItemTable
        )->where(
            'parent_item_id = ?',
            $parentItemId
        );

        return $salesConnection->fetchCol($queryBuilder);
    }

    /**
     * Get total commission amount has been returned for order item
     *      exclude this rma item
     * @param array $orderItemId
     * @param object $rma
     * @return int
     */
    public function getReturnedCommissionAmountForOrderItemList($orderItemId, $rma)
    {
        /*get default connection*/
        $defaultConnection = $this->_connectionHelper->getDefaultConnection();

        /*rma item table*/
        $rmaItemTable = $defaultConnection->getTableName('magento_rma_item_entity');

        /*count total return commission amount*/
        $queryBuilder = $defaultConnection->select(
            ['SUM( commission_amount ) as commission_amount']
        )->from(
            $rmaItemTable
        )->where(
            'order_item_id IN (?)',
            implode(',', $orderItemId)
        );

        /*exclude this rma item id*/
        if (!empty($rma->getId())) {
            $queryBuilder->where(
                'rma_entity_id NOT IN (?)',
                $rma->getId()
            );
        }

        $queryBuilder->join(
            ['rma' => $defaultConnection->getTableName('magento_rma')],
            $rmaItemTable.'.rma_entity_id = rma.entity_id',
            []
        )->where(
            'rma.status != "closed"'
        );

        $commissionAmount = $defaultConnection->fetchRow($queryBuilder);

        if ($commissionAmount) {
            return $commissionAmount['commission_amount'];
        }

        return 0;
    }

    /**
     * @param MageOrder $order
     * @return array
     */
    public function getInitialOrderStatus(\Magento\Sales\Model\Order $order)
    {
        $paymentMethod = $order->getPayment()->getMethod();

        switch ($paymentMethod) {
            case \Bluecom\Paygent\Model\Paygent::CODE:
                if ($order->getStatus() == OrderStatusResourceModel::STATUS_ORDER_NOT_SHIPPED) {
                    $newStatus = OrderStatusResourceModel::STATUS_ORDER_NOT_SHIPPED;
                    $newState = MageOrder::STATE_PROCESSING;
                } else {
                    $newStatus = OrderStatusResourceModel::STATUS_ORDER_PENDING_CC;
                    $newState = MageOrder::STATE_PENDING_PAYMENT;
                }
                break;
            case \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE:
                $newStatus = OrderStatusResourceModel::STATUS_ORDER_PENDING_CVS;
                $newState = MageOrder::STATE_NEW;
                break;
            case \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD:
                $newStatus = OrderStatusResourceModel::STATUS_ORDER_NOT_SHIPPED;
                $newState = MageOrder::STATE_PROCESSING;
                break;
            case \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_INVOICED:
                if ($order->getGrandTotal() >= \Riki\Sales\Model\OrderCutoffDate::SUSPICIOUS_VALUE) {
                    $newStatus = OrderStatusResourceModel::STATUS_ORDER_SUSPICIOUS;
                    $newState = MageOrder::STATE_PROCESSING;
                } else {
                    $newStatus = OrderStatusResourceModel::STATUS_ORDER_NOT_SHIPPED;
                    $newState = MageOrder::STATE_PROCESSING;
                }
                break;
            case \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE: //shopping point
                $newStatus = OrderStatusResourceModel::STATUS_ORDER_NOT_SHIPPED;
                $newState = MageOrder::STATE_PROCESSING;
                break;
            case \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE:
                $newStatus = OrderStatusResourceModel::STATUS_ORDER_PENDING_NP;
                $newState = MageOrder::STATE_NEW;
                break;
            default: // free of charge
                $newStatus = OrderStatusResourceModel::STATUS_ORDER_NOT_SHIPPED;
                $newState = MageOrder::STATE_PROCESSING;
                break;
        }

        /**
         * Do not set status PENDING_CRD_REVIEW for import order csv
         */
        if ($order->getData('original_unique_id') != ''
            || $order->getData('is_csv_import_order_flag')
        ) {
            return [
                'status' => $newStatus,
                'state' => $newState
            ];
        }

        /**
         * Ticket 9144
         * Performance api
         * Don't process for machine api
         */

        if ($order->getOrderChannel() != \Riki\Sales\Model\Config\Source\OrderChannel::ORDER_CHANEL_TYPE_MACHINE_API) {
            /*hold paygent with pending_cc status, do not need to check pending_crd_review status*/
            if ($paymentMethod != \Bluecom\Paygent\Model\ConfigProvider::PAYGENT_CODE) {
                /*flag to check this order that need to review by call center*/
                $crdReview = $this->isCrdReviewOrder($order);

                /*crd review case*/
                if ($crdReview) {
                    /*flag to check this order is fraud order*/
                    $isFraudOrder = $this->isFraudOrder($order);

                    /*
                     * if this order is crd review and fraud order
                     *      change status to suspicious
                     *      ( will be change to pending crd review after remove suspicious by call center(manually from
                     * BO) )
                     */
                    if ($isFraudOrder) {
                        $newStatus = OrderStatusResourceModel::STATUS_ORDER_SUSPICIOUS;
                        $newState = MageOrder::STATE_PROCESSING;
                    } else {
                        /*is not fraud order - change status to pending crd review*/
                        $newStatus = OrderStatusResourceModel::STATUS_ORDER_PENDING_CRD_REVIEW;
                        $newState = MageOrder::STATE_HOLDED;
                    }
                }
            }
        }
        return [
            'status' => $newStatus,
            'state' => $newState
        ];
    }

    /**
     * @param MageOrder $order
     * @return int
     */
    public function getIssueReceipPrint(\Magento\Sales\Model\Order $order)
    {
        $paymentMethod = $order->getPayment()->getMethod();
        $criteria = $this->_searchBuilder->addFilter('order_id', $order->getId())->create();
        $shipmentCollection = $this->_shipmentRepository->getList($criteria);
        $counter = $shipmentCollection->getTotalCount();
        $created = 0;
        $exported = 0;
        $shippedOut = 0;
        $deliveryComplete = 0;
        $rejected = 0;
        $cancelled = 0;
        // Default : Hidden 'Issue receipt' if order has no shipment
        $issueNumber = 4;

        if ($shipmentCollection->getSize()) {
            foreach ($shipmentCollection->getItems() as $shipment) {
                switch ($shipment->getShipmentStatus()) {
                    case ShipmentStatus::SHIPMENT_STATUS_CREATED:
                        $created++;
                        break;
                    case ShipmentStatus::SHIPMENT_STATUS_EXPORTED:
                        $exported++;
                        break;
                    case ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT:
                        $shippedOut++;
                        break;
                    case ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED:
                        $deliveryComplete++;
                        break;
                    case ShipmentStatus::SHIPMENT_STATUS_REJECTED:
                        $rejected++;
                        break;
                    case ShipmentStatus::SHIPMENT_STATUS_CANCEL:
                        $cancelled++;
                        break;
                }
            }

            switch ($paymentMethod) {
                case \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE:
                case \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE:
                case \Bluecom\Paygent\Model\Paygent::CODE:
                    if ($created || $exported || $shippedOut || $cancelled) {
                        $issueNumber = 1;
                    } elseif ($rejected == $counter) {
                        $issueNumber = 2;
                    } else {
                        $issueNumber = $this->canPrintReceiptOrderCC($order);
                    }
                    break;
                case NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE:
                    if ($created || $exported || $shippedOut) {
                        $issueNumber = 1;
                    } elseif ($rejected == $counter) {
                        $issueNumber = 2;
                    } elseif ($cancelled) {
                        $issueNumber = 4;
                    } else {
                        $issueNumber = $this->canPrintReceiptOrderCC($order);
                    }
                    break;
                case \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE:
                default: //free, shopping point
                    $issueNumber = 4;
                    break;
            }
        }

        return $issueNumber;
    }

    /**
     * Check can print receipt for delay payment
     *
     * @param MageOrder $order
     * @return int
     */
    private function canPrintReceiptOrderCC(\Magento\Sales\Model\Order $order)
    {
        $issueNumber = 3; // can print receipt
        if ($order->getRikiType() == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT) {
            if (!$order->hasInvoices()) {
                $issueNumber = 2;
            }
        }
        return $issueNumber;
    }

    /**
     * @param $customerId
     * @return bool
     */
    public function isCustomerMembershipAmbassador($customerId)
    {
        $customer = $this->_customerRepository->getById($customerId);
        $membershipRaw = $customer->getCustomAttribute('membership')->getValue();
        $memberships  = explode(',', $membershipRaw);
        if ($memberships && in_array(3, $memberships)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param MageOrder $order
     * @param $receiptNumber
     * @return \Magento\Framework\Phrase|string
     */
    public function getReceiptName(\Magento\Sales\Model\Order $order, $receiptNumber)
    {
        $customerId = $order->getCustomerId();
        $customer = $this->_customerRepository->getById($customerId);
        $receiptName = '';
        $firstName = $customer->getFirstname();
        $lastName = $customer->getLastname();
        $consumerDbIdAttribute = $customer->getCustomAttribute('consumer_db_id');
        if (isset($consumerDbIdAttribute)) {
            $consumerDbResponse = $this->_rikiCustomerRepository->prepareAllInfoCustomer(
                $consumerDbIdAttribute->getValue()
            );
        }
        $companyName = '';
        if ($customer->getCustomAttribute('customer_company_name')) {
            $companyName = $customer->getCustomAttribute('customer_company_name')->getValue();
        }
        $ambCompanyName = '';
        if ($customer->getCustomAttribute('amb_com_name')) {
            $ambCompanyName = $customer->getCustomAttribute('amb_com_name')->getValue();
        }
        $companyDeptName = '';
        if ($customer->getCustomAttribute('amb_com_division_name')) {
            $companyDeptName = $customer->getCustomAttribute('amb_com_division_name')->getValue();
        }
        $companyPersonCharge = '';
        if (isset($consumerDbResponse['amb_api']['CHARGE_PERSON']) &&
            $consumerDbResponse['amb_api']['CHARGE_PERSON'] != null
        ) {
            $companyPersonCharge = $consumerDbResponse['amb_api']['CHARGE_PERSON'];
        }
        switch ($receiptNumber) {
            //Last Name & " " & First Name + " " + "様"
            case 1:
                $receiptName = __('Receipt name 1', $lastName, $firstName);
                break;
            //Company Name (if provided) & " " & Last Name & " " & First Name + " " + "様"
            case 2:
                $receiptName = __('Receipt name 2', $companyName, $lastName, $firstName);
                break;
            //Last Name & " " & First Name + " " + "様"
            case 3:
                $receiptName = __('Receipt name 1', $lastName, $firstName);
                break;
            //AMB Company Name (if provided) & " " & Last Name & " " & First Name + " " + "様"
            case 4:
                $receiptName = __('Receipt name 2', $ambCompanyName, $lastName, $firstName);
                break;
            //AMB Company Name
            case 5:
                $receiptName = __('Receipt name 3', $ambCompanyName);
                break;
            //AMB Company Name & " " & AMB Dept Name & " " & Name of Person in charge of Company.
            case 6:
                $receiptName = __('Receipt name 4', $ambCompanyName, $companyDeptName, $companyPersonCharge);
                break;
            //AMB Company Name & " " & Name of Person in charge of Company.
            case 7:
                $receiptName = __('Receipt name 5', $ambCompanyName, $companyPersonCharge);
                break;
        }
        return $receiptName;
    }

    /**
     * @param MageOrder $order
     * @param $receiptNumber
     * @return \Magento\Framework\Phrase|string
     */
    public function getReceiptNamePrint(\Magento\Sales\Model\Order $order)
    {
        $customerId = $order->getCustomerId();
        $customer = $this->_customerRepository->getById($customerId);
        $receiptName = [];
        $firstName = $customer->getFirstname();
        $lastName = $customer->getLastname();
        $consumerDbIdAttribute = $customer->getCustomAttribute('consumer_db_id');
        if (isset($consumerDbIdAttribute)) {
            $consumerDbResponse = $this->_rikiCustomerRepository->prepareAllInfoCustomer(
                $consumerDbIdAttribute->getValue()
            );
        }

        $companyName = '';
        if ($customer->getCustomAttribute('customer_company_name')) {
            $companyName = $customer->getCustomAttribute('customer_company_name')->getValue();
        }
        $ambCompanyName = '';
        if ($customer->getCustomAttribute('amb_com_name')) {
            $ambCompanyName = $customer->getCustomAttribute('amb_com_name')->getValue();
        }
        $companyDeptName = '';
        if ($customer->getCustomAttribute('amb_com_division_name')) {
            $companyDeptName = $customer->getCustomAttribute('amb_com_division_name')->getValue();
        }
        $companyPersonCharge = '';
        if (isset($consumerDbResponse['amb_api']['CHARGE_PERSON']) &&
            $consumerDbResponse['amb_api']['CHARGE_PERSON'] != null
        ) {
            $companyPersonCharge = $consumerDbResponse['amb_api']['CHARGE_PERSON'];
        }
        $customerType = $this->isCustomerMembershipAmbassador($customerId);
        if ($customerType) {
            $receiptName[3] = __('Receipt name 1', $lastName, $firstName);

            //AMB Company Name
            if ($ambCompanyName) {
                //AMB Company Name (if provided) & " " & Last Name & " " & First Name + " " + "様"
                $receiptName[4] = __('Receipt name 2', $ambCompanyName, $lastName, $firstName);
                $receiptName[5] = __('Receipt name 3', $ambCompanyName);
            }
            //AMB Company Name & " " & AMB Dept Name & " " & Name of Person in charge of Company.
            if ($ambCompanyName && $companyDeptName) {
                $receiptName[6] = __('Receipt name 4', $ambCompanyName, $companyDeptName, $companyPersonCharge);
            }
            //AMB Company Name & " " & Name of Person in charge of Company.
            if ($ambCompanyName || $companyPersonCharge) {
                $receiptName[7] = __('Receipt name 5', $ambCompanyName, $companyPersonCharge);
            }
        } else {
            //Last Name & " " & First Name + " " + "様"

                $receiptName[] = __('Receipt name 1', $lastName, $firstName);

            //Company Name (if provided) & " " & Last Name & " " & First Name + " " + "様"
            if ($companyName) {
                $receiptName[] = __('Receipt name 2', $companyName, $lastName, $firstName);
            }
        }

        return $receiptName;
    }

    /**
     * @param MageOrder $order
     * @return string
     */
    public function getOrderShippedOutDate(\Magento\Sales\Model\Order $order)
    {
        $criteria = $this->_searchBuilder->addFilter('order_id', $order->getId())->create();
        $shipmentCollection = $this->_shipmentRepository->getList($criteria);
        $shippedOutDate = [];
        foreach ($shipmentCollection->getItems() as $shipment) {
            $sdate = $shipment->getData('shipped_out_date');
            if ($sdate) {
                $shippedOutDate[] = date('Y/m/d', strtotime($sdate));
            }
        }
        return implode(' . ', $shippedOutDate);
    }

    /**
     * @param array $data
     * @return int
     */
    public function increaseReceiptNumberPrinting(array $data)
    {
        $order = $this->_orderRepository->get($data['order_id']);
        $currentPrintingNumber = $order->getData('receipt_counter');
        if (!$currentPrintingNumber) {
            $initialCounter = $this->getInitialReceiptNumberFrontend($order);
            $currentPrintingNumber = $initialCounter + 1;
        } else {
            $currentPrintingNumber += 1;
        }
        try {
            $order->setData('receipt_counter', $currentPrintingNumber)->save();
        } catch (\Exception $e) {
            $this->_logger->info(__('Can not increase receipt number of invoice printing'));
            $this->_logger->critical($e);
        }
        return $currentPrintingNumber;
    }

    /**
     * @param MageOrder $order
     * @return int
     */
    public function getInitialReceiptNumberFrontend(\Magento\Sales\Model\Order $order)
    {
        //retreive payment method
        if ($order->getPayment()) {
            $paymentMethod = $order->getPayment()->getMethod();
        } else {
            $paymentMethod = '';
        }
        //get shipment
        $shipmentCriteria = $this->_searchBuilder->addFilter('order_id', $order->getId())->create();
        $shipmentCollection = $this->_shipmentRepository->getList($shipmentCriteria);
        $multiShipping = $this->isMultipleShippingOrder($order->getId());
        $addressTypes = ['home','company'];
        $shippingAddressType = false;
        foreach ($shipmentCollection->getItems() as $shipment) {
            $shippingAddress = $this->_orderAddressRepository->get($shipment->getShippingAddressId());
            if (in_array(strtolower($shippingAddress->getRikiTypeAddress()), $addressTypes)) {
                $shippingAddressType = true;
            }
        }
        $initialReceiptCounter = 0;
        switch ($paymentMethod) {
            case \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE:
                break;
            case \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE:
                break;
            case \Bluecom\Paygent\Model\Paygent::CODE:
                break;
            case \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE:
                if ($shippingAddressType && !$multiShipping) {
                    $initialReceiptCounter = 1;
                } else {
                    $initialReceiptCounter = 0;
                }
                break;
            default:
                $initialReceiptCounter = 0;
                break;
        }
        return $initialReceiptCounter;
    }

    public function isMultipleShippingOrder($orderId)
    {
        $criterial = $this->_searchBuilder->addFilter('parent_id', $orderId)
                     ->addFilter('address_type', 'shipping')
                     ->create();
        $addressCollection = $this->_orderAddressRepository->getList($criterial);
        $addressIds = [];
        if ($addressCollection->getTotalCount()) {
            foreach ($addressCollection->getItems() as $orderAddress) {
                if (!in_array($orderAddress->getCustomerAddressId(), $addressIds)) {
                    $addressIds[] = $orderAddress->getCustomerAddressId();
                }
            }
            if (count($addressIds) > 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param MageOrder $order
     * @return array
     */
    public function getOrderTotals(\Magento\Sales\Model\Order $order)
    {
        $this->_totals = [];
        //wrapping fee
        $this->_totals['wrapping_fee'] = new \Magento\Framework\DataObject(
            [
                'code' => 'wrapping',
                'field' => 'wrapping',
                'value' => $order->getData('gw_items_price_incl_tax'),
                'label' => __('print invoice wrapping fee'),
            ]
        );
        /**
         * Add shipping
         */
        if (!$order->getIsVirtual() && ((double)$order->getShippingAmount() || $order->getShippingDescription())) {
            $this->_totals['shipping'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'shipping',
                    'field' => 'shipping_amount',
                    'value' => $order->getShippingAmount() + $order->getShippingTaxAmount(),
                    'label' => __('Shipment Fee'),
                ]
            );
        }

        /**
         * Add discount
         */
        if ((double)$order->getDiscountAmount() != 0) {
            if ($order->getDiscountDescription()) {
                $discountLabel = __('Discount Print (%1)', $order->getDiscountDescription());
            } else {
                $discountLabel = __('Discount Print');
            }
            $this->_totals['discount'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'discount',
                    'field' => 'discount_amount',
                    'value' => $order->getDiscountAmount(),
                    'label' => $discountLabel,
                ]
            );
        }
        $this->_totals['payment_fee'] = new \Magento\Framework\DataObject(
            [
                'code' => 'payment_fee',
                'field' => 'payment_fee',
                'strong' => true,
                'value' => $order->getFee(),
                'label' => __('print invoice payment fee'),
            ]
        );
        $this->_totals['point'] = new \Magento\Framework\DataObject(
            [
                'code' => 'point',
                'field' => 'point',
                'strong' => true,
                'value' => $order->getUsedPoint(),
                'label' => __('print invoice used point'),
            ]
        );
        $totalAmountInclTax8Percent = $this->_getTotalProductAmountByTax($order, self::TAX_8_PERCENT);
        $totalAmountInclTax10Percent = $this->_getTotalProductAmountByTax($order, self::TAX_10_PERCENT);
        $usedPoint8Percent = 0;
        if (($totalAmountInclTax8Percent + $totalAmountInclTax10Percent) > 0) {
            $usedPoint8Percent = floor($order->getUsedPoint() *
                $totalAmountInclTax8Percent /
                ($totalAmountInclTax8Percent + $totalAmountInclTax10Percent + $order->getData('gw_items_price_incl_tax')
                 + $order->getData('shipping_incl_tax') + $order->getFee()));
        }
        $usedPoint10Percent = $order->getData('used_point_amount') - $usedPoint8Percent;
        $totalAmountInclTax8Percent -= $usedPoint8Percent;

        $totalAmountInclTax10Percent = $totalAmountInclTax10Percent
            + $order->getData('gw_items_price_incl_tax') //wrapping fee
            + $order->getData('shipping_incl_tax') //shipping fee
            + $order->getFee() // payment fee
            - $usedPoint10Percent;

        $this->_totals['usedpoint_8_percent'] = new \Magento\Framework\DataObject(
            [
                'code' => 'usedpoint_8_percent',
                'field' => 'usedpoint_8_percent',
                'strong' => true,
                'value' => $usedPoint8Percent,
                'label' => __('(Reduced tax rate 8% target used point)'),
            ]
        );
        $this->_totals['usedpoint_10_percent'] = new \Magento\Framework\DataObject(
            [
                'code' => 'usedpoint_10_percent',
                'field' => 'usedpoint_10_percent',
                'strong' => true,
                'value' => $usedPoint10Percent,
                'label' => __('(Reduced tax rate 10% target used point)'),
            ]
        );
        $this->_totals['grand_total'] = new \Magento\Framework\DataObject(
            [
                'code' => 'grand_total',
                'field' => 'grand_total',
                'strong' => true,
                'value' => $order->getGrandTotal(),
                'label' => __('Grand Total'),
            ]
        );
        $this->_totals['grand_total8_percent'] = new \Magento\Framework\DataObject(
            [
                'code' => 'grand_total8_percent',
                'field' => 'grand_total8_percent',
                'strong' => true,
                'value' => $totalAmountInclTax8Percent,
                'label' => __('(Standard tax rate 8% target)'),
            ]
        );
        $this->_totals['grand_total10_percent'] = new \Magento\Framework\DataObject(
            [
                'code' => 'grand_total10_percent',
                'field' => 'grand_total10_percent',
                'strong' => true,
                'value' => $totalAmountInclTax10Percent,
                'label' => __('(Standard tax rate 10% target)'),
            ]
        );
        /**
         * Base grandtotal
         */
        if ($order->isCurrencyDifferent()) {
            $this->_totals['base_grandtotal'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'base_grandtotal',
                    'value' => $order->formatPrice($order->getGrandTotal()),
                    'label' => __('Grand Total to be Charged'),
                    'is_formated' => true,
                ]
            );
        }
        return $this->_totals;
    }

    /**
     * @param $orderId
     * @return int
     */
    public function retreiveReceiptCounter($orderId)
    {
        try {
            $order = $this->_orderRepository->get($orderId);
            return $order->getData('receipt_counter');
        } catch (\Exception $e) {
            $this->_logger->info('Could not load order: '.$orderId);
            $this->_logger->critical($e);
            return 0;
        }
    }

    /**
     * get commission amount for bundle children item
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param $itemQty
     * @return float|int
     */
    public function getCommissionAmountForBundleChildrenItem(
        \Magento\Sales\Model\Order\Item $orderItem,
        $itemQty
    ) {
        /*recalculate some field for bundle children item*/
        $orderItemAfterReCalculate = $this->bundleItemsHelper->reCalculateOrderItem($orderItem);

        /*order item - commission amount*/
        $commissionAmount = $orderItemAfterReCalculate->getCommissionAmount();

        /*commission amount for bundle item*/
        $orderItemQtyOrdered = $orderItemAfterReCalculate->getQtyOrdered();

        if ($commissionAmount) {
            if ($itemQty != $orderItemQtyOrdered) {
                $commissionAmount = $this->reCalculateCommissionAmountForSpecifiedQty(
                    $commissionAmount,
                    $orderItemQtyOrdered,
                    $itemQty
                );
            }
        }

        return $commissionAmount;
    }

    /**
     * re calculate commission amount for specified qty
     *
     * @param $originalCommissionAmount
     * @param $originalQty
     * @return float|int
     */
    protected function reCalculateCommissionAmountForSpecifiedQty($originalCommissionAmount, $originalQty, $qty)
    {
        $commission = 0;

        if ($originalCommissionAmount) {
            if ($qty >= $originalQty) {
                $commission = $originalCommissionAmount;
            } else {
                if ($qty > 0 && $originalQty > 0) {
                    $commission = floor(
                        $originalCommissionAmount * $qty / $originalQty
                    );
                }
            }
        }

        return $commission;
    }

    /**
     * Get delivery information of stock point order
     *
     * @param $orderNumber
     * @return array
     */
    public function getStockPointDeliveryOrderInfo($orderNumber)
    {
        $requestData = ['magento_order_id' => $orderNumber];
        return $this->apiBuildStockPointPostData->callApiGetStockPointDeliveryStatus($requestData);
    }

    /**
     * Get stock point delivery status url api
     *
     * @return mixed
     */
    public function getStockPointDeliveryStatusApiUrl()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::XML_PATH_STOCK_POINT_GET_DELIVERY_STATUS_URL, $storeScope);
    }

    /**
     * Get request value for Delivery Status API
     *
     * @param $orderNumber
     * @return string
     */
    public function getRequestDataValueForDeliveryStatus($orderNumber)
    {
        $rawDataValue = ['magento_order_id' => $orderNumber,
                       'sectime' => date(DATE_ISO8601)];
        try {
            return $this->apiBuildStockPointPostData->buildDataWithOpenSSL($rawDataValue);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->_logger->info(__('Cannot build SAML request data for order: %1', $orderNumber));
            $this->_logger->info($exception->getMessage());
        }
    }

    /**
     * get order by id
     *
     * @param $orderId
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrderById($orderId)
    {
        try {
            return $this->_orderRepository->get($orderId);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }

        return false;
    }

    /**
     * Order is delay payment order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isDelayPaymentOrder(\Magento\Sales\Model\Order $order)
    {
        if (strtoupper($order->getRikiType()) == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT) {
            return true;
        }
        return false;
    }

    /**
     * Delay payment order is allowed to created new returns
     *      logic: just allowed create new return after this order is captured
     *
     * @param MageOrder $order
     * @return bool
     */
    public function isDelayPaymentOrderAllowedReturn(\Magento\Sales\Model\Order $order)
    {
        /*always return false if this is not delay payment order*/
        if (!$this->isDelayPaymentOrder($order)) {
            return false;
        }

        /*for the case that delay order has not captured yet.*/
        if ($order->getState() != \Magento\Sales\Model\Order::STATE_COMPLETE) {
            return false;
        }

        return true;
    }

    /**
     * @param MageOrder $order
     * @param int $taxPercent
     * @return float|int|null
     */
    private function _getTotalProductAmountByTax(\Magento\Sales\Model\Order $order, int $taxPercent)
    {
        $orderItems = $order->getItems();
        $totalProductAmount = 0;
        if ($orderItems) {
            foreach ($orderItems as $orderItem) {
                if ((int)$orderItem->getTaxPercent() == $taxPercent) {
                    $totalProductAmount += $orderItem->getRowTotalInclTax();
                }
            }
        }
        return $totalProductAmount;
    }

    /**
     * @param MageOrder\Shipment $shipment
     * @return array
     */
    public function splitShipmentAmountsByTaxPercent(
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        $usedPoint = $shipment->getShoppingPointAmount();
        $totalAmountTax8Percent = 0;
        $totalTaxAmountTax8percent = 0;
        $totalAmountTax10Percent = 0;
        $totalTaxAmountTax10percent = 0;
        $totalDiscountAmount8Percent = 0;
        $totalDiscountAmount10Percent = 0;
        $shipmentItems = $shipment->getItems();
        if ($shipmentItems) {
            foreach ($shipmentItems as $shipmentItem) {
                $orderItem = $this->getOrderItemById($shipmentItem->getOrderItemId());
                if ($orderItem) {

                    $discountAmount = round($orderItem->getDiscountAmount() * $shipmentItem->getQty() / $orderItem->getQtyOrdered());
                    $rowTotalAmount = $shipmentItem->getPrice() * $shipmentItem->getQty();
                    $rowTotalTaxAmount = floor($shipmentItem->getPrice() * $orderItem->getTaxPercent() / 100) * $shipmentItem->getQty();

                    switch ((int)$orderItem->getTaxPercent()) {
                        case self::TAX_8_PERCENT:
                            $totalAmountTax8Percent += $rowTotalAmount;
                            $totalTaxAmountTax8percent += $rowTotalTaxAmount;
                            $totalDiscountAmount8Percent += $discountAmount;
                            break;
                        case self::TAX_10_PERCENT:
                        default:
                            $totalAmountTax10Percent += $rowTotalAmount;
                            $totalTaxAmountTax10percent += $rowTotalTaxAmount;
                            $totalDiscountAmount10Percent += $discountAmount;
                    }
                }
            }
        }

        $totalAmountTax8Percent += $totalTaxAmountTax8percent - $totalDiscountAmount8Percent;
        $totalAmountTax10Percent += $totalTaxAmountTax10percent - $totalDiscountAmount10Percent;

        $totalAmount = $totalAmountTax8Percent + $totalAmountTax10Percent;
        $totalAmount += $shipment->getData('gw_price') + $shipment->getData('gw_tax_amount') + $shipment->getData('shipment_fee') + $shipment->getData('payment_fee');

        $usedPointTax8Percent = 0;
        if ($totalAmount > 0) {
            $usedPointTax8Percent = floor(($usedPoint * $totalAmountTax8Percent) / $totalAmount);
        }
        if ($totalAmountTax10Percent < 0) {
            $totalAmountTax10Percent = 0;
        }
        $usedPointTax10Percent = $usedPoint - $usedPointTax8Percent;

        //wrapping fee
        $totalAmountTax10Percent += $shipment->getData('gw_price') + $shipment->getData('gw_tax_amount');
        $totalTaxAmountTax10percent += $shipment->getData('gw_tax_amount');
        //shipping fee
        $totalAmountTax10Percent += $shipment->getData('shipment_fee');
        $totalTaxAmountTax10percent += $this->_getTaxAmountFromInclTaxAmount(
            self::TAX_10_PERCENT,
            $shipment->getData('shipment_fee')
        );
        //payment fee
        $totalAmountTax10Percent += $shipment->getData('payment_fee');
        $totalTaxAmountTax10percent += $this->_getTaxAmountFromInclTaxAmount(
            self::TAX_10_PERCENT,
            $shipment->getData('payment_fee')
        );
        $totalAmountTax8Percent -= $usedPointTax8Percent;
        if ($totalAmountTax8Percent < 0) {
            $totalAmountTax8Percent = 0;
        }
        $totalAmountTax10Percent -= $usedPointTax10Percent;
        if ($totalAmountTax10Percent < 0) {
            $totalAmountTax10Percent = 0;
        }
        return [
            $totalAmountTax8Percent,
            $totalAmountTax10Percent,
            $usedPointTax8Percent,
            $usedPointTax10Percent,
            $totalTaxAmountTax8percent,
            $totalTaxAmountTax10percent,
        ];
    }

    /**
     * @param MageOrder $order
     * @return float
     */
    public function getRowsTotal(\Magento\Sales\Model\Order $order)
    {
        $orderItems = $order->getItems();
        $rowsTotal = 0;
        if ($orderItems) {
            foreach ($orderItems as $orderItem) {
                $rowsTotal += $orderItem->getRowTotalInclTax();
            }
        }
        return $rowsTotal;
    }

    /**
     * @param int $taxPercent
     * @param float $inclTaxAmount
     * @return float
     */
    private function _getTaxAmountFromInclTaxAmount(int $taxPercent, float $inclTaxAmount)
    {
        return floor($inclTaxAmount / ($taxPercent + 100) * $taxPercent);
    }

    /**
     * Check order atobarai
     * @param MageOrder $order
     * @return bool
     */
    public function isOrderNpAtobarai(\Magento\Sales\Model\Order $order)
    {
        $payment = $order->getPayment();
        if ($payment && $payment->getMethod() == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
            return true;
        }
        return false;
    }
}
