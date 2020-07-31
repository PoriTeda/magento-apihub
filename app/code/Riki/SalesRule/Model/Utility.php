<?php
namespace Riki\SalesRule\Model;

use Magento\SalesRule\Model\Rule;

class Utility extends \Magento\SalesRule\Model\Utility
{
    /**
     * Process "delta" rounding
     * Override the core method to fix the round method -
     * Always use round-normal even admin setting is round-down or round-up
     *
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function deltaRoundingFix(
        \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item
    ) {
        $store = $item->getQuote()->getStore();
        $discountAmount = $discountData->getAmount();
        $baseDiscountAmount = $discountData->getBaseAmount();

        //that can not be used as array index
        $percentKey = $item->getDiscountPercent();
        if ($percentKey) {
            $delta = isset($this->_roundingDeltas[$percentKey]) ? $this->_roundingDeltas[$percentKey] : 0;
            $baseDelta = isset($this->_baseRoundingDeltas[$percentKey]) ? $this->_baseRoundingDeltas[$percentKey] : 0;

            $discountAmount += $delta;
            $baseDiscountAmount += $baseDelta;

            $this->_roundingDeltas[$percentKey] = $discountAmount - round($discountAmount);
            $this->_baseRoundingDeltas[$percentKey] = $baseDiscountAmount - round($baseDiscountAmount);
        }

        $discountData->setAmount(round($discountAmount));
        $discountData->setBaseAmount(round($baseDiscountAmount));

        return $this;
    }

    /**
     * should merge qty for case multiple shipping
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Magento\SalesRule\Model\Rule $rule
     * @return float|int|mixed
     */
    public function getItemQty($item, $rule)
    {
        $quote = $item->getQuote();

        if ($quote->getIsMultipleShipping()
            && in_array($rule->getSimpleAction(), [
                Rule::BY_FIXED_ACTION,
                Rule::BY_PERCENT_ACTION,
                Rule::BUY_X_GET_Y_ACTION
            ])) {
            $qty = 0;
            $productId = $item->getProductId();

            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            foreach ($quote->getAllItems() as $quoteItem) {
                if ($quoteItem->getParentItemId() || !$quoteItem->getPrice()) {
                    continue;
                }

                if ($quoteItem->getProductId() == $productId) {
                    $qty += $quoteItem->getQty();
                }
            }
        } else {
            $qty = $item->getTotalQty();
        }

        $discountQty = $rule->getDiscountQty();
        return $discountQty ? min($qty, $discountQty) : $qty;
    }
}
