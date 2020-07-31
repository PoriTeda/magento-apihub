<?php

namespace Riki\OfflineShipping\Model\SalesRule;

class Calculator extends \Magento\OfflineShipping\Model\SalesRule\Calculator
{
    /**
     * @var \Riki\SalesRule\Model\Validator
     */
    protected $salesRuleValidator;

    /**
     * Calculator constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\SalesRule\Model\Utility $utility
     * @param \Magento\SalesRule\Model\RulesApplier $rulesApplier
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\SalesRule\Model\Validator\Pool $validators
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Riki\SalesRule\Model\Validator $salesRuleValidator
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collectionFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\SalesRule\Model\Utility $utility,
        \Magento\SalesRule\Model\RulesApplier $rulesApplier,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\SalesRule\Model\Validator\Pool $validators,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Riki\SalesRule\Model\Validator $salesRuleValidator,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $collectionFactory, $catalogData, $utility, $rulesApplier, $priceCurrency, $validators, $messageManager, $resource, $resourceCollection, $data);

        $this->salesRuleValidator = $salesRuleValidator;
    }

    /**
     * @inheritdoc
     */
    public function init($websiteId, $customerGroupId, $couponCode)
    {
        if (($quote = $this->getData('quote'))) {
            $this->salesRuleValidator->setData('quote', $quote);
            $this->salesRuleValidator->init($websiteId, $customerGroupId, $couponCode);
            return $this;
        } else {
            return parent::init($websiteId, $customerGroupId, $couponCode);
        }
    }

    /**
     * @inheritdoc
     */
    protected function _getRules(\Magento\Quote\Model\Quote\Address $address = null)
    {
        if ($this->getData('quote')) {
            return $this->salesRuleValidator->getRules();
        } else {
            return parent::_getRules();
        }
    }

    /**
     * Check if the rule has no a free shipping action, we will skip the rule.
     *
     * @see \Magento\OfflineShipping\Model\SalesRule\Calculator
     * @inheritdoc
     */
    public function processFreeShipping(\Magento\Quote\Model\Quote\Item\AbstractItem $item)
    {
        $address = $item->getAddress();
        $item->setFreeShipping(false);

        if ($item->getQuote()->getSkipCollectDiscountFlag()) {
            return $this;
        }

        if ($item->getQuote() && $item->getQuote()->getData('is_monthly_fee_confirmed')) {
            $item->setFreeShipping(true);
            return $this;
        }

        foreach ($this->_getRules() as $rule) {
            // Check if the rule has no a free shipping action, we will skip the rule.
            if (!$rule->getSimpleFreeShipping()) {
                continue;
            }

            /* @var $rule \Magento\SalesRule\Model\Rule */
            if (!$this->validatorUtility->canProcessRule($rule, $address)) {
                continue;
            }

            if (!$rule->getActions()->validate($item)) {
                continue;
            }

            switch ($rule->getSimpleFreeShipping()) {
                case \Magento\OfflineShipping\Model\SalesRule\Rule::FREE_SHIPPING_ITEM:
                    $item->setFreeShipping($rule->getDiscountQty() ? $rule->getDiscountQty() : true);
                    break;

                case \Magento\OfflineShipping\Model\SalesRule\Rule::FREE_SHIPPING_ADDRESS:
                    $address->setFreeShipping(true);
                    break;
            }
            if ($rule->getStopRulesProcessing()) {
                break;
            }
        }
        return $this;
    }
}
