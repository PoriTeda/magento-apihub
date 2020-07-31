<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */

namespace Amasty\Promo\Model\Rule\Action\Discount;

class Spent extends AbstractDiscount
{
    protected function _getFreeItemsQty(
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Model\Quote $quote
    ) {
        $amount = max(1, $rule->getDiscountAmount());
        $step = $rule->getDiscountStep();

        if (!$step)
            return 0;

        $totals = $quote->getTotals();
        $qty = floor($totals['subtotal']->getValue() / $step) * $amount;

        $max = $rule->getDiscountQty();
        if ($max){
            $qty = min($max, $qty);
        }

        return $qty;
    }
}
