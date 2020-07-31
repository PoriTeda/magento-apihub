<?php
namespace Riki\Sales\Model\Order;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\EntityInterface;
use Magento\Sales\Model\Order\Creditmemo;

class CreditmemoFactory extends \Magento\Sales\Model\Order\CreditmemoFactory
{
    /**
     * Prepare order creditmemo based on order items and requested params
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     * @return Creditmemo
     */
    public function createByOrder(\Magento\Sales\Model\Order $order, array $data = [])
    {
        $totalQty = 0;
        $creditmemo = $this->convertor->toCreditmemo($order);
        $qtys = isset($data['qtys']) ? $data['qtys'] : [];

        $rmaItems = null;
        if (isset($data['rma']) && $data['rma']) {
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $data['rma'];
            $rmaItems = $rma->getRmaItems();
        }
        foreach ($order->getAllItems() as $orderItem) {
            if (!$this->canRefundItem($orderItem, $qtys)) {
                continue;
            }

            $item = $this->convertor->itemToCreditmemoItem($orderItem);

            if (!array_key_exists($orderItem->getId(), $data['qtys'])) {
                continue;
            }

            if ($orderItem->isDummy()) {
                $qty = 1;
                $orderItem->setLockedDoShip(true);
            } else {
                if (isset($qtys[$orderItem->getId()])) {
                    $qty = (double)$qtys[$orderItem->getId()];
                } elseif (!count($qtys)) {
                    $qty = $orderItem->getQtyToRefund();
                } else {
                    continue;
                }
            }
            $totalQty += $qty;
            $item->setQty($qty);
            $item = $this->setPriceForItem($item, $rmaItems, $orderItem->getId());
            $creditmemo->addItem($item);
        }
        $creditmemo->setTotalQty($totalQty);

        $this->initData($creditmemo, $data);

        $creditmemo->collectTotals();
        return $creditmemo;
    }

    /**
     * Prepare order creditmemo based on invoice and requested params
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @param array $data
     * @return Creditmemo
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createByInvoice(\Magento\Sales\Model\Order\Invoice $invoice, array $data = [])
    {
        $order = $invoice->getOrder();
        $totalQty = 0;
        $qtys = isset($data['qtys']) ? $data['qtys'] : [];
        $creditmemo = $this->convertor->toCreditmemo($order);
        $creditmemo->setInvoice($invoice);

        $invoiceQtysRefunded = [];
        foreach ($invoice->getOrder()->getCreditmemosCollection() as $createdCreditmemo) {
            if ($createdCreditmemo->getState() != Creditmemo::STATE_CANCELED &&
                $createdCreditmemo->getInvoiceId() == $invoice->getId()
            ) {
                foreach ($createdCreditmemo->getAllItems() as $createdCreditmemoItem) {
                    $orderItemId = $createdCreditmemoItem->getOrderItem()->getId();
                    if (isset($invoiceQtysRefunded[$orderItemId])) {
                        $invoiceQtysRefunded[$orderItemId] += $createdCreditmemoItem->getQty();
                    } else {
                        $invoiceQtysRefunded[$orderItemId] = $createdCreditmemoItem->getQty();
                    }
                }
            }
        }

        $invoiceQtysRefundLimits = [];
        foreach ($invoice->getAllItems() as $invoiceItem) {
            $invoiceQtyCanBeRefunded = $invoiceItem->getQty();
            $orderItemId = $invoiceItem->getOrderItem()->getId();
            if (isset($invoiceQtysRefunded[$orderItemId])) {
                $invoiceQtyCanBeRefunded = $invoiceQtyCanBeRefunded - $invoiceQtysRefunded[$orderItemId];
            }
            $invoiceQtysRefundLimits[$orderItemId] = $invoiceQtyCanBeRefunded;
        }
        $rmaItems = null;
        if (isset($data['rma']) && $data['rma']) {
            /** @var \Riki\Rma\Model\Rma $rma */
            $rma = $data['rma'];
            $rmaItems = $rma->getRmaItems();
        }

        foreach ($invoice->getAllItems() as $invoiceItem) {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $invoiceItem->getOrderItem();

            if (!$this->canRefundItem($orderItem, $qtys, $invoiceQtysRefundLimits)) {
                continue;
            }

            $item = $this->convertor->itemToCreditmemoItem($orderItem);
            if (!array_key_exists($orderItem->getId(), $data['qtys'])) {
                continue;
            }
            if ($orderItem->isDummy()) {
                if (isset($qtys[$orderItem->getId()])) {
                    $qty = (double)$qtys[$orderItem->getId()];
                } else {
                    $qty = 1;
                }
            } else {
                if (isset($qtys[$orderItem->getId()])) {
                    $qty = (double)$qtys[$orderItem->getId()];
                } elseif (!count($qtys)) {
                    $qty = $orderItem->getQtyToRefund();
                } else {
                    continue;
                }
                if (isset($invoiceQtysRefundLimits[$orderItem->getId()])) {
                    $qty = min($qty, $invoiceQtysRefundLimits[$orderItem->getId()]);
                }
            }
            $qty = min($qty, $invoiceItem->getQty());
            $totalQty += $qty;
            $item->setQty($qty);
            //set data for item again if item is bundle item
            $item = $this->setPriceForItem($item, $rmaItems, $orderItem->getId());
            $creditmemo->addItem($item);
        }
        $creditmemo->setTotalQty($totalQty);

        $this->initData($creditmemo, $data);
        if (!isset($data['shipping_amount'])) {
            $isShippingInclTax = $this->taxConfig->displaySalesShippingInclTax($order->getStoreId());
            if ($isShippingInclTax) {
                $baseAllowedAmount = $order->getBaseShippingInclTax() -
                    $order->getBaseShippingRefunded() -
                    $order->getBaseShippingTaxRefunded();
            } else {
                $baseAllowedAmount = $order->getBaseShippingAmount() - $order->getBaseShippingRefunded();
                $baseAllowedAmount = min($baseAllowedAmount, $invoice->getBaseShippingAmount());
            }
            $creditmemo->setBaseShippingAmount($baseAllowedAmount);
        }

        $creditmemo->collectTotals();
        return $creditmemo;
    }

    /**
     * Method to set price for credit memo item if the item is bundle item.
     * @param Creditmemo\Item $item
     * @param array $rmaItems
     * @param int $orderItemId
     * @return Creditmemo\Item
     */
    private function setPriceForItem(\Magento\Sales\Model\Order\Creditmemo\Item $item, $rmaItems = [], $orderItemId = 0)
    {
        if (!$rmaItems) {
            return $item;
        }
        foreach ($rmaItems as $rmaItem) {
            if ($rmaItem->getOrderItemId() == $orderItemId) {
                $item->setPrice($rmaItem->getReturnAmountExclTax()/$item->getQty());
                $item->setPriceInclTax($rmaItem->getReturnAmountExclTax());
                $item->setBaseTaxAmount($rmaItem->getReturnTaxAmount());
                $item->setTaxAmount($rmaItem->getReturnTaxAmount());
                $item->setRowTotal($rmaItem->getReturnAmountExclTax());
                $item->setBaseRowTotal($rmaItem->getReturnAmountExclTax());
                $item->setRowTotalInclTax($rmaItem->getReturnAmount());
                $item->setBaseRowTotalInclTax($rmaItem->getReturnAmount());
                return $item;
            }
        }
        return $item;
    }

    /**
     * Check if order item can be refunded
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @param array $qtys
     * @param array $invoiceQtysRefundLimits
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function canRefundItem($item, $qtys = [], $invoiceQtysRefundLimits = [])
    {
        if ($item->isDummy()) {
            if ($item->getHasChildren()) {
                foreach ($item->getChildrenItems() as $child) {
                    if (empty($qtys)) {
                        if ($this->canRefundNoDummyItem($child, $invoiceQtysRefundLimits)) {
                            return true;
                        }
                    } else {
                        if (isset($qtys[$child->getId()]) && $qtys[$child->getId()] > 0) {
                            return true;
                        }
                    }
                }
                return false;
            } elseif ($item->getParentItem()) {
                $parent = $item->getParentItem();
                if (empty($qtys)) {
                    return $this->canRefundNoDummyItem($parent, $invoiceQtysRefundLimits);
                } else {
                    if (isset($qtys[$parent->getId()]) && $qtys[$parent->getId()] > 0) {
                        return true;
                    } elseif (isset($qtys[$item->getId()]) && $qtys[$item->getId()] > 0) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        } else {
            return $this->canRefundNoDummyItem($item, $invoiceQtysRefundLimits);
        }
    }
}
