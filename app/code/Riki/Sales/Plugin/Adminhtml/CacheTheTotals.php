<?php

namespace Riki\Sales\Plugin\Adminhtml;

class CacheTheTotals
{
    public function aroundGetTotals(\Magento\Sales\Block\Adminhtml\Order\Create\Totals $subject, $proceed)
    {
        if (!$subject->getData('totals_cached')) {
            $subject->setData('totals_cached', $proceed());
        }

        return $subject->getData('totals_cached');
    }
}
