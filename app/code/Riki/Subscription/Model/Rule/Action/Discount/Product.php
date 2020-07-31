<?php
namespace Riki\Subscription\Model\Rule\Action\Discount;

class Product extends \Riki\Subscription\Model\Rule\Action\Discount\AbstractDiscount
{
    protected function _getFreeItemsQty(
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Model\Quote $quote
    ) {
        $qty = 0;
        $amount = max(1, $rule->getDiscountAmount());
        $step = max(1, $rule->getDiscountStep());
        foreach ($quote->getAllVisibleItems() as $item) {
            if (!$item) {
                continue;
            }

            if ($this->promoItemHelper->isPromoItem($item)) {
                continue;
            }

            if (!$rule->getActions()->validate($item)) {
                continue;
            }

            if ($item->getParentItemId()) {
                continue;
            }

            if ($item->getProduct()->getParentProductId()) {
                continue;
            }

            $unitQty = 1;
            if ($item->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                $unitQty = $item->getUnitQty()? $item->getUnitQty() : 1;
            }

            $itemQty = $item->getQty() / $unitQty;

            $qty = $qty + $itemQty;
        }

        $quote->setFreeItemsQty($qty);

        /*dispatch event to collect more quantity for items which not in current quote*/
        $this->_eventManager->dispatch(
            'get_free_item_qty_after_get_quote_item_qty',
            ['quote' => $quote, 'rule' => $rule, 'qty' => $qty]
        );

        $qty = floor($quote->getFreeItemsQty() / $step) * $amount;
        $max = $rule->getDiscountQty();
        if ($max) {
            $qty = min($max, $qty);
        }

        return $qty;
    }
}
