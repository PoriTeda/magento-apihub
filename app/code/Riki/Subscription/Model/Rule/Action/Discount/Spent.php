<?php

namespace Riki\Subscription\Model\Rule\Action\Discount;

class Spent extends \Riki\Subscription\Model\Rule\Action\Discount\AbstractDiscount
{
    /**
     * Get subtotal from registry instead of from quote as at this time quote totals is not calculated
     *
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return int
     */
    protected function _getFreeItemsQty(
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Model\Quote $quote
    )
    {
        $amount = max(1, $rule->getDiscountAmount());
        $step = $rule->getDiscountStep();

        if (!$step)
            return 0;

        $subtotal = $this->_registry->registry('riki_promo_subtotal') ? (float)$this->_registry->registry('riki_promo_subtotal') : 0;
        $qty = floor($subtotal / $step) * $amount;

        $max = $rule->getDiscountQty();
        if ($max) {
            $qty = min($max, $qty);
        }

        return $qty;
    }
}