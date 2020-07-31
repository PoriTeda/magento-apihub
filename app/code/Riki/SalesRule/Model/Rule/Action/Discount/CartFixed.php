<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SalesRule\Model\Rule\Action\Discount;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\SalesRule\Model\DeltaPriceRound;
use Magento\SalesRule\Model\Rule\Action\Discount\CartFixed as DefaultCartFixed;
use Magento\SalesRule\Model\Validator;

class CartFixed extends DefaultCartFixed
{
    /**
     * Store information about addresses which cart fixed rule applied for
     *
     * @var int[]
     */
    protected $_cartFixedRuleUsedForAddress = [];

    /**
     * @var DeltaPriceRound
     */
    private $deltaPriceRound;

    /**
     * @var string
     */
    private static $discountType = 'CartFixed';

    /**
     * @param Validator $validator
     * @param DataFactory $discountDataFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param DeltaPriceRound $deltaPriceRound
     */
    public function __construct(
        Validator $validator,
        \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory,
        PriceCurrencyInterface $priceCurrency,
        DeltaPriceRound $deltaPriceRound
    ) {
        $this->deltaPriceRound = $deltaPriceRound;

        parent::__construct($validator, $discountDataFactory, $priceCurrency, $deltaPriceRound);
    }

    public function calculate($rule, $item, $qty)
    {
        /** @var \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData */
        $discountData = $this->discountFactory->create();

        $ruleTotals = $this->validator->getRuleItemTotalsInfo($rule->getId());

        $quote = $item->getQuote();
        $address = $item->getAddress();

        $itemPrice = $this->validator->getItemPrice($item);
        $baseItemPrice = $this->validator->getItemBasePrice($item);
        $itemOriginalPrice = $this->validator->getItemOriginalPrice($item);
        $baseItemOriginalPrice = $this->validator->getItemBaseOriginalPrice($item);

        /**
         * prevent applying whole cart discount for every shipping order, but only for first order
         */
        if ($quote->getIsMultiShipping()) {
            $usedForAddressId = $this->getCartFixedRuleUsedForAddress($rule->getId());
            if ($usedForAddressId && $usedForAddressId != $address->getId()) {
                return $discountData;
            } else {
                $this->setCartFixedRuleUsedForAddress($rule->getId(), $address->getId());
            }
        }
        $cartRules = $address->getCartFixedRules();
        if (!isset($cartRules[$rule->getId()])) {
            $cartRules[$rule->getId()] = $rule->getDiscountAmount();
        }
        $availableDiscountAmount = (float)$cartRules[$rule->getId()];
        $discountType = self::$discountType . $rule->getId();

        if ($availableDiscountAmount > 0) {
            $store = $quote->getStore();
            if ($ruleTotals['items_count'] <= 1) {
                $quoteAmount = $this->priceCurrency->convert($availableDiscountAmount, $store);
                $baseDiscountAmount = min($baseItemPrice * $qty, $availableDiscountAmount);
                $this->deltaPriceRound->reset($discountType);
            } else {
                $ratio = 1;
                if($ruleTotals['base_items_price']) {
                    $ratio = $baseItemPrice * $qty / $ruleTotals['base_items_price'];
                }
                $maximumItemDiscount = $this->deltaPriceRound->round(
                    $rule->getDiscountAmount() * $ratio,
                    $discountType
                );

                $quoteAmount = $this->priceCurrency->convert($maximumItemDiscount, $store);

                $baseDiscountAmount = min($baseItemPrice * $qty, $maximumItemDiscount);
                $this->validator->decrementRuleItemTotalsCount($rule->getId());
            }

            $baseDiscountAmount = $this->priceCurrency->round($baseDiscountAmount);

            $availableDiscountAmount -= $baseDiscountAmount;
            $cartRules[$rule->getId()] = $availableDiscountAmount;
            if ($availableDiscountAmount <= 0) {
                $this->deltaPriceRound->reset($discountType);
            }

            $discountData->setAmount($this->priceCurrency->round(min($itemPrice * $qty, $quoteAmount)));
            $discountData->setBaseAmount($baseDiscountAmount);
            $discountData->setOriginalAmount(min($itemOriginalPrice * $qty, $quoteAmount));
            $discountData->setBaseOriginalAmount($this->priceCurrency->round($baseItemOriginalPrice));
        }
        $address->setCartFixedRules($cartRules);

        return $discountData;
    }
}
