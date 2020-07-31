<?php

namespace Riki\Rma\Helper;

use Riki\Rma\Model\Rma as RmaModel;
use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;
use Riki\Loyalty\Model\Reward;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Magento\OfflinePayments\Model\Cashondelivery;
use Riki\Rma\Api\Data\Reason\DuetoInterface;
use Riki\Loyalty\Model\Reward as LoyaltyReward;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Sales\Model\Order\PaymentMethod as RikiPaymentMethod;
use Magento\Rma\Model\Rma\Source\Status as RmaStatus;
use \Riki\NpAtobarai\Model\Payment\NpAtobarai;
use \Riki\NpAtobarai\Model\Config\Source\TransactionPaymentStatus as NpTransactionPaymentStatus;

class Amount extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $orderItems = [];

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $_filterBuilder;
    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $_loyaltyManagement;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;
    /**
     * @var
     */
    protected $_customer;

    /**
     * @var
     */
    protected $_orderItemRepository;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $_shipmentRepository;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    /**
     * @var \Magento\Rma\Model\ResourceModel\Item
     */
    protected $_rmaItemResource;
    /**
     * @var array
     */
    protected $_returnableItemsQty = [];
    /**
     * @var array
     */
    protected $_rmaToShipment = [];

    /**
     * @var array
     */
    protected $_rmaReturnAmount = [];
    /**
     * @var array
     */
    protected $_rmaReturnAmountItems = [];
    /**
     * @var array
     */
    protected $_rmaEarnedPoints = [];

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $_rikiSalesHelper;
    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_loyaltyHelper;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $paymentFeeDataHelper;

    /**
     * @var \Riki\Rma\Model\Repository\RmaRepository
     */
    protected $rmaRepository;

    /**
     * @var \Riki\Loyalty\Model\ResourceModel\Reward\CollectionFactory
     */
    protected $rewardCollectionFactory;

    /**
     * @var array
     */
    protected $reasonCodeNotAllowedRefund = [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 41, 44, 45, 51];

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * @var RmaModel\BundleItem
     */
    protected $rmaBundleItem;

    /**
     * @var \Riki\NpAtobarai\Model\TransactionRepository
     */
    protected $npTransactionRepository;

    /**
     * @var \Riki\NpAtobarai\Model\Method\Adapter
     */
    protected $npAtobaraiAdapter;

    /**
     * Amount constructor.
     * @param \Riki\Rma\Model\Repository\RmaRepository $rmaRepository
     * @param \Bluecom\PaymentFee\Helper\Data $paymentFeeDataHelper
     * @param Data $dataHelper
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemCollectionFactory
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepositoryInterface
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Rma\Model\ResourceModel\Item $rmaItemResource
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Riki\Sales\Helper\Data $rikiSalesHelper
     * @param \Riki\Loyalty\Helper\Data $loyaltyHelper
     * @param \Riki\Loyalty\Model\RewardManagement $loyaltyManagement
     * @param \Riki\Loyalty\Model\ResourceModel\Reward\CollectionFactory $rewardCollectionFactory
     * @param RmaModel\BundleItem $rmaBundleItem
     */
    public function __construct(
        \Riki\Rma\Model\Repository\RmaRepository $rmaRepository,
        \Bluecom\PaymentFee\Helper\Data $paymentFeeDataHelper,
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemCollectionFactory,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepositoryInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Rma\Model\ResourceModel\Item $rmaItemResource,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Riki\Sales\Helper\Data $rikiSalesHelper,
        \Riki\Loyalty\Helper\Data $loyaltyHelper,
        \Riki\Loyalty\Model\RewardManagement $loyaltyManagement,
        \Riki\Loyalty\Model\ResourceModel\Reward\CollectionFactory $rewardCollectionFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        \Riki\Rma\Model\Rma\BundleItem $rmaBundleItem,
        \Riki\NpAtobarai\Model\TransactionRepository $npTransactionRepository,
        \Riki\NpAtobarai\Model\Method\Adapter $npAtobaraiAdapter
    ) {
        $this->rmaRepository = $rmaRepository;
        $this->dataHelper = $dataHelper;
        $this->searchHelper = $searchHelper;
        $this->functionCache = $functionCache;
        $this->paymentFeeDataHelper = $paymentFeeDataHelper;
        $this->_loyaltyManagement = $loyaltyManagement;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_orderItemRepository = $orderItemCollectionFactory;
        $this->_shipmentRepository = $shipmentRepositoryInterface;
        $this->_connection = $resourceConnection->getConnection();
        $this->_rmaItemResource = $rmaItemResource;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder = $filterBuilder;
        $this->_rikiSalesHelper = $rikiSalesHelper;
        $this->_loyaltyHelper = $loyaltyHelper;
        $this->rewardCollectionFactory = $rewardCollectionFactory;

        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);

        $this->rmaBundleItem = $rmaBundleItem;
        $this->npTransactionRepository = $npTransactionRepository;
        $this->npAtobaraiAdapter = $npAtobaraiAdapter;
        parent::__construct($context);
    }

    /**
     * @return \Riki\Framework\Helper\Cache\FunctionCache
     */
    public function getFunctionCache()
    {
        return $this->functionCache;
    }

    /**
     * @param $name
     * @return mixed
     */
    protected function _getConfig($name)
    {
        return $this->scopeConfig->getValue('rma/return_amount/' . $name);
    }

    /**
     * @param RmaModel $rma
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer(RmaModel $rma)
    {
        try {
            return $this->_customerRepositoryInterface->getById($rma->getOrder()->getCustomerId());
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return Data
     */
    public function getDataHelper()
    {
        return $this->dataHelper;
    }

    /**
     * @param RmaModel $rma
     * @return array
     */
    public function getReturnableItemsQty(RmaModel $rma)
    {

        $rmaId = $rma->getId();

        if (!isset($this->_returnableItemsQty[$rmaId])) {
            $this->_returnableItemsQty[$rmaId] = $this->_rmaItemResource->getReturnableItems($rma->getOrderId());
        }

        return $this->_returnableItemsQty[$rmaId];
    }

    /**
     * @param RmaModel $rma
     * @param bool $static Flag to determine to get point from DB or API
     * @param bool $force
     *
     * @return float|int|mixed|null
     */
    public function getPointsBalance(RmaModel $rma, $static = true, $force = false)
    {
        if (!$force && $this->functionCache->has([$rma->getId()])) {
            return $this->functionCache->load([$rma->getId()]);
        }

        if ($static && in_array($rma->getStatus(), [RmaStatus::STATE_PROCESSED_CLOSED, RmaStatus::STATE_CLOSED])) {
            $pointBalance = intval($rma->getData('customer_point_balance'));
        }

        if (!isset($pointBalance)) {
            $customer = $this->getCustomer($rma);
            $customerCode = 0;
            if ($customer && !empty($customer->getCustomAttribute('consumer_db_id'))) {
                $customerCode = $customer->getCustomAttribute('consumer_db_id')->getValue();
            }

            $pointBalance = (float)$this->_loyaltyManagement->getPointBalance($customerCode);
        }

        $this->functionCache->store($pointBalance, [$rma->getId()]);

        return $pointBalance;
    }

    /**
     * @param RmaModel $rma
     * @return float
     */
    public function getEarnedPoint(RmaModel $rma)
    {
        if ($this->functionCache->has($rma->getId())) {
            return $this->functionCache->load($rma->getId());
        }

        if ($this->isFreeReturn($rma)) {
            return 0;
        }

        $result = $this->getItemConversionEarnedPoints($rma);

        $reason = $this->getReasonByRma($rma);
        $reasonDueTo = $reason ? $reason->getDueTo() : null;

        if (($this->isFullReturn($rma) || $reasonDueTo == DuetoInterface::CONSUMER)
            && $rma->isTriggerCancelPoint()
        ) {
            $result += $this->getCartPromotionEarnedPoints($rma);
        }

        $this->functionCache->store($result, $rma->getId());

        return $result;
    }

    /**
     * get list of order item id and order item parent id
     *
     * @param RmaModel $rma
     * @return array
     */
    public function getOrderItemsIdByRma(RmaModel $rma)
    {

        $result = [];

        $rmaItems = $rma->getItemsForDisplay()->getItems();

        $orderItemIds = [];

        /** @var \Magento\Rma\Model\Item $rmaItem */
        foreach ($rmaItems as $rmaItem) {
            $orderItemIds[$rmaItem->getOrderItemId()] = $rmaItem->getQtyRequested();
        }

        if (count($orderItemIds)) {
            $this->_searchCriteriaBuilder->addFilters(
                [
                    $this->_filterBuilder->setField('order_id')
                        ->setValue($rma->getOrderId())
                        ->setConditionType('eq')
                        ->create()
                ]
            );

            $searchCriteria = $this->_searchCriteriaBuilder->create();
            $orderItems = $this->_orderItemRepository->getList($searchCriteria)->getItems();

            /** @var \Magento\Sales\Api\Data\OrderItemInterface $orderItem */
            foreach ($orderItems as $orderItem) {
                $orderItemId = $orderItem->getItemId();

                if (!isset($orderItemIds[$orderItemId])) {
                    continue;
                }

                $result[$orderItemId] = isset($orderItemIds[$orderItemId]) ? $orderItemIds[$orderItemId] : 0;
            }
        }

        return $result;
    }

    /**
     * @param RmaModel $rma
     * @return mixed
     */
    public function getDetailEarnedPoints(RmaModel $rma)
    {
        if (!isset($this->_rmaEarnedPoints[$rma->getId()])) {
            $orderItemIdsToQtyReturnRequested = $this->getOrderItemsIdByRma($rma);

            $orderItemsEarnedPoint = $this->_loyaltyHelper->getOrderFullPointEarned($rma->getOrderIncrementId());

            $result = [];

            foreach ($orderItemsEarnedPoint as $itemEarnedPointData) {
                $orderItemId = $itemEarnedPointData['order_item_id'];
                if (!in_array($orderItemId, array_keys($orderItemIdsToQtyReturnRequested))
                    && $itemEarnedPointData['level'] == 0
                ) {
                    continue;
                }
                if (!isset($result[$itemEarnedPointData['level']])) {
                    $result[$itemEarnedPointData['level']] = 0;
                }

                $requestedQty = isset($orderItemIdsToQtyReturnRequested[$orderItemId]) ?
                    $orderItemIdsToQtyReturnRequested[$orderItemId] : 1;

                $itemPoints = $itemEarnedPointData['point'] * ($itemEarnedPointData['level'] == LoyaltyReward::LEVEL_ORDER ? 1 : $requestedQty);
                $result[$itemEarnedPointData['level']] += $itemPoints;
            }
            // add earned point of bundle item
            $rmaItems = $rma->getRmaItems();
            foreach ($rmaItems as $rmaItem) {
                if ($rmaItem->getData('bundle_item_earned_point') > 0) {
                    if (!isset($result[LoyaltyReward::LEVEL_ITEM])) {
                        $result[LoyaltyReward::LEVEL_ITEM] = 0;
                    }
                    if ($this->rmaBundleItem->isBundleItemShoppingPoint($rmaItem, $orderItemsEarnedPoint)) {
                        $result[LoyaltyReward::LEVEL_ITEM] += $rmaItem->getData('bundle_item_earned_point');
                    }
                }
            }
            $this->_rmaEarnedPoints[$rma->getId()] = $result;
        }

        return $this->_rmaEarnedPoints[$rma->getId()];
    }

    /**
     * @param RmaModel $rma
     * @return int
     */
    public function getItemConversionEarnedPoints(RmaModel $rma)
    {
        $pointsData = $this->getDetailEarnedPoints($rma);

        return isset($pointsData[LoyaltyReward::LEVEL_ITEM]) ? $pointsData[LoyaltyReward::LEVEL_ITEM] : 0;
    }

    /**
     * @param RmaModel $rma
     * @return int
     */
    public function getCartPromotionEarnedPoints(RmaModel $rma)
    {
        $pointsData = $this->getDetailEarnedPoints($rma);
        return isset($pointsData[LoyaltyReward::LEVEL_ORDER]) ? $pointsData[LoyaltyReward::LEVEL_ORDER] : 0;
    }

    /**
     * @param RmaModel $rma
     * @param bool $static
     *
     * @return float
     */
    public function getRetractablePoints(RmaModel $rma, $static = true)
    {
        return (float)min($this->getEarnedPoint($rma), $this->getPointsBalance($rma, $static));
    }

    /**
     * @param RmaModel $rma
     * @param bool $static
     *
     * @return mixed
     */
    public function getNotRetractablePoints(RmaModel $rma, $static = true)
    {
        return max($this->getEarnedPoint($rma) - $this->getPointsBalance($rma, $static), 0);
    }

    /**
     * @param RmaModel $rma
     * @return float|int
     */
    public function getReturnShippingAmount(RmaModel $rma)
    {
        $checkReasonCodeNp = false;

        if ($this->functionCache->has($rma->getId())) {
            return $this->functionCache->load($rma->getId());
        }

        if ($this->isFreeReturn($rma)) {
            $this->functionCache->store(0, $rma->getId());
            return 0;
        }

        if ($this->dataHelper->canGetReturnShippingFeeFromShipmentFee($rma)) {
            $shipment = $this->dataHelper->getRmaShipment($rma);
            $result = floatval($shipment->getShipmentFee());
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        // check Np-Atobarai reason code
        if ($this->dataHelper->checkNpAtobaraiReasonCode($rma))
        {
            $checkReasonCodeNp = true;
        }

        $reasonFault = $this->dataHelper->getRmaReasonDueTo($rma);
        $order = $this->dataHelper->getRmaOrder($rma);
        $shippingAmount = floatval($order->getShippingInclTax());
        $paymentMethod = $order->getPayment()->getMethod();

        if ($shippingAmount
            && $checkReasonCodeNp
            && $paymentMethod == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ) {
            $result = $shippingAmount;
            $this->functionCache->store($result, $rma->getId());
            return  $result;
        }

        if ($shippingAmount
            && $rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::FULL
            && $reasonFault == DuetoInterface::NESTLE
        ) {
            $result = floatval($order->getShippingInclTax());
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if ($shippingAmount
            && ($rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::FULL)
            && $reasonFault == DuetoInterface::CONSUMER
        ) {
            $result = 0;
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if ($shippingAmount
            && ($rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::PARTIAL)
        ) {
            $result = 0;
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if (!$shippingAmount
            && ($rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::FULL)
        ) {
            $result = 0;
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if (!$shippingAmount
            && ($rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::PARTIAL)
            && $reasonFault == DuetoInterface::NESTLE
        ) {
            $result = 0;
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if (!$shippingAmount
            && ($rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::PARTIAL)
            && $reasonFault == DuetoInterface::CONSUMER
        ) {
            if (($this->getReturnableItemsTotalAmount($rma) < (float)$this->_getConfig('remaining_amount_limit'))) {
                $result = (float)$this->_getConfig('shipment_fees_with_remaining');
            } else {
                $result = 0;
            }

            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        $this->functionCache->store(0, $rma->getId());
        return 0;
    }

    /**
     * @param RmaModel $rma
     * @return bool
     */
    public function isFullReturn(RmaModel $rma)
    {

        return $rma->getFullPartial() == \Riki\Rma\Model\Config\Source\Rma\Type::FULL;
    }

    /**
     * @param RmaModel $rma
     * @return float|int
     */
    public function getReturnableItemsTotalAmount(RmaModel $rma)
    {

        $result = 0;

        $returnableItemsQty = $this->getReturnableItemsQty($rma);

        $orderItems = $rma->getOrder()->getAllVisibleItems();

        $hasGw = $this->doCalculateGiftWrappingFee($rma);

        foreach ($orderItems as $orderItem) {
            $itemId = $orderItem->getId();

            if (isset($returnableItemsQty[$itemId])) {
                $result += $returnableItemsQty[$itemId] * (($orderItem->getRowTotal() / $orderItem->getQtyOrdered()));

                if ($hasGw) {
                    $result += ($orderItem->getGwPrice() + $orderItem->getGwTaxAmount()) * $returnableItemsQty[$itemId];
                }
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Rma\Model\Item $rmaItem
     * @return int
     */
    public function getReturnAmountByItem(\Magento\Rma\Model\Item $rmaItem)
    {

        if ($this->isFreeReturn($rmaItem->getRma())) {
            return 0;
        }

        $orderItemId = $rmaItem->getOrderItemId();

        $rmaItemsReturnAmount = $this->getReturnAmountOfItems($rmaItem->getRma());

        if (isset($rmaItemsReturnAmount[$orderItemId])) {
            return $rmaItemsReturnAmount[$orderItemId]['amount'];
        }

        return 0;
    }

    /**
     * @param \Magento\Rma\Model\Item $rmaItem
     * @return int
     */
    public function getReturnWrappingByItem(\Magento\Rma\Model\Item $rmaItem)
    {
        if ($this->isFreeReturn($rmaItem->getRma())) {
            return 0;
        }

        $orderItemId = $rmaItem->getOrderItemId();

        $rmaItemsReturnAmount = $this->getReturnAmountOfItems($rmaItem->getRma());

        if (isset($rmaItemsReturnAmount[$orderItemId])) {
            return (int)$rmaItemsReturnAmount[$orderItemId]['wrapping'];
        }
        return 0;
    }

    /**
     * @param RmaModel $rma
     * @return mixed
     */
    public function getReturnAmountOfItems(RmaModel $rma)
    {
        $rmaId = $rma->getId();

        if (!isset($this->_rmaReturnAmountItems[$rmaId])) {
            $this->_rmaReturnAmountItems[$rmaId] = [];

            $rmaItems = $rma->getItemsForDisplay()->getItems();

            $rmaItemsData = [];
            $bundleItemsAmount = [];
            $rmaItemObj = [];
            foreach ($rmaItems as $rmaItem) {
                $orderItemId = $rmaItem->getOrderItemId();
                $salesOrderItem = $this->rmaBundleItem->getOrderItemById($orderItemId);
                if ($parentItemId = $salesOrderItem->getData('parent_item_id')) {
                    $bundleItemsAmount[$parentItemId][$orderItemId] = $this->getBundleChildItemTotal(
                        $salesOrderItem,
                        $rmaItem->getQtyRequested()
                    );
                }
                $rmaItemsData[$rmaItem->getOrderItemId()] = $rmaItem->getQtyRequested();
                $rmaItemObj[$rmaItem->getOrderItemId()] = $rmaItem;
            }

            $orderItems = $rma->getOrder()->getAllItems();
            $hasGw = $this->doCalculateGiftWrappingFee($rma);
            $wrappingFeeData = [];
            if (!(empty($bundleItemsAmount))) {
                $this->rmaBundleItem->resetOrderItemsWrappingFee();
                $wrappingFeeData = $this->rmaBundleItem->getWrappingFeeData($rma, $rma->getData(), $rmaItems);
            }

            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            foreach ($orderItems as $orderItem) {
                $itemId = $orderItem->getId();

                if (isset($rmaItemsData[$itemId])) {
                    $rowTotalPerQty = ($orderItem->getRowTotal() - $orderItem->getDiscountAmount() + $orderItem->getTaxAmount()) / $orderItem->getQtyOrdered();

                    if ($orderItem->getParentItemId() && $rowTotalPerQty == 0) {
                        $itemAmount = $bundleItemsAmount[$orderItem->getParentItemId()][$itemId];
                    } else {
                        $itemAmount = $rowTotalPerQty * $rmaItemsData[$itemId];
                    }

                    $unitQty = 1;
                    if ($orderItem->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $unitQty = $orderItem->getUnitQty();
                    }
                    $wrappingFee = $hasGw ? ($orderItem->getGwPrice() + $orderItem->getGwTaxAmount()) * ($rmaItemsData[$itemId] / $unitQty) : 0;
                    if ($orderItem->getParentItemId() > 0) {
                        $wrappingFee = 0;
                        if ($hasGw && isset($rmaItemObj[$itemId])) {
                            $rmaBundleItem = $rmaItemObj[$itemId];
                            $wrappingFee = $rmaBundleItem->getReturnWrappingFee();
                            if (isset($wrappingFeeData[$orderItem->getParentItemId()][$itemId])) {
                                $wrappingFee = $wrappingFeeData[$orderItem->getParentItemId()][$itemId];
                            }
                        }
                    }
                    $this->_rmaReturnAmountItems[$rmaId][$itemId] = [
                        'amount' => $itemAmount,
                        'wrapping' => $wrappingFee
                    ];
                }
            }
        }

        return $this->_rmaReturnAmountItems[$rmaId];
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param $returnRequestQty
     * @return float|int|null
     */
    public function getBundleChildItemTotal(\Magento\Sales\Model\Order\Item $orderItem, $returnRequestQty)
    {
        $result = 0;
        
        $parentItemId = $orderItem->getParentItemId();
        $orderItemCollection = $orderItem->getOrder()->getItemsCollection()
            ->addFieldToFilter('item_id', $parentItemId);
        // Need load collection in order to $parentItem has children items
        $parentItem = $orderItemCollection->getItemByColumnValue('item_id', $orderItem->getParentItemId());


        if ($parentItem && $parentItem->getItemId()) {
            $parentTaxAmount = $parentItem->getTaxAmount();
            $parentDiscountAmount = $parentItem->getDiscountAmount();

            $maxPriceChild = 0;
            $maxPriceChildId = null;

            $totalChildTax = 0;
            $totalChildDiscount = 0;

            /** @var \Magento\Sales\Model\Order\Item $child */
            foreach ($parentItem->getChildrenItems() as $child) {
                $bundleOption = $child->getProductOptionByCode('bundle_selection_attributes');
                $bundleOption = $this->serializer->unserialize($bundleOption);

                $bundleItemPrice = isset($bundleOption['price']) ? $bundleOption['price'] : 0;

                $bundleChildQty = (isset($bundleOption['qty']) ? $bundleOption['qty'] : 1);

                $bundleItemPricePerQty = $bundleItemPrice / $bundleChildQty;

                $bundleItemTaxPerQty = (int)(($parentItem->getTaxPercent() / 100) * $bundleItemPricePerQty);
                $totalChildTax += $bundleItemTaxPerQty * $child->getQtyShipped();

                $parentItemRowTotal = $parentItem->getRowTotal();

                $discountAmountPerQty = $parentItemRowTotal == 0
                    ? 0
                    : (int)(($bundleItemPricePerQty / $parentItemRowTotal) * $parentDiscountAmount);
                $totalChildDiscount += $discountAmountPerQty * $child->getQtyShipped();

                if ($child->getItemId() == $orderItem->getItemId()) {
                    $result = ($bundleItemPricePerQty + $bundleItemTaxPerQty - $discountAmountPerQty) * $returnRequestQty;
                }
                if ($bundleItemPricePerQty >= $maxPriceChild) {
                    $maxPriceChild = $bundleItemPricePerQty;
                    $maxPriceChildId = $child->getId();
                }
            }

            if ($maxPriceChildId == $orderItem->getId() &&
                $returnRequestQty + $orderItem->getQtyReturned() >= $orderItem->getQtyShipped()
            ) {
                $diffTax = $parentTaxAmount - $totalChildTax;
                $result += $diffTax;

                $diffDiscount = $parentDiscountAmount - $totalChildDiscount;
                $result -= $diffDiscount;
            }
        }

        return $result;
    }

    /**
     * @param RmaModel $rma
     * @return int
     */
    public function getReturnedGoodsAmount(RmaModel $rma)
    {
        if ($this->isFreeReturn($rma)) {
            return 0;
        }

        $result = 0;

        $itemsAmount = $this->getReturnAmountOfItems($rma);

        foreach ($itemsAmount as $itemAmount) {
            $result += $itemAmount['amount'] + $itemAmount['wrapping'];
        }

        return $result;
    }

    /**
     * @param RmaModel $rma
     * @return float|int
     */
    public function getReturnAmount(RmaModel $rma)
    {

        $rmaId = $rma->getId();

        if (!isset($this->_rmaReturnAmount[$rmaId])) {
            $result = $this->getReturnShippingAmount($rma) + $this->getNotRetractablePoints($rma);

            $amountReturnItems = $this->getReturnAmountOfItems($rma);

            foreach ($amountReturnItems as $orderItemId => $data) {
                $result += $data['amount'] + $data['wrapping'];
            }

            $this->_rmaReturnAmount[$rmaId] = $result;
        }

        return $this->_rmaReturnAmount[$rmaId];
    }

    /**
     * @param RmaModel $rma
     * @return int
     */
    public function getReturnPaymentFee(RmaModel $rma)
    {
        if ($this->functionCache->has($rma->getId())) {
            return $this->functionCache->load($rma->getId());
        }

        if ($this->isFreeReturn($rma)) {
            $this->functionCache->store(0, $rma->getId());
            return 0;
        }

        $order = $this->dataHelper->getRmaOrder($rma);
        $methodCode = $this->dataHelper->getRmaOrderPaymentMethodCode($rma);
        $paymentFee = floatval($order->getFee());
        $shipmentStatus = $this->dataHelper->getRmaShipmentStatus($rma);
        $reasonFault = $this->dataHelper->getRmaReasonDueTo($rma);
        $expectedMethodCodes = [
            Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE,
            NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ];
        if (in_array($methodCode, $expectedMethodCodes)
            && $paymentFee
            && $shipmentStatus != ShipmentStatus::SHIPMENT_STATUS_REJECTED
            && $rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::FULL
            && $reasonFault == DuetoInterface::NESTLE
        ) {
            $result = $paymentFee;
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if (in_array($methodCode, $expectedMethodCodes)
            && !$paymentFee
            && $shipmentStatus != ShipmentStatus::SHIPMENT_STATUS_REJECTED
            && $rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::FULL
            && $reasonFault == DuetoInterface::NESTLE
        ) {
            $result = 0;
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if (in_array($methodCode, $expectedMethodCodes)
            && $paymentFee
            && $shipmentStatus != ShipmentStatus::SHIPMENT_STATUS_REJECTED
            && $rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::FULL
            && $reasonFault == DuetoInterface::CONSUMER
        ) {
            $result = 0;
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if (in_array($methodCode, $expectedMethodCodes)
            && $paymentFee
            && $shipmentStatus != ShipmentStatus::SHIPMENT_STATUS_REJECTED
            && $rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::PARTIAL
        ) {
            $result = 0;
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if (in_array($methodCode, $expectedMethodCodes)
            && !$paymentFee
            && $shipmentStatus != ShipmentStatus::SHIPMENT_STATUS_REJECTED
            && $rma->getData('full_partial') == \Riki\Rma\Model\Config\Source\Rma\Type::PARTIAL
        ) {
            $result = -floatval($this->paymentFeeDataHelper->getPaymentCharge($methodCode));
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if (in_array($methodCode, $expectedMethodCodes)
            && $paymentFee
            && $shipmentStatus == ShipmentStatus::SHIPMENT_STATUS_REJECTED
        ) {
            $result = $this->dataHelper->getRmaShipment($rma)->getPaymentFee();
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if ($methodCode == \Bluecom\Paygent\Model\Paygent::CODE) {
            $result = 0;
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        if ($methodCode == \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE) {
            $result = 0;
            $this->functionCache->store($result, $rma->getId());
            return $result;
        }

        $this->functionCache->store(0, $rma->getId());
        return 0;
    }

    /**
     * @param RmaModel $rma
     * @return mixed
     */
    public function getShipment(RmaModel $rma)
    {
        if ($this->functionCache->has($rma->getId())) {
            return $this->functionCache->load($rma->getId());
        }
        $rmaId = $rma->getId();

        if (!isset($this->_rmaToShipment[$rmaId])) {
            $this->_rmaToShipment[$rmaId] = new \Magento\Framework\DataObject();

            if ($rma->getRmaShipmentNumber()) {
                $this->_searchCriteriaBuilder->addFilters(
                    [$this->_filterBuilder->setField('increment_id')->setValue($rma->getRmaShipmentNumber())->setConditionType('eq')->create()]
                );

                $this->_searchCriteriaBuilder->setCurrentPage(0)->setPageSize(1);

                $searchCriteria = $this->_searchCriteriaBuilder->create();
                $shipments = $this->_shipmentRepository->getList($searchCriteria);

                if ($shipments->getTotalCount()) {
                    $listShipment = $shipments->getItems();
                    $this->_rmaToShipment[$rmaId] = array_shift($listShipment);
                }
            }
        }
        $this->functionCache->store($this->_rmaToShipment[$rmaId], $rma->getId());
        return $this->_rmaToShipment[$rmaId];
    }

    /**
     * @param RmaModel $rma
     * @return mixed
     */
    public function getReasonByRma(RmaModel $rma)
    {
        return $this->dataHelper->getRmaReason($rma);
    }

    /**
     * @param RmaModel $rma
     * @param int $returnedShippingFeeAdj
     * @param int $returnedPaymentFeeAdj
     *
     * @return int|mixed
     *
     * @deprecated
     */
    public function getPointsToReturn(RmaModel $rma, $returnedShippingFeeAdj = 0, $returnedPaymentFeeAdj = 0)
    {
        if ($this->functionCache->has([$rma->getId(), $returnedShippingFeeAdj, $returnedPaymentFeeAdj])) {
            return $this->functionCache->load([$rma->getId(), $returnedShippingFeeAdj, $returnedPaymentFeeAdj]);
        }

        if ($this->isFreeReturn($rma)) {
            $this->functionCache->store(0, [$rma->getId(), $returnedShippingFeeAdj, $returnedPaymentFeeAdj]);
            return 0;
        }

        $shipment = $this->getShipment($rma);
        $order = $rma->getOrder();
        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
        $usedPoints = $order->getUsedPointAmount();

        if ($shipment->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_REJECTED
            && $paymentMethod == Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE
        ) {
            $this->functionCache->store(
                $shipment->getShoppingPointAmount(),
                [
                    $rma->getId(),
                    $returnedShippingFeeAdj,
                    $returnedPaymentFeeAdj
                ]
            );
            return $shipment->getShoppingPointAmount();
        }

        $finalReturnedShippingFee = $this->getReturnShippingAmount($rma) + $returnedShippingFeeAdj;
        $finalReturnedPaymentFee = $this->getReturnPaymentFee($rma) + $returnedPaymentFeeAdj;
        $goodsAmount = $this->getReturnedGoodsAmount($rma);

        $result = floatval(min(
            $goodsAmount + $finalReturnedShippingFee + $finalReturnedPaymentFee,
            $usedPoints
        ));

        $this->functionCache->store($result, [$rma->getId(), $returnedShippingFeeAdj, $returnedPaymentFeeAdj]);

        return $result;
    }

    /**
     * @param $rma
     *
     * @return int
     */
    public function getReturnedPoint($rma)
    {
        $rewards = $this->rewardCollectionFactory->create()
            ->addFieldToFilter('order_no', $rma->getOrderIncrementId())
            ->addFieldToFilter('point_type', ShoppingPoint::ISSUE_TYPE_ADJUSTMENT)
            ->addFieldToFilter('status', Reward::STATUS_PENDING_APPROVAL);

        $amount = 0;
        foreach ($rewards as $reward) {
            $amount += $reward->getPoint();
        }

        return $amount;
    }

    /**
     * Get point to cancel. Calling to customer point balance API in real time.
     *
     * @param RmaModel $rma
     * @param boolean $static
     *
     * @return float|int
     */
    public function getPointsToCancel(RmaModel $rma, $static = true)
    {
        if ($this->functionCache->has([$rma->getId(), $static])) {
            return $this->functionCache->load([$rma->getId(), $static]);
        }

        if ($this->isFreeReturn($rma)) {
            $this->functionCache->store(0, $rma->getId());
            return 0;
        }
        $reason = $this->getReasonByRma($rma);
        $reasonDueTo = $reason ? $reason->getDueTo() : null;

        $customerBalance = $this->getPointsBalance($rma, $static);
        $itemLevelPoints = $this->getItemConversionEarnedPoints($rma);
        $earnedPoints = $this->getEarnedPoint($rma);

        $result = 0;

        $rmaOrderPaymentMethod = $this->dataHelper->getRmaOrderPaymentMethodCode($rma);
        $rmaOrder = $this->dataHelper->getRmaOrder($rma);
        $rmaOrderPaymentStatus = is_object($rmaOrder) ? $rmaOrder->getData('payment_status') : '';
        $noCalculatePaymentMethods = [
            RikiPaymentMethod::PAYMENT_METHOD_COD,
            NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE,
            RikiPaymentMethod::PAYMENT_METHOD_PAYGENT
        ];
        if (in_array($rmaOrderPaymentMethod, $noCalculatePaymentMethods)
            && PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED != $rmaOrderPaymentStatus
        ) {
            $result = 0;
        } else {
            if ($this->isFullReturn($rma)) {
                if ($earnedPoints <= $customerBalance) {
                    $result = $earnedPoints;
                } else {
                    $result = $customerBalance;
                }
            } else {
                if ($reasonDueTo == DuetoInterface::NESTLE) {
                    if ($itemLevelPoints <= $customerBalance) {
                        $result = $itemLevelPoints;
                    } else {
                        $result = $customerBalance;
                    }
                } elseif ($reasonDueTo == DuetoInterface::CONSUMER) {
                    if ($earnedPoints <= $customerBalance) {
                        $result = $earnedPoints;
                    } else {
                        $result = $customerBalance;
                    }
                }
            }
        }

        $this->functionCache->store($result, [$rma->getId(), $static]);

        return $result;
    }

    /**
     * @param RmaModel $rma
     * @return bool
     */
    public function doCalculateGiftWrappingFee(RmaModel $rma)
    {
        $order = $rma->getOrder();

        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
        $shipment = $this->getShipment($rma);

        $shouldCalculateGWFeePaymentMethods = [
            Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE,
            \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ];

        if ($shipment->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_REJECTED
            && in_array($paymentMethod, $shouldCalculateGWFeePaymentMethods)
        ) {
            return true;
        }
        $reason = $this->getReasonByRma($rma);
        if (in_array($paymentMethod, $shouldCalculateGWFeePaymentMethods)) {
            if ($reason && in_array($reason->getCode(), $this->reasonCodeNotAllowedRefund)) {
                return true;
            }
        }
        if ($reason && ($reason->getDueTo() == DuetoInterface::NESTLE)) {
            return true;
        }

        return false;
    }

    /**
     * @param RmaModel $rma
     * @return bool
     */
    public function isFreeReturn(RmaModel $rma)
    {
        if ($rma->getSubstitutionOrder()) {
            return true;
        }

        $order = $rma->getOrder();

        if ($order->getFreeOfCharge()) {
            return true;
        }

        return false;
    }

    /**
     * Check is allowed refund for RMA
     * Fix bug NED-620: Add condition to check return reason code # 11-24, 41, 44, 45 and 51
     *
     * @param RmaModel $rma
     * @return bool
     */
    public function isAllowedRefund(RmaModel $rma)
    {
        if ($this->functionCache->has($rma->getId())) {
            return $this->functionCache->load($rma->getId());
        }

        $result = false;
        $order = $this->dataHelper->getRmaOrder($rma);
        $methodCode = $this->dataHelper->getRmaOrderPaymentMethodCode($rma);
        $reason = $this->dataHelper->getRmaReason($rma);

        if ($methodCode == Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
            if (!$rma->getSubstitutionOrder()
                && !$order->getFreeOfCharge()
                && !$rma->getRmaShipmentNumber()
                && $reason instanceof \Riki\Rma\Model\Reason
                && !in_array($reason->getCode(), $this->reasonCodeNotAllowedRefund)
            ) {
                $result = true;
            } elseif (!$rma->getSubstitutionOrder()
                && !$order->getFreeOfCharge()
                && $reason instanceof \Riki\Rma\Model\Reason
                && !in_array($reason->getCode(), $this->reasonCodeNotAllowedRefund)
            ) {
                $shipment = $this->dataHelper->getRmaShipment($rma);
                if ($shipment && $shipment->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED) {
                    $result = true;
                }
            }
        } elseif ($methodCode == \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE
            || $methodCode == \Bluecom\Paygent\Model\Paygent::CODE
        ) {
            if (!$rma->getSubstitutionOrder() && !$order->getFreeOfCharge()) {
                $result = true;
            }
        } elseif ($methodCode == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
            return $this->isRefundAllowedNpAtobaraiPaymentMethod($rma);
        }

        $this->functionCache->store($result, $rma->getId());

        return $result;
    }

    /**
     * @param RmaModel $rma
     * @return bool
     */
    public function isAllowedEditAmount(RmaModel $rma)
    {
        if ($rma->getReturnStatus() == \Riki\Rma\Api\Data\Rma\ReturnStatusInterface::CREATED) {
            return true;
        }

        return false;
    }

    /**
     * Get point has been captured by not complete siblings RMAs
     *
     * @param $rma
     *
     * @return int
     */
    public function getCapturedPoint($rma)
    {
        $siblingRmas = $rma->getSiblingRmas();
        $capturedPoint = 0;

        foreach ($siblingRmas as $siblingRma) {
            if (!in_array($siblingRma->getStatus(), [
                    \Magento\Rma\Model\Rma\Source\Status::STATE_PROCESSED_CLOSED,
                    \Magento\Rma\Model\Rma\Source\Status::STATE_CLOSED
                ])
                && $siblingRma->getData('total_return_point_adjusted') > 0
            ) {
                $capturedPoint += $siblingRma->getData('total_return_point_adjusted');
            }
        }

        return $capturedPoint;
    }

    /**
     * @param RmaModel $rma
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function isRefundAllowedNpAtobaraiPaymentMethod(RmaModel $rma)
    {
        $result = false;
        $shipment = $this->dataHelper->getRmaShipment($rma);
        $reason = $this->dataHelper->getRmaReason($rma);

        try {
            $transaction = $this->npTransactionRepository->getByShipmentId($shipment->getEntityId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // refund_allowed is NO for case payment NpAtobarai with shipment has no transaction
            // (due to shipment amount = 0, then we didn't register order to NP)
            return $result;
        }

        if ($shipment && $transaction && $reason) {
            if ($transaction->getNpCustomerPaymentStatus() == NpTransactionPaymentStatus::PAID_STATUS_VALUE) {
                if ($shipment->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
                    && !in_array($reason->getCode(), $this->reasonCodeNotAllowedRefund)) {
                    $result = true;
                }
            } else {
                $this->npAtobaraiAdapter->getPaymentStatus([$transaction]);
                $transaction = $this->npTransactionRepository->getByShipmentId($shipment->getEntityId());
                if ($transaction->getNpCustomerPaymentStatus() == NpTransactionPaymentStatus::PAID_STATUS_VALUE) {
                    if ($shipment->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
                        && !in_array($reason->getCode(), $this->reasonCodeNotAllowedRefund)) {
                        $result = true;
                    }
                }
            }
        }

        return $result;
    }
}
