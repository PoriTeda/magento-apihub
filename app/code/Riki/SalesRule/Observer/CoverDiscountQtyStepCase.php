<?php

namespace Riki\SalesRule\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\SalesRule\Model\Rule;

class CoverDiscountQtyStepCase implements ObserverInterface
{
    const QUOTE_ITEM_DISCOUNT_QTY_BY_RULE_KEY = 'quote_item_discount_qty_by_rule';

    /**
     * @var \Magento\SalesRule\Model\Validator
     */
    private $validator;

    /**
     * @var Rule\Action\Discount\CalculatorFactory
     */
    private $calculatorFactory;

    /**
     * CoverDiscountQtyStepCase constructor.
     * @param \Magento\SalesRule\Model\Validator $validator
     * @param Rule\Action\Discount\CalculatorFactory $calculatorFactory
     */
    public function __construct(
        \Magento\SalesRule\Model\Validator $validator,
        \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $calculatorFactory
    ) {
        $this->validator = $validator;
        $this->calculatorFactory = $calculatorFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $rule = $observer->getEvent()->getRule();
        $ruleType = $rule->getSimpleAction();

        if (!$quote->getIsMultipleShipping()
        || !in_array($ruleType, [Rule::BY_FIXED_ACTION, Rule::BY_PERCENT_ACTION, Rule::BUY_X_GET_Y_ACTION])) {
            return;
        }

        $item = $observer->getEvent()->getItem();
        $qty = $observer->getEvent()->getQty();
        /** @var \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData */
        $discountData = $observer->getEvent()->getResult();

        if ($ruleType == Rule::BUY_X_GET_Y_ACTION) {
            $itemPrice = $this->validator->getItemPrice($item);
            $totalDiscountQty = $discountData->getAmount() / $itemPrice;
        } else {
            $totalDiscountQty = $qty;
        }

        $discountQty = $this->getDiscountQty($rule, $quote, $item, $totalDiscountQty);

        $discountCalculator = $this->calculatorFactory->create($ruleType);

        $x = $rule->getDiscountStep();
        $y = $rule->getDiscountAmount();

        if ($ruleType == Rule::BUY_X_GET_Y_ACTION) {
            $rule->setDiscountStep($discountQty);
            $rule->setDiscountAmount($discountQty);
            $discountQty = $discountQty * 2;
        }

        $itemDiscountData = $discountCalculator->calculate($rule, $item, $discountQty);

        if ($ruleType == Rule::BUY_X_GET_Y_ACTION) {
            $rule->setDiscountStep($x);
            $rule->setDiscountAmount($y);
        }

        $discountData->setAmount($itemDiscountData->getAmount());
        $discountData->setBaseAmount($itemDiscountData->getBaseAmount());
        $discountData->setOriginalAmount($itemDiscountData->getOriginalAmount());
        $discountData->setBaseOriginalAmount($itemDiscountData->getBaseOriginalAmount());
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param Rule $rule
     * @return mixed|null
     */
    protected function getItemDiscountQtyByRule(
        \Magento\Quote\Model\Quote\Item $item,
        \Magento\SalesRule\Model\Rule $rule
    ) {
        $ruleId = $rule->getId();

        $discountQty = $item->getData(self::QUOTE_ITEM_DISCOUNT_QTY_BY_RULE_KEY);

        if (is_array($discountQty)) {
            if (isset($discountQty[$ruleId])) {
                return $discountQty[$ruleId];
            }
        }

        return null;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param $ruleId
     * @param $discountQty
     * @return $this
     */
    protected function setItemDiscountQtyByRule(
        \Magento\Quote\Model\Quote\Item $item,
        $ruleId,
        $discountQty
    ) {
        $itemDiscountQty = $item->getData(self::QUOTE_ITEM_DISCOUNT_QTY_BY_RULE_KEY);

        if (!is_array($itemDiscountQty)) {
            $itemDiscountQty = [];
        }

        $itemDiscountQty[$ruleId] = $discountQty;

        $item->setData(self::QUOTE_ITEM_DISCOUNT_QTY_BY_RULE_KEY, $itemDiscountQty);

        return $this;
    }

    /**
     * @param Rule $rule
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param $totalDiscountQty
     * @return mixed|null
     */
    protected function getDiscountQty(
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Item $item,
        $totalDiscountQty
    ) {
        $discountQty = $this->getItemDiscountQtyByRule($item, $rule);

        if ($discountQty === null) {
            $discountQty = $totalDiscountQty;

            if ($discountQty > 0) {
                /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
                foreach ($quote->getAllItems() as $quoteItem) {
                    if ($quoteItem->getProductId() == $item->getProductId()) {
                        $discountQty -= $this->getItemDiscountQtyByRule($quoteItem, $rule);
                    }
                }
            }

            $discountQty = min($discountQty, $item->getQty());

            $this->setItemDiscountQtyByRule($item, $rule->getId(), $discountQty);
        }

        return $discountQty;
    }
}
