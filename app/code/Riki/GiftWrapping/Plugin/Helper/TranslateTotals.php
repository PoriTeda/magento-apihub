<?php

namespace Riki\GiftWrapping\Plugin\Helper;

class TranslateTotals
{
    public function afterGetTotals($subject, $totals)
    {
        foreach ($totals as &$total) {
            if (isset($total['label'])) {
                $total['label'] = __($total['label']);
            }
        }

        return $totals;
    }
}
