<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogRuleStaging\Pricing\Price;

use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\ResourceModel\RuleFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Pricing\Adjustment\Calculator;
use Magento\Framework\Pricing\Price\BasePriceProviderInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManager;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Staging\Model\VersionManager;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CatalogRulePrice extends \Magento\CatalogRule\Pricing\Price\CatalogRulePrice implements BasePriceProviderInterface
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var \Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param Product $saleableItem
     * @param float $quantity
     * @param Calculator $calculator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param TimezoneInterface $dateTime
     * @param StoreManager $storeManager
     * @param Session $customerSession
     * @param RuleFactory $catalogRuleResourceFactory
     * @param CollectionFactory $ruleCollectionFactory
     * @param VersionManager $versionManager
     * @param ProductRepositoryInterface $productRepository
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Product $saleableItem,
        $quantity,
        Calculator $calculator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        TimezoneInterface $dateTime,
        StoreManager $storeManager,
        Session $customerSession,
        RuleFactory $catalogRuleResourceFactory,
        CollectionFactory $ruleCollectionFactory,
        VersionManager $versionManager,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct(
            $saleableItem,
            $quantity,
            $calculator,
            $priceCurrency,
            $dateTime,
            $storeManager,
            $customerSession,
            $catalogRuleResourceFactory
        );
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->versionManager = $versionManager;
        $this->productRepository = $productRepository;
    }

    /**
     * Returns catalog rule value
     *
     * @return float|boolean
     */
    public function getValue()
    {
        if (!$this->versionManager->isPreviewVersion()) {
            return parent::getValue();
        }
        if (null === $this->value) {
            $this->value = $this->product->getPrice();
            $activeRules = $this->getActiveRules(
                $this->storeManager->getStore()->getWebsiteId(),
                $this->customerSession->getCustomerGroupId()
            );
            /** @var  \Magento\CatalogRule\Model\Rule $rule */
            foreach ($activeRules as $rule) {
                if ($this->product->getParentId()) {
                    $this->product = $this->productRepository->getById($this->product->getParentId());
                }

                if ($rule->validate($this->product)) {
                    $this->value = $this->calculateRuleProductPrice($rule, $this->value);
                    if ($rule->getStopRulesProcessing()) {
                        break;
                    }
                }
            }
        }
        return $this->value;
    }

    /**
     * Get active rules
     *
     * @param int $websiteId
     * @param int $customerGroupId
     *
     * @return array
     */
    protected function getActiveRules($websiteId, $customerGroupId)
    {
        /** @var \Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollection */
        return $this->ruleCollectionFactory->create()
            ->addWebsiteFilter($websiteId)
            ->addCustomerGroupFilter($customerGroupId)
            ->addFieldToFilter('is_active', 1)
            ->setOrder('sort_order', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
    }

    /**
     * Calculate rule product price
     *
     * @param \Magento\CatalogRule\Model\Rule $rule
     * @param float $currentProductPrice
     * @return float
     */
    protected function calculateRuleProductPrice(\Magento\CatalogRule\Model\Rule $rule, $currentProductPrice)
    {
        switch ($rule->getSimpleAction()) {
            case 'to_fixed':
                $currentProductPrice = min($rule->getDiscountAmount(), $currentProductPrice);
                break;
            case 'to_percent':
                $currentProductPrice = $currentProductPrice * $rule->getDiscountAmount() / 100;
                break;
            case 'by_fixed':
                $currentProductPrice = max(0, $currentProductPrice - $rule->getDiscountAmount());
                break;
            case 'by_percent':
                $currentProductPrice = $currentProductPrice * (1 - $rule->getDiscountAmount() / 100);
                break;
            default:
                $currentProductPrice = 0;
        }

        return $this->priceCurrency->round($currentProductPrice);
    }
}
