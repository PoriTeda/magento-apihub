<?php
namespace Riki\Sales\Block\Adminhtml\Order\Create;


class Totals extends \Magento\Sales\Block\Adminhtml\Order\Create\Totals
{
    public function getTotals()
    {
        if ($this->getQuote()->isVirtual()) {
            $totals = $this->getQuote()->getBillingAddress()->getTotals();
        } else {
            $totals = $this->getQuote()->getShippingAddress()->getTotals();
        }
        return $totals;
    }

}