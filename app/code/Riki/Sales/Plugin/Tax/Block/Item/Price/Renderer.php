<?php
namespace Riki\Sales\Plugin\Tax\Block\Item\Price;

use Magento\Sales\Model\Order\CreditMemo\Item as CreditMemoItem;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;

class Renderer
{

    /**
     * @param \Magento\Tax\Block\Item\Price\Renderer $subject
     * @param \Closure $proceed
     * @param $item
     * @return mixed
     */
    public function aroundGetTotalAmount(
        \Magento\Tax\Block\Item\Price\Renderer $subject,
        \Closure $proceed,
        $item
    ) {

        $result = $proceed($item);

        $unitQty = $item->getUnitQty()? $item->getUnitQty() : 1;

        $qty = $item->getQtyOrdered() / $unitQty;

        $result = $result + ($item->getGwPrice() + $item->getGwTaxAmount()) * $qty;

        return $result;
    }

    /**
     * @param \Magento\Tax\Block\Item\Price\Renderer $subject
     * @param \Closure $proceed
     * @param $item
     * @return mixed
     */
    public function aroundGetBaseTotalAmount(
        \Magento\Tax\Block\Item\Price\Renderer $subject,
        \Closure $proceed,
        $item
    ) {

        $result = $proceed($item);

        $unitQty = $item->getUnitQty()? $item->getUnitQty() : 1;

        $qty = $item->getQtyOrdered() / $unitQty;

        $result = $result + ($item->getGwBasePrice() + $item->getGwBaseTaxAmount()) * $qty;

        return $result;
    }
}
