<?php
namespace Riki\Subscription\Model\Rule\Action\Discount;

class SameProduct extends \Riki\Subscription\Model\Rule\Action\Discount\AbstractDiscount
{

    /**
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param $qty
     */
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

        $unitQty = 1;
        if($item->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE){
            $unitQty = $item->getUnitQty()? $item->getUnitQty() : 1;
        }

        $itemQty = $this->_rikiPromoHelper->getTotalQtyOfSameProductId($item) / $unitQty;

        $qty = min(
            floor($itemQty / $discountStep) * $discountAmount,
            $maxDiscountQty
        );

        if ($item->getParentItemId())
            return;

        if ($item['product_type'] == 'downloadable')
            return;

        if ($qty < 1)
            return;

        $this->addPromoItem(
            $item->getQuote(),
            $item->getProduct()->getData('sku'),
            $qty,
            $rule->getId()
        );
    }
}