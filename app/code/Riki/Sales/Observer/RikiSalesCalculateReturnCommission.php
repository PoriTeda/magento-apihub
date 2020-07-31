<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RikiSalesCalculateReturnCommission implements ObserverInterface
{
    const ITEM_TYPE_SIMPLE = 'simple';
    const ITEM_TYPE_BUNDLE = \Magento\Bundle\Model\Product\Type::TYPE_CODE;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * array of remaining commission amount via order item
     *
     * @var array
     */
    protected $remainingOrderItemCommissionAmount;

    /**
     * Constructor.
     *
     * @param \Riki\Sales\Helper\Order $orderHelper
     */
    public function __construct(
        \Riki\Sales\Helper\Order $orderHelper
    ) {
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $rma = $observer->getRma();

        $items = $rma->getItems();
        if (!$items) {
            return;
        }

        // total commission amount from rma item
        $totalCommissionAmount = 0;

        /* @var \Magento\Rma\Model\Item $rmaItem */
        foreach ($items as $rmaItem) {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $rmaItem->getOrderItem();

            if (!$orderItem) {
                continue;
            }

            $this->_calculateRemainingCommissionAmountOfOrderItem($rma, $orderItem);

            // recalculate commission amount for rma item
            $commissionAmount = $this->_getCommissionAmountForReturnItem($rmaItem, $orderItem);

            // set commission amount for rma item
            $rmaItem->setData('commission_amount', $commissionAmount);

            // sum total commission amount for rma level
            $totalCommissionAmount += $commissionAmount;
        }

        $rma->setData('commission_amount', $totalCommissionAmount);
    }

    /**
     * Get commission amount for return item
     *
     * @param $rmaItem
     * @param $orderItem
     *
     * @return int
     */
    protected function _getCommissionAmountForReturnItem($rmaItem, $orderItem)
    {
        $itemCommissionAmount = 0;

        // item id will be deduct commission amount
        $deductItemId = 0;

        // bundle product
        if (!empty($orderItem->getParentItemId())) {

            // get commission amount from parent item id
            if (isset($this->remainingOrderItemCommissionAmount[$orderItem->getParentItemId()])) {
                $deductItemId = $orderItem->getParentItemId();
                $itemCommissionAmount = $this->remainingOrderItemCommissionAmount[$deductItemId];
            }

        } else {
            $deductItemId = $orderItem->getId();

            // get item total commission amount from global variable
            $itemCommissionAmount = $this->remainingOrderItemCommissionAmount[$deductItemId];
        }

        if ($itemCommissionAmount > 0) {
            // recalculate commission amount for return item
            $returnItemCommissionAmount = $this->orderHelper->getCommissionAmountForReturnItem($rmaItem, $orderItem);

            if ($returnItemCommissionAmount >= $itemCommissionAmount) {

                // commission amount of shipment item is remaining value from order item's commission amount
                $returnItemCommissionAmount = $itemCommissionAmount;

                // set commission amount = 0 for this item
                $this->remainingOrderItemCommissionAmount[$deductItemId] = 0;
            } else {
                // deduct order item commission amount by shipment item's commission amount
                $this->remainingOrderItemCommissionAmount[$deductItemId] -= $returnItemCommissionAmount;
            }

            return $returnItemCommissionAmount;
        }

        return 0;
    }

    /**
     * set commission amount which can be return for this return
     *
     * @param $rma
     * @param $orderItem
     */
    protected function _calculateRemainingCommissionAmountOfOrderItem($rma, $orderItem)
    {
        // order item id which will be get commission amount and re calculated for return item
        $getCommissionAmountItemId = $orderItem->getId();

        $orderItemType = self::ITEM_TYPE_SIMPLE;

        // commission amount from order item
        $remainingOrderItemCommissionAmount = $orderItem->getData('commission_amount');

        // bundle children item case - will be get commission amount from parent item
        if (!empty($orderItem->getParentItemId())) {
            $getCommissionAmountItemId = $orderItem->getParentItemId();
            $orderItemType = self::ITEM_TYPE_BUNDLE;
        }

        if (!isset($this->remainingOrderItemCommissionAmount[$getCommissionAmountItemId])) {
            // for case return item is bundle children item
            if ($orderItemType == self::ITEM_TYPE_BUNDLE) {
                $parentOrderItem = $this->orderHelper->getOrderItemById($getCommissionAmountItemId);
                if ($parentOrderItem && $parentOrderItem->getData('commission_amount')) {
                    $remainingOrderItemCommissionAmount = $this->orderHelper->getRemainingOrderItemCommissionAmountForReturn($rma, $parentOrderItem);
                }
            } else {
                $remainingOrderItemCommissionAmount = $this->orderHelper->getRemainingOrderItemCommissionAmountForReturn($rma, $orderItem);
            }

            // store remaining commission amount of this order item to recalculated for this rma
            $this->remainingOrderItemCommissionAmount[$getCommissionAmountItemId] = $remainingOrderItemCommissionAmount;
        }
    }
}
