<?php
namespace Riki\Promo\Model\Rule\Action\Discount;

class SameProduct extends \Amasty\Promo\Model\Rule\Action\Discount\SameProduct
{
    protected $_rikiHelper;

    public function __construct(
        \Magento\SalesRule\Model\Validator $validator,
        \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Riki\Promo\Helper\Data $rikiHelper
    ){

        $this->_rikiHelper = $rikiHelper;

        parent::__construct(
            $validator,
            $discountDataFactory,
            $priceCurrency,
            $objectManager,
            $promoItemHelper,
            $promoRegistry,
            $productCollectionFactory
        );
    }

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

        if(!$this->_rikiHelper->canApplyRuleForQuoteItem($item, $rule->getId()))
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

        $itemQty = $this->_rikiHelper->getTotalQtyOfSameProductId($item) / $unitQty;

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

        $this->promoRegistry->addPromoItem(
            $item->getProduct()->getData('sku'),
            $qty,
            $rule->getId()
        );
    }
}