<?php
namespace Riki\Promo\Model\Rule\Action\Discount;

class Product extends \Amasty\Promo\Model\Rule\Action\Discount\Product
{
    /** @var \Riki\Promo\Helper\Data  */
    protected $rikiPromoHelper;

    /**
     * Product constructor.
     * @param \Magento\SalesRule\Model\Validator $validator
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Amasty\Promo\Helper\Item $promoItemHelper
     * @param \Amasty\Promo\Model\Registry $promoRegistry
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Riki\Promo\Helper\Data $rikiPromoHelper
     */
    public function __construct(
        \Magento\SalesRule\Model\Validator $validator,
        \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Riki\Promo\Helper\Data $rikiPromoHelper
    )
    {
        $this->rikiPromoHelper = $rikiPromoHelper;

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
        if (!$this->promoRegistry->getApplyAttempt($rule->getId())) {
            return;
        }

        $ampromoRule = $this->_objectManager->get('Amasty\Promo\Model\Rule');

        $ampromoRule = $ampromoRule->loadBySalesrule($rule);

        $promoSku = $ampromoRule->getSku();
        if (!$promoSku) {
            return;
        }

        $quote = $item->getQuote();

        $qty = $this->_getFreeItemsQty($rule, $quote);
        if (!$qty) {
            return;
        }

        $promoSku = preg_split('/\s*,\s*/', $promoSku, -1, PREG_SPLIT_NO_EMPTY);

        if ($promoSku) {
            foreach ($promoSku as $sku) {
                if ($this->rikiPromoHelper->ableToAddSkuToQuote($quote, $sku)) {
                    $this->promoRegistry->addPromoItem(
                        $sku,
                        $qty,
                        $rule->getId()
                    );

                    if ($ampromoRule->getType() == \Amasty\Promo\Model\Rule::RULE_TYPE_ONE) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote $quote
     * @return float|int|mixed
     */
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

            $unitQty = 1;
            if($item->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE){
                $unitQty = $item->getUnitQty()? $item->getUnitQty() : 1;
            }

            $itemQty = $item->getQty() / $unitQty;

            $qty = $qty + $itemQty;
        }

        $qty = floor($qty / $step) * $amount;
        $max = $rule->getDiscountQty();
        if ($max){
            $qty = min($max, $qty);
        }

        return $qty;
    }
}