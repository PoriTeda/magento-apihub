<?php

namespace Riki\Rma\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;

class ProcessAdditionalData implements ObserverInterface
{
    const ITEM_TYPE_SIMPLE = 'simple';
    const ITEM_TYPE_BUNDLE = 'bundle';
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /*array of remaining commission amount via order item*/
    protected $remainingOrderItemCommissionAmount;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Riki\Sales\Helper\Order $orderHelper
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Riki\Sales\Helper\Order $orderHelper
    ) {
        $this->eventManager = $eventManager;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Customized data will be handled before a RMA is saved.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $rma = $observer->getRma();

        $this->eventManager->dispatch('riki_rma_process_additional_data_before', ['rma' => $rma]);

        if (!$rma->getId()) {
            $rma->setData('return_status', ReturnStatusInterface::CREATED);
            $rma->setOrigData('return_status', ReturnStatusInterface::CREATED);

            // Fix bug of magento, magento add incorrect entity_id into rma item
            foreach ($rma->getItems() as $item) {
                $item->unsetData('entity_id');
            }
        }

        if (strlen((string)$rma->getData('substitution_order'))) {
            if ($rma->getData('return_status') == ReturnStatusInterface::REVIEWED_BY_CC) {
                $rma->setOrigData('return_status', ReturnStatusInterface::REVIEWED_BY_CC);
                $rma->setData('return_status', ReturnStatusInterface::APPROVED_BY_CC);
            }
        }

        $this->_updateCalculatedData($rma);

        $this->eventManager->dispatch('riki_rma_process_additional_data_after', ['rma' => $rma]);
    }

    /**
     * Update calculate data
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     */
    protected function _updateCalculatedData(\Magento\Rma\Model\Rma $rma)
    {
        $items = $rma->getItems();
        if (!$items) {
            return;
        }

        $totalReturnTaxAmount = 0;
        $totalReturnAmountExclTax = 0;
        $totalReturnTaxAmountAdj = 0;
        $totalReturnAmountAdjExclTax = 0;

        /*total commission amount from rma item*/
        $totalCommissionAmount = 0;

        /* @var \Magento\Rma\Model\Item $rmaItem */
        foreach ($items as $rmaItem) {
            /* @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $rmaItem->getOrderItem();

            if (!$orderItem) {
                continue;
            }

            $this->setRemainingOrderItemCommissionAmountForReturn($rma, $orderItem);

            $taxPercent = floatval($rmaItem->getOrderItemTaxPercent() / 100);
            $qtyRequested = intval($rmaItem->getQtyRequested());

            $returnAmount = floatval($rmaItem->getData('return_amount'));
            if ($returnAmount) {
                $returnAmountExclTax = floatval($rmaItem->getOrderItemPrice() * $qtyRequested) - ceil(($rmaItem->getOrderItemDiscountAmount() / $orderItem->getQtyOrdered() * $rmaItem->getQtyRequested()) / (1 + $taxPercent));
            } else {
                $returnAmountExclTax = 0;
            }

            $returnTaxAmount = $returnAmount - $returnAmountExclTax;
            $returnAmountAdj = floatval($rmaItem->getData('return_amount_adj'));
            $returnAmountAdjExclTax = ceil($returnAmountAdj / (1 + $taxPercent));

            $rmaItem->setData('return_tax_amount', $returnTaxAmount);
            $rmaItem->setData('return_amount_excl_tax', $returnAmountExclTax);
            $rmaItem->setData('return_amount_adj_excl_tax', $returnAmountAdjExclTax);
            $rmaItem->setData('return_tax_amount_adj', $returnAmountAdj - $returnAmountAdjExclTax);

            /*recalculate commission amount for rma item */
            $commissionAmount = $this->getCommissionAmountForReturnItem($rmaItem, $orderItem);

            /*set commission amount for rma item*/
            $rmaItem->setData('commission_amount', $commissionAmount);

            /*sum total commission amount for rma level*/
            $totalCommissionAmount += $commissionAmount;

            $totalReturnTaxAmount += $rmaItem->getData('return_tax_amount');
            $totalReturnAmountExclTax += $rmaItem->getData('return_amount_excl_tax');
            $totalReturnTaxAmountAdj += $rmaItem->getData('return_tax_amount_adj');
            $totalReturnAmountAdjExclTax += $rmaItem->getData('return_amount_adj_excl_tax');
        }

        $rma->setData('return_tax_amount', $totalReturnTaxAmount);
        $rma->setData('return_amount_excl_tax', $totalReturnAmountExclTax);
        $rma->setData('return_tax_amount_adj', $totalReturnTaxAmountAdj);
        $rma->setData('return_amount_adj_excl_tax', $totalReturnAmountAdjExclTax);
        $rma->setData('commission_amount', $totalCommissionAmount);
    }

    /**
     * Get commission amount for return item
     *
     * @param \Magento\Rma\Model\Item $rmaItem
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return int
     */
    protected function getCommissionAmountForReturnItem(
        \Magento\Rma\Model\Item $rmaItem,
        \Magento\Sales\Model\Order\Item $orderItem
    ) {
        /*bundle product*/
        if (!empty($orderItem->getParentItemId())) {
            return $this->orderHelper->getCommissionAmountForBundleChildrenItem(
                $orderItem,
                $rmaItem->getQtyRequested()
            );
        }

        /*item id will be deduct commission amount*/
        $deductItemId = $orderItem->getId();

        /*get item total commission amount from global variable*/
        $itemCommissionAmount = $this->remainingOrderItemCommissionAmount[$deductItemId];

        if ($itemCommissionAmount > 0) {
            /*recalculate commission amount for return item*/
            $returnItemCommissionAmount = $this->orderHelper->getCommissionAmountForReturnItem($rmaItem, $orderItem);

            if ($returnItemCommissionAmount >= $itemCommissionAmount) {
                /*commission amount of shipment item is remaining value from order item's commission amount*/
                $returnItemCommissionAmount = $itemCommissionAmount;

                /*set commission amount = 0 for this item */
                $this->remainingOrderItemCommissionAmount[$deductItemId] = 0;
            } else {
                /*deduct order item commission amount by shipment item's commission amount*/
                $this->remainingOrderItemCommissionAmount[$deductItemId] -= $returnItemCommissionAmount;
            }

            return $returnItemCommissionAmount;
        }

        return 0;
    }

    /**
     * set commission amount which can be return for this return
     *
     * @param \Magento\Rma\Model\Rma $rma
     * @param \Magento\Sales\Model\Order\Item $orderItem
     */
    protected function setRemainingOrderItemCommissionAmountForReturn(
        \Magento\Rma\Model\Rma $rma,
        \Magento\Sales\Model\Order\Item $orderItem
    ) {
        /*order item id which will be get commission amount and re calculated for return item*/
        $getCommissionAmountItemId = $orderItem->getId();

        $orderItemType = self::ITEM_TYPE_SIMPLE;

        /*commission amount from order item*/
        $remainingOrderItemCommissionAmount = $orderItem->getData('commission_amount');

        /*bundle children item case - will be get commission amount from parent item*/
        if (!empty($orderItem->getParentItemId())) {
            $getCommissionAmountItemId = $orderItem->getParentItemId();
            $orderItemType = self::ITEM_TYPE_BUNDLE;
        }

        if (!isset($this->remainingOrderItemCommissionAmount[$getCommissionAmountItemId])) {
            /*for case return item is bundle children item*/
            if ($orderItemType == self::ITEM_TYPE_BUNDLE) {
                $parentOrderItem = $this->orderHelper->getOrderItemById($getCommissionAmountItemId);
                if ($parentOrderItem && $parentOrderItem->getData('commission_amount')) {
                    $remainingOrderItemCommissionAmount = $this->getRemainingOrderItemCommissionAmountForReturn(
                        $rma,
                        $parentOrderItem
                    );
                }
            } else {
                $remainingOrderItemCommissionAmount = $this->getRemainingOrderItemCommissionAmountForReturn(
                    $rma,
                    $orderItem
                );
            }

            /*store remaining commission amount of this order item to recalculated for this rma*/
            $this->remainingOrderItemCommissionAmount[$getCommissionAmountItemId] = $remainingOrderItemCommissionAmount;
        }
    }

    /**
     * get commission amount which can be return for this return
     *
     * @param $rma
     * @param $orderItem
     * @return int
     */
    protected function getRemainingOrderItemCommissionAmountForReturn($rma, $orderItem)
    {
        return $this->orderHelper->getRemainingOrderItemCommissionAmountForReturn($rma, $orderItem);
    }
}
