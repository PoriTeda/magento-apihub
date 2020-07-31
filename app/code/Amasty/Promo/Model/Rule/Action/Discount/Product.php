<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */

namespace Amasty\Promo\Model\Rule\Action\Discount;

class Product extends AbstractDiscount
{
    protected function _getFreeItemsQty(
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Model\Quote $quote
    ) {
        $qty = 0;
        $amount = max(1, $rule->getDiscountAmount());
        $step = max(1, $rule->getDiscountStep());
        foreach ($quote->getAllVisibleItems() as $item) {
            if (!$item)
                continue;

            if ($this->promoItemHelper->isPromoItem($item))
                continue;

            if (!$rule->getActions()->validate($item))
                continue;

            if ($item->getParentItemId())
                continue;

            if ($item->getProduct()->getParentProductId())
                continue;

            $qty = $qty + $item->getQty();
        }

        $qty = floor($qty / $step) * $amount;
        $max = $rule->getDiscountQty();
        if ($max){
            $qty = min($max, $qty);
        }

        return $qty;
    }
}
