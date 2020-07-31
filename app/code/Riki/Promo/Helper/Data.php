<?php
namespace Riki\Promo\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const PRODUCT_GIFT_FLAG_NAME = 'is_amasty_free_gift';

    protected $_amtPromoRule;

    protected $_promoItemHelper;

    protected $_freeGiftVisibleInCartRules = [];
    protected $_freeGiftVisibleInUserAccountRules = [];

    protected $_promoRuleConnection;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface  */
    protected $productRepository;

    protected $_appliedProductIdsRules = [];

    protected $_productIdsToQty = [];

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Amasty\Promo\Helper\Item $itemHelper
     * @param \Amasty\Promo\Model\Rule $rule
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Amasty\Promo\Helper\Item $itemHelper,
        \Amasty\Promo\Model\Rule $rule,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {

        $this->productRepository = $productRepository;
        $this->_amtPromoRule = $rule;
        $this->_promoItemHelper = $itemHelper;
        $this->_promoRuleConnection = $rule->getResource()->getConnection();

        parent::__construct($context);
    }

    /**
     * @param $ruleId
     * @return boolean
     */
    public function isFreeGiftVisibleInCart($ruleId){

        if (!isset($this->_freeGiftVisibleInCartRules[$ruleId])) {
            $ampromoRule = $this->_amtPromoRule->load($ruleId, 'salesrule_id');

            $this->_freeGiftVisibleInCartRules[$ruleId] = $ampromoRule->getAttVisibleCart();
        }

        return $this->_freeGiftVisibleInCartRules[$ruleId];
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function isVisibleFreeGiftItem(\Magento\Quote\Model\Quote\Item $item){

        if ($this->isPromoItem($item)) {
            $ruleId = $this->_promoItemHelper->getRuleId($item);
            return $this->isFreeGiftVisibleInCart($ruleId);
        }

        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function isVisibleFreeGiftOrderItem(\Magento\Sales\Model\Order\Item $item)
    {

        $buyRequest = $item->getBuyRequest();
        $ruleId = isset($buyRequest['options']['ampromo_rule_id'])
            ? $buyRequest['options']['ampromo_rule_id'] : null;

        if ($ruleId) {
            return $this->isFreeGiftVisibleInCart($ruleId);
        }

        return true;
    }

    /**
     * @param $ruleId
     * @return boolean
     */
    public function isVisibleFreeGiftInUserAccountRule($ruleId){

        if (!isset($this->_freeGiftVisibleInUserAccountRules[$ruleId])) {
            $ampromoRule = $this->_amtPromoRule->load($ruleId, 'salesrule_id');

            $this->_freeGiftVisibleInUserAccountRules[$ruleId] = $ampromoRule->getAttVisibleUserAccount();
        }

        return $this->_freeGiftVisibleInUserAccountRules[$ruleId];
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function isVisibleFreeGiftInUserAccountItem(\Magento\Quote\Model\Quote\Item $item){
        if ($this->isPromoItem($item)) {
            $ruleId = $this->_promoItemHelper->getRuleId($item);
            return $this->isVisibleFreeGiftInUserAccountRule($ruleId);
        }

        return true;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function isPromoItem(\Magento\Quote\Model\Quote\Item $item)
    {
        return $this->_promoItemHelper->isPromoItem($item);
    }

    /**
     * @param array $ids
     * @return array
     */
    public function filterVisibleInUserAccountRuleByIds(array $ids){
        $select = $this->_promoRuleConnection->select()->from(
            'amasty_ampromo_rule',
            ['salesrule_id']
        )->where(
            'amasty_ampromo_rule.salesrule_id IN (?)',
            $ids
        )->where(
            'amasty_ampromo_rule.att_visible_user_account = 1'
        );

        return $this->_promoRuleConnection->fetchCol($select);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function isPromoOrderItem(\Magento\Sales\Model\Order\Item $item)
    {
        $buyRequest = $item->getBuyRequest();
        $ruleId = isset($buyRequest['options']['ampromo_rule_id'])
            ? $buyRequest['options']['ampromo_rule_id'] : null;

        if ((int)$ruleId) {
            return true;
        }

        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param $ruleId
     * @return bool
     */
    public function canApplyRuleForQuoteItem(\Magento\Quote\Model\Quote\Item $item, $ruleId){

        $productId = $item->getProductId();

        $quoteId = $item->getQuoteId();

        if ($quoteId) {
            if (!isset($this->_appliedProductIdsRules[$quoteId])) {
                $this->_appliedProductIdsRules[$quoteId] = [];
            }

            if (isset($this->_appliedProductIdsRules[$quoteId][$productId])) {
                if (in_array($ruleId, $this->_appliedProductIdsRules[$quoteId][$productId])) {
                    return false;
                } else {
                    $this->_appliedProductIdsRules[$quoteId][$productId][] = $ruleId;
                }
            } else {
                $this->_appliedProductIdsRules[$quoteId][$productId] = [$ruleId];
            }

            return true;
        }

        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return mixed
     */
    public function getTotalQtyOfSameProductId(\Magento\Quote\Model\Quote\Item $item){
        $productId = $item->getProductId();

        $quoteId = $item->getQuoteId();

        if (isset($this->_productIdsToQty[$quoteId])) {
            if (isset($this->_productIdsToQty[$quoteId][$productId])) {
                return $this->_productIdsToQty[$quoteId][$productId];
            }
        }

        $quoteItems = $item->getQuote()->getAllItems();

        $productIdsToQty = [];

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getParentItemId() ||
                $this->isPromoItem($quoteItem)
            ) {
                continue;
            }

            $itemProductId = $quoteItem->getProductId();

            if (!isset($productIdsToQty[$itemProductId])) {
                $productIdsToQty[$itemProductId] = 0;
            }

            $productIdsToQty[$itemProductId] += $quoteItem->getQty();
        }

        $this->_productIdsToQty[$quoteId] = $productIdsToQty;

        return isset($this->_productIdsToQty[$quoteId][$productId])? $this->_productIdsToQty[$quoteId][$productId] : 0;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $sku
     * @return bool
     */
    public function ableToAddSkuToQuote(\Magento\Quote\Model\Quote $quote, $sku)
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (\Exception $e) {
            return false;
        }

        $websiteId = $quote->getStore()->getWebsiteId();

        if (!is_array($product->getWebsiteIds())
            || !in_array($websiteId, $product->getWebsiteIds())) {
            // Ignore products from other websites
            return false;
        }

        return true;
    }

    /**
     * Get item rule id
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return int|null
     */
    public function getRuleId(\Magento\Quote\Model\Quote\Item $item)
    {
        return $this->_promoItemHelper->getRuleId($item);
    }
}
