<?php
namespace Riki\Sales\Plugin\Weee\Block\Item\Price;

use Magento\Sales\Model\Order\CreditMemo\Item as CreditMemoItem;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;

class Renderer
{

    /**
     * @param \Magento\Weee\Block\Item\Price\Renderer $subject
     * @param \Closure $proceed
     * @param $item
     * @return mixed
     */
    public function aroundGetTotalAmount(
        \Magento\Weee\Block\Item\Price\Renderer $subject,
        \Closure $proceed,
        $item
    ) {

        $result = $proceed($item);

        $unitQty = $item->getUnitQty()? $item->getUnitQty() : 1;

        $qty = $item->getQtyOrdered() / $unitQty;

        $result = $result
            + $item->getDiscountAmount()
            - $item->getTotalDiscountAmount()
            + ($item->getGwPrice() + $item->getGwTaxAmount()) * $qty;

        return $result;
    }

    /**
     * @param \Magento\Weee\Block\Item\Price\Renderer $subject
     * @param \Closure $proceed
     * @param $item
     * @return mixed
     */
    public function aroundGetBaseTotalAmount(
        \Magento\Weee\Block\Item\Price\Renderer $subject,
        \Closure $proceed,
        $item
    ) {

        $result = $proceed($item);

        $unitQty = $item->getUnitQty()? $item->getUnitQty() : 1;

        $qty = $item->getQtyOrdered() / $unitQty;

        $result = $result
            + $item->getTotalDiscountAmount()
            - $item->getBaseTotalDiscountAmount()
            + ($item->getGwBasePrice() + $item->getGwBaseTaxAmount()) * $qty;

        return $result;
    }
}
