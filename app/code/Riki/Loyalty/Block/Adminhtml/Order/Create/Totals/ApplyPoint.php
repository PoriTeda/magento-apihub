<?php

namespace Riki\Loyalty\Block\Adminhtml\Order\Create\Totals;


class ApplyPoint extends \Magento\Sales\Block\Adminhtml\Order\Create\Totals\DefaultTotals
{
    public function hasPointForTrial()
    {
        if($this->getQuote()->getPointForTrial())
        {
            return true;
        }
        return false;
    }
}