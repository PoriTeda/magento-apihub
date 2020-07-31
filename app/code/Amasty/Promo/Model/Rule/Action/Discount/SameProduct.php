<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */

namespace Amasty\Promo\Model\Rule\Action\Discount;

class SameProduct extends AbstractDiscount
{
    protected function _addFreeItems(
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Model\Quote\Item $item,
        $qty
    ) {
        if ($this->promoItemHelper->isPromoItem($item))
            return;

        $discountStep   = max(1, $rule->getDiscountStep());
        $maxDiscountQty = 100000;
        if ($rule->getDiscountQty()){
            $maxDiscountQty = intVal(max(1, $rule->getDiscountQty()));
        }

        $discountAmount = max(1, $rule->getDiscountAmount());
        $qty = min(
            floor($item->getQty() / $discountStep) * $discountAmount,
            $maxDiscountQty
        );

        if ($item->getParentItemId())
            return;

        if ($item['product_type'] == 'downloadable')
            return;

        if ($qty < 1)
            return;

        $this->promoRegistry->addPromoItem(
            $item->getProduct()->getData('sku'),
            $qty,
            $rule->getId()
        );
    }
}
