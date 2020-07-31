<?php
namespace Riki\Rma\Model\Rma;

use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Magento\OfflinePayments\Model\Cashondelivery;
use Riki\Rma\Api\Data\Reason\DuetoInterface;
use Magento\Framework\Serialize\Serializer\Serialize as Serializer;

class BundleItem
{
    protected $reasonCodeNotAllowedRefund = [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 41, 44, 45, 51];
    /**
     * @var array
     */
    protected $rmaToShipment = [];

    /** @var array */
    protected $orderItems = [];

    /** @var array */
    protected $orderItemsEarnedPoint = [];

    /** @var array */
    protected $orderItemsWrappingFee = [];

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Riki\Sales\Helper\Admin
     */
    protected $adminHelper;

    /**
     * @var \Riki\Rma\Model\Repository\ItemRepository
     */
    protected $rmaItemRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $promoHelper;

    /**
     * @var \Riki\Rma\Api\ReasonRepositoryInterface
     */
    protected $reasonRepository;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $dataObject;

    protected $serializer;

    /**
     * BundleItem constructor.
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Riki\Sales\Helper\Admin $adminHelper
     * @param \Riki\Rma\Model\Repository\ItemRepository $rmaItemRespository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Promo\Helper\Data $promoHelper
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Framework\DataObject $dataObject
     */
    public function __construct(
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Riki\Sales\Helper\Admin $adminHelper,
        \Riki\Rma\Model\Repository\ItemRepository $rmaItemRespository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Promo\Helper\Data $promoHelper,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\DataObject $dataObject,
        Serializer $serializer
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->adminHelper = $adminHelper;
        $this->rmaItemRepository = $rmaItemRespository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->promoHelper = $promoHelper;
        $this->searchHelper = $searchHelper;
        $this->reasonRepository = $reasonRepository;
        $this->connection = $resourceConnection->getConnection();
        $this->filterBuilder = $filterBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->dataObject = $dataObject;
        $this->serializer = $serializer;
    }

    /**
     * @param $bundleItemId
     * @return int
     */
    public function getBundleItemEarnedPoint($bundleItemId, $rmaItem)
    {
        $earnedPoint = 0;
        $bundleItem = $this->getOrderItemById($bundleItemId);
        if ($parentItemId = $bundleItem->getData('parent_item_id')) {
            $parentItem = $this->getOrderItemById($parentItemId);
            $parentItemEarnedPoints = $this->adminHelper->getEarnedPointByOrderItem($parentItem);
            $totalEarnedPointPerItem = 0;
            foreach ($parentItemEarnedPoints as $parentItemEarnedPoint) {
                $totalEarnedPointPerItem += $parentItemEarnedPoint['point'];
            }
            $bundleItemEarnedPoint = $this->calculateBundleItemEarnedPoint(
                $parentItem,
                $bundleItem,
                $totalEarnedPointPerItem
            );
            $requestQty = $rmaItem->getQtyRequested();
            $qtyShipped = $bundleItem->getData('qty_shipped');
            $rmaBundleItemData = $this->getCreatedRmaBundleItemData($bundleItemId);
            if (($requestQty + $rmaBundleItemData['qty_requested']) == $qtyShipped) {
                // For final rma of each order item
                $rmaBundleItemUsedPoint = $rmaBundleItemData['bundle_item_earned_point'];
                $earnedPoint = ($bundleItemEarnedPoint * $parentItem->getData('qty_ordered')) - $rmaBundleItemUsedPoint;
            } else {
                $originalBundleItemQty = $bundleItem->getData('qty_ordered') / $parentItem->getData('qty_ordered');
                $eachBundleItemEarnedPoint = ceil($bundleItemEarnedPoint / $originalBundleItemQty);
                $earnedPoint = $eachBundleItemEarnedPoint * $requestQty;
            }
        }
        return $earnedPoint;
    }

    /**
     * @param $parentItem
     * @param $bundleItem
     * @return float|int
     */
    public function calculateBundleItemEarnedPoint($parentItem, $bundleItem, $totalEarnedPoint)
    {
        $parentItemId = $parentItem->getId();
        $returnPoint = 0;
        if (!isset($this->orderItemsEarnedPoint[$parentItemId])) {
            $bundleItems = $this->getBundleItemsByParentId($parentItem->getId());
            foreach ($bundleItems as $item) {
                $bundleItemEarnedPoint = 0;
                $bundleItemPrice = $this->getOriginalPriceForBundleChildrenItem(
                    $item,
                    $parentItem->getPrice()
                );
                $bundleItemPriceInclTax = $this->getPriceIncludeTaxForBundleChildrenItem(
                    $bundleItemPrice,
                    $parentItem->getTaxPercent()
                );
                if ($parentItem->getPriceInclTax()) {
                    $bundleItemEarnedPoint = floor(
                        ($totalEarnedPoint * $bundleItemPriceInclTax) / $parentItem->getPriceInclTax()
                    );
                }
                $this->orderItemsEarnedPoint[$parentItemId][$item->getId()] = $bundleItemEarnedPoint;
            }
            $maxItemId = $this->getBundleItemIdByMaximumValue($this->orderItemsEarnedPoint[$parentItemId]);
            $adjustPoint = $totalEarnedPoint - array_sum($this->orderItemsEarnedPoint[$parentItemId]);
            if ($adjustPoint > 0) {
                $this->orderItemsEarnedPoint[$parentItemId][$maxItemId] += $adjustPoint;
            }
        }
        if (isset($this->orderItemsEarnedPoint[$parentItemId][$bundleItem->getId()])) {
            $returnPoint = $this->orderItemsEarnedPoint[$parentItemId][$bundleItem->getId()];
        }
        return $returnPoint;
    }

    /**
     * @param $orderItemId
     * @return mixed
     */
    public function getOrderItemById($orderItemId)
    {
        if (!isset($this->orderItems[$orderItemId])) {
            $orderItem = $this->orderItemRepository->get($orderItemId);
            $this->orderItems[$orderItemId] = $orderItem;
        }
        return $this->orderItems[$orderItemId];
    }

    /**
     * @param $bundleItemId
     * @return array
     */
    private function getCreatedRmaBundleItemData($bundleItemId)
    {
        $createdRmaItemData = [
            'bundle_item_earned_point' => 0,
            'qty_requested' => 0,
        ];
        $query = $this->searchCriteriaBuilder
            ->addFilter('order_item_id', $bundleItemId)
            ->addFilter('status', 'rejected', 'neq')
            ->create();

        $result = $this->rmaItemRepository->getList($query);
        foreach ($result->getItems() as $item) {
            $createdRmaItemData['bundle_item_earned_point'] += $item->getData('bundle_item_earned_point');
            $createdRmaItemData['qty_requested'] += $item->getData('qty_requested');
        }
        return $createdRmaItemData;
    }

    /**
     * @param $bundleItemId
     * @return array
     */
    public function getExistRmaBundleItemData($bundleItemId, $rmaItemId)
    {
        $existRmaItemData = [
            'bundle_item_earned_point' => 0,
            'total_item' => 0,
            'updated_item_total' => 0,
            'qty_requested' => 0
        ];
        $query = $this->searchCriteriaBuilder
            ->addFilter('order_item_id', $bundleItemId)
            ->addFilter('status', 'rejected', 'neq')
            ->create();

        $result = $this->rmaItemRepository->getList($query);
        $existRmaItemData['total_item'] = $result->getTotalCount();
        foreach ($result->getItems() as $item) {
            if ($item->getId() == $rmaItemId) {
                continue;
            }
            if ($item->getData('bundle_item_earned_point') > 0) {
                $existRmaItemData['bundle_item_earned_point'] += $item->getData('bundle_item_earned_point');
                $existRmaItemData['updated_item_total'] += 1;
                $existRmaItemData['qty_requested'] += $item->getData('qty_requested');
            }
        }
        return $existRmaItemData;
    }

    /**
     * @param $parentItemId
     * @return \Magento\Sales\Api\Data\OrderItemSearchResultInterface
     */
    private function getBundleItemsByParentId($parentItemId)
    {
        $query = $this->searchCriteriaBuilder
            ->addFilter('parent_item_id', $parentItemId)
            ->create();

        $result = $this->orderItemRepository->getList($query);
        return $result;
    }

    /**
     * @param $rmaItemsData
     * @param $orderItemId
     * @param $parentItemId
     * @return int
     */
    public function getBundleItemWrappingFee($rmaItemsData, $orderItemId, $parentItemId, $bundleItemsAmount)
    {
        $wrappingFee = 0;
        $rmaBundleItems = [];
        /** @var \Magento\Sales\Model\Order\Item $parentItem */
        $parentItem = $this->getOrderItemById($parentItemId);
        if (!$parentItem->getGwId()) {
            return $wrappingFee;
        }
        foreach ($rmaItemsData as $itemId => $requestQty) {
            $orderItem = $this->getOrderItemById($itemId);
            if ($orderItem->getData('parent_item_id') > 0) {
                $rmaBundleItems[$orderItem->getData('parent_item_id')][$itemId] = $requestQty;
            }
        }

        $maxWrappingFee = $this->getMaxWrappingFee($rmaBundleItems[$parentItemId], $parentItem);
        if (!isset($this->orderItemsWrappingFee[$parentItemId])) {
            if (count($rmaBundleItems[$parentItemId]) == 1) {
                $this->orderItemsWrappingFee[$parentItemId][$orderItemId] = $maxWrappingFee;
            } else {
                $totalPrice = array_sum($bundleItemsAmount);
                // Divise $maxWrappingFee for each item
                foreach ($rmaBundleItems[$parentItemId] as $bundleItemId => $requestQty) {
                    $itemWrappingFee = floor($maxWrappingFee * $bundleItemsAmount[$bundleItemId] / $totalPrice);
                    $this->orderItemsWrappingFee[$parentItemId][$bundleItemId] = $itemWrappingFee;
                }
            }
            $restWrappingFee = $maxWrappingFee - array_sum($this->orderItemsWrappingFee[$parentItemId]);
            if ($restWrappingFee > 0) {
                $maxItemId = $this->getBundleItemIdByMaximumValue($this->orderItemsWrappingFee[$parentItemId]);
                $this->orderItemsWrappingFee[$parentItemId][$maxItemId] += $restWrappingFee;
            }
        }
        if (isset($this->orderItemsWrappingFee[$parentItemId][$orderItemId])) {
            $wrappingFee = $this->orderItemsWrappingFee[$parentItemId][$orderItemId];
        }
        return $wrappingFee;
    }

    /**
     * @param $bundleItems
     * @param $parentItem
     * @return float|int
     */
    protected function getMaxWrappingFee($bundleItems, $parentItem)
    {
        $maxWrappingFee = 0;
        foreach ($bundleItems as $bundleItemId => $requestQty) {
            /** @var \Magento\Sales\Model\Order\Item $bundleItem */
            $bundleItem = $this->getOrderItemById($bundleItemId);
            $itemWrapingFee = $this->getItemWrappingFee($bundleItem, $parentItem, $requestQty);
            if ($itemWrapingFee > $maxWrappingFee) {
                $maxWrappingFee = $itemWrapingFee;
            }
        }
        return $maxWrappingFee;
    }

    /**
     * @param $bundleItem
     * @param $parentItem
     * @param $requestQty
     * @return float
     */
    protected function getItemWrappingFee($bundleItem, $parentItem, $requestQty)
    {
        $bundleItemQty = $bundleItem->getData('qty_ordered') / $parentItem->getData('qty_ordered');
        $convertedQty = ceil($requestQty / $bundleItemQty);
        return ($parentItem->getGwPrice() + $parentItem->getGwTaxAmount()) * $convertedQty;
    }

    /**
     * Reset order items earned point
     * @return void
     */
    public function resetOrderItemsEarnedPoint()
    {
        $this->orderItemsEarnedPoint = [];
    }

    /**
     * Reset order items wrapping fee
     * @return void
     */
    public function resetOrderItemsWrappingFee()
    {
        $this->orderItemsWrappingFee = [];
    }

    /**
     * @param $item
     * @param $parentPrice
     * @return float|int
     */
    public function getOriginalPriceForBundleChildrenItem($item, $parentPrice)
    {
        if ($parentPrice > 0) {
            /*get product option*/
            if (is_array($item['product_options'])) {
                $productOption = $item['product_options'];
            } else {
                $productOption = $this->unserializeOption($item['product_options']);
            }
            if ($productOption && !empty($productOption['bundle_selection_attributes'])) {
                /*get bundle option*/
                $bundleOption = $this->unserializeOption($productOption['bundle_selection_attributes']);
                if ($bundleOption) {
                    return $bundleOption['price'];
                }
            }
        }
        return 0;
    }

    /**
     * un serialize Option
     *
     * @param $option
     * @return bool|mixed
     */
    protected function unserializeOption($option)
    {
        try {
            return json_decode($option,true);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get price include tax for bundle children item
     *
     * @param $price
     * @param $taxPercent
     * @return float|int
     */
    public function getPriceIncludeTaxForBundleChildrenItem($price, $taxPercent)
    {
        if ($taxPercent) {
            $taxPercent = $taxPercent / 100;

            return floor($price * (1 + $taxPercent));
        }

        return $price;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param $returnRequestQty
     * @return float|int|null
     */
    public function getBundleChildItemTotal(\Magento\Sales\Model\Order\Item $orderItem, $returnRequestQty)
    {
        $result = 0;

        $parentItem = $orderItem->getParentItem();

        if (!$parentItem) {
            $orderItemCollection = $orderItem->getOrder()->getItemsCollection()
                ->addFieldToFilter('item_id', $orderItem->getParentItemId());
            // Need load collection in order to $parentItem has children items
            $parentItem = $orderItemCollection->getItemByColumnValue('item_id', $orderItem->getParentItemId());
        }

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
                $bundleOption = json_decode($bundleOption, true);

                $bundleItemPrice = isset($bundleOption['price']) ? $bundleOption['price'] : 0;

                $bundleChildQty = (isset($bundleOption['qty']) ? $bundleOption['qty'] : 1);

                $bundleItemPricePerQty = $bundleItemPrice / $bundleChildQty;

                $bundleItemTaxPerQty = (int)(($parentItem->getTaxPercent() / 100) * $bundleItemPricePerQty);
                $totalChildTax += $bundleItemTaxPerQty * $child->getQtyShipped();

                $parentItemRowTotal = $parentItem->getRowTotal();

                $discountAmountPerQty = $parentItemRowTotal == 0? 0 : (int)(($bundleItemPricePerQty / $parentItemRowTotal) * $parentDiscountAmount);
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
     * @param $rmaItems
     * @return array
     */
    public function buildBundleItemAmount($rmaItems)
    {
        $rmaItemsData = [];
        $bundleItemsAmount = [];
        foreach ($rmaItems as $item) {
            $orderItemId = $item->getOrderItemId();
            $salesOrderItem = $this->getOrderItemById($orderItemId);
            if ($parentItemId = $salesOrderItem->getData('parent_item_id')) {
                $total = $this->getBundleChildItemTotal($salesOrderItem, $item->getQtyRequested());
                $bundleItemsAmount[$parentItemId][$orderItemId] = $total;
                $rmaItemsData[$item->getOrderItemId()] = $item->getQtyRequested();
            }
        }

        return [$rmaItemsData, $bundleItemsAmount];
    }

    /**
     * @param $rmaItems
     * @return array
     */
    public function getWrappingFeeData($rma, $rmaData, $rmaItems)
    {
        $wrappingFeeData = [];
        list($rmaItemsData, $bundleItemsAmount) = $this->buildBundleItemAmount($rmaItems);
        if (!empty($bundleItemsAmount)) {
            $hasGw = $this->doCalculateGiftWrappingFee($rma, $rmaData);
            if ($hasGw) {
                foreach ($rmaItems as $item) {
                    $orderItem = $this->getOrderItemById($item->getOrderItemId());
                    if ($orderItem->getParentItemId() > 0) {
                        $wrappingFee = $this->getBundleItemWrappingFee(
                            $rmaItemsData,
                            $orderItem->getId(),
                            $orderItem->getParentItemId(),
                            $bundleItemsAmount[$orderItem->getParentItemId()]
                        );
                        if ($wrappingFee > 0) {
                            $wrappingFeeData[$orderItem->getParentItemId()][$item->getOrderItemId()] = $wrappingFee;
                        }
                    }
                }
            }
        }
        if (!empty($wrappingFeeData)) {
            foreach ($wrappingFeeData as $parentId => $bundleItemData) {
                $wrappingFeeTotal = array_sum($bundleItemData);
                $parentItemTotalWrappingFee = $this->getParentItemTotalWrappingFee($parentId);
                $bundleItemUsedWrappingFee = $this->getBundleItemUsedWrappingFee($rma, $parentId);
                if ($parentItemTotalWrappingFee == $bundleItemUsedWrappingFee) {
                    unset($wrappingFeeData[$parentId]);
                    continue;
                }
                $restWrappingFee = $parentItemTotalWrappingFee - $bundleItemUsedWrappingFee;
                if ($wrappingFeeTotal > $restWrappingFee) {
                    // divise $restWrappingFee for every item
                    $totalBundleItemAmount = $bundleItemsAmount[$parentId];
                    $maxItemId = $this->getBundleItemIdByMaximumValue($totalBundleItemAmount);
                    $totalFee = 0;
                    foreach ($bundleItemData as $itemId => $fee) {
                        $bundleItemAmount = $totalBundleItemAmount[$itemId];
                        $restWrappingFeePerItem = floor($bundleItemAmount * $restWrappingFee / array_sum($totalBundleItemAmount));
                        $wrappingFeeData[$parentId][$itemId] = $restWrappingFeePerItem;
                        $totalFee += $restWrappingFeePerItem;
                    }
                    $surPlus = $restWrappingFee - $totalFee;
                    if ($surPlus) {
                        $wrappingFeeData[$parentId][$maxItemId] += $surPlus;
                    }
                }
            }
        }
        return $wrappingFeeData;
    }

    /**
     * @param $parentId
     * @return mixed
     */
    protected function getParentItemTotalWrappingFee($parentId)
    {
        $parentItem = $this->getOrderItemById($parentId);
        return ($parentItem->getGwPrice() + $parentItem->getGwTaxAmount()) * $parentItem->getQtyOrdered();
    }

    /**
     * @param RmaModel $rma
     * @return bool
     */
    public function doCalculateGiftWrappingFee($rma, $rmaData)
    {
        $order = $rma->getOrder();

        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
        $shipment = $this->getShipment($rma, $rmaData);

        $shouldCalculateGWFeePaymentMethods = [
            Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE,
            \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE
        ];

        if ($shipment->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_REJECTED
            && in_array($paymentMethod, $shouldCalculateGWFeePaymentMethods)
        ) {
            return true;
        }
        $reasonId = isset($rmaData['reason_id']) ? $rmaData['reason_id'] : $rma->getReasonId();
        $reason = $this->searchHelper
            ->getById($reasonId)
            ->getOne()
            ->execute($this->reasonRepository);
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
     * @param $rma
     * @param $parentId
     * @return int
     */
    protected function getBundleItemUsedWrappingFee($rma, $parentId)
    {
        $bundleItemUsedWrappingFee = 0;
        $rmaIds = $this->getCreatedRmaIds($rma->getOrderId(), $rma->getId());
        $query = $this->searchCriteriaBuilder
            ->addFilter('rma_entity_id', $rmaIds, 'in')
            ->addFilter('status', 'rejected', 'neq')
            ->create();
        $result = $this->rmaItemRepository->getList($query);
        foreach ($result->getItems() as $rmaItem) {
            $bundleItem = $this->getOrderItemById($rmaItem->getOrderItemId());
            if ($bundleItem->getParentItemId() == $parentId) {
                $bundleItemUsedWrappingFee += $rmaItem->getReturnWrappingFee();
            }
        }
        return $bundleItemUsedWrappingFee;
    }

    /**
     * @param $orderId
     * @return array
     */
    protected function getCreatedRmaIds($orderId, $currentRmaId)
    {
        $rmaIds = [];
        $tableName = $this->connection->getTableName('magento_rma');
        $select = $this->connection->select('entity_id')->from($tableName)->where('order_id = ?', $orderId);
        if ($currentRmaId) {
            $select->where('entity_id <> ?', $currentRmaId);
        }
        $result = $this->connection->fetchCol($select);
        if (!empty($result)) {
            $rmaIds = $result;
        }
        return $rmaIds;
    }

    /**
     * @param RmaModel $rma
     * @return mixed
     */
    public function getShipment($rma, $rmaData)
    {
        $rmaShippmentNumber = isset($rmaData['rma_shipment_number'])
            ? $rmaData['rma_shipment_number']
            : $rma->getRmaShipmentNumber();
        if ($rmaShippmentNumber) {
            $this->searchCriteriaBuilder->addFilters(
                [$this->filterBuilder->setField('increment_id')
                    ->setValue($rmaShippmentNumber)->setConditionType('eq')->create()
                ]
            );

            $this->searchCriteriaBuilder->setCurrentPage(0)->setPageSize(1);

            $searchCriteria = $this->searchCriteriaBuilder->create();
            $shipments = $this->shipmentRepository->getList($searchCriteria);

            if ($shipments->getTotalCount()) {
                $listShipment = $shipments->getItems();
                return array_shift($listShipment);
            }
        }
        return $this->dataObject;
    }

    /**
     * @param $rmaItem
     * @param $orderItemsEarnedPoint
     * @return bool
     */
    public function isBundleItemShoppingPoint($rmaItem, $orderItemsEarnedPoint)
    {
        $orderItem = $this->getOrderItemById($rmaItem->getOrderItemId());
        foreach ($orderItemsEarnedPoint as $orderItemData) {
            if ($orderItemData['order_item_id'] == $orderItem->getParentItemId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $bundleItemData
     * @return false|int|string
     */
    public function getBundleItemIdByMaximumValue($bundleItemData)
    {
        return array_search(max($bundleItemData), $bundleItemData);
    }
}
