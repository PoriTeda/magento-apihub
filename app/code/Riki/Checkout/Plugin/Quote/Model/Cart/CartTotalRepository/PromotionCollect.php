<?php
namespace Riki\Checkout\Plugin\Quote\Model\Cart\CartTotalRepository;

use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Cart\CartTotalRepository;

class PromotionCollect
{
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Api\Data\TotalsExtensionFactory
     */
    protected $totalsExtensionFactory;

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $salesRuleRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $rikiPromoHelper;

    /**
     * PromotionCollect constructor.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\SalesRule\Api\RuleRepositoryInterface $salesRuleRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Api\Data\TotalsExtensionFactory $totalsExtensionFactory
     * @param \Riki\Promo\Helper\Data $rikiPromoHelper
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\SalesRule\Api\RuleRepositoryInterface $salesRuleRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\Data\TotalsExtensionFactory $totalsExtensionFactory,
        \Riki\Promo\Helper\Data $rikiPromoHelper
    )
    {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->salesRuleRepository = $salesRuleRepository;
        $this->quoteRepository = $quoteRepository;
        $this->totalsExtensionFactory = $totalsExtensionFactory;
        $this->rikiPromoHelper = $rikiPromoHelper;
    }

    /**
     * @param TotalRepository $subject
     * @param TotalsInterface $totals
     * @param int $cartId
     * @return TotalsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(CartTotalRepository $subject, TotalsInterface $totals, $cartId)
    {
        /** @var \Magento\Quote\Model\Quote  $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        if (!$quote->getAppliedRuleIds()) {
            return $totals;
        }

        /** @var \Magento\Quote\Api\Data\TotalsExtensionInterface $extensionAttributes */
        $extensionAttributes = $totals->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->totalsExtensionFactory->create();
        }

        $criteria = $this->searchCriteriaBuilder
            ->addFilter('rule_id', explode(',', $quote->getAppliedRuleIds()), 'in')
            ->create();
        $salesRule = $this->salesRuleRepository->getList($criteria);
        $ruleData = [];
        $visibleRuleActions = [
            \Magento\SalesRule\Model\Rule::BY_FIXED_ACTION,
            \Magento\SalesRule\Model\Rule::CART_FIXED_ACTION,
            \Magento\SalesRule\Model\Rule::BY_PERCENT_ACTION,
            \Magento\SalesRule\Model\Rule::BUY_X_GET_Y_ACTION
        ];
        foreach ($salesRule->getItems() as $salesRuleItem) {
            $title = $salesRuleItem->getName();
            foreach ($salesRuleItem->getStoreLabels() as $storeLabel) {
                if ($storeLabel->getStoreId() == 0) { // global store
                    $title = $storeLabel->getStoreLabel();
                    continue;
                }
                if ($storeLabel->getStoreId() == $quote->getStoreId()) {
                    $title = $storeLabel->getStoreLabel();
                    break;
                }
            }

            $ruleId = $salesRuleItem->getRuleId();
            $visible = in_array($salesRuleItem->getSimpleAction(), $visibleRuleActions);
            $visible = $visible ?: $this->rikiPromoHelper->isFreeGiftVisibleInCart($ruleId);
            $ruleData[] = [
                'type' => 'sales_rule',
                'id' => $ruleId,
                'title' => trim($title),
                'visible' => intval($visible)
            ];
        }
        $extensionAttributes->setPromotionRules($ruleData);
        $totals->setExtensionAttributes($extensionAttributes);

        return $totals;
    }
}