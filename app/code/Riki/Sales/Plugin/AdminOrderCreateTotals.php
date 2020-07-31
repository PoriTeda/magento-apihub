<?php
namespace Riki\Sales\Plugin;

class AdminOrderCreateTotals
{
    /**
     * Modify Grand Total Excl. Tax when creating Order in BO
     * To + tax_riki = Grand Total of Magento
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Totals\Grandtotal $grandTotal Grandtotal
     *
     * @return float
     */
    public function afterGetTotalExclTax(\Magento\Sales\Block\Adminhtml\Order\Create\Totals\Grandtotal $grandTotal)
    {
        // use 'tax_riki' instead of 'tax'
        $excl = $grandTotal->getTotals()['grand_total']->getValue() - $grandTotal->getTotals()['tax_riki']->getValue();
        $excl = max($excl, 0);
        return $excl;
    }
}