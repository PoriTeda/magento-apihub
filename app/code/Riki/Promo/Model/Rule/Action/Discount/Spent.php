<?php

namespace Riki\Promo\Model\Rule\Action\Discount;

use Riki\Promo\Helper\Data;

class Spent extends \Amasty\Promo\Model\Rule\Action\Discount\Spent
{
    /** @var \Magento\Framework\Registry  */
    protected $registry;

    /** @var Data  */
    protected $rikiPromoHelper;

    /**
     * Spent constructor.
     * @param \Magento\SalesRule\Model\Validator $validator
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Amasty\Promo\Helper\Item $promoItemHelper
     * @param \Amasty\Promo\Model\Registry $promoRegistry
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param Data $rikiPromoHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\SalesRule\Model\Validator $validator,
        \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Riki\Promo\Helper\Data $rikiPromoHelper,
        \Magento\Framework\Registry $registry
    )
    {
        $this->rikiPromoHelper = $rikiPromoHelper;
        $this->registry = $registry;
        parent::__construct($validator, $discountDataFactory, $priceCurrency, $objectManager, $promoItemHelper, $promoRegistry, $productCollectionFactory);
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

        $subtotal = $this->registry->registry('riki_promo_subtotal') ? (float)$this->registry->registry('riki_promo_subtotal') : 0;
        $qty = floor($subtotal / $step) * $amount;

        $max = $rule->getDiscountQty();
        if ($max) {
            $qty = min($max, $qty);
        }

        return $qty;
    }
}