<?php

namespace Riki\SalesRule\Model;

use Magento\Quote\Model\Quote\Address;
use Riki\CatalogRule\Model\Rule\SubscriptionDeliveryOptionsProvider;
use Riki\SalesRule\Helper\Rule as RuleHelper;

class Validator extends \Magento\SalesRule\Model\Validator
{
    /**
     * Filter rules based on subscription settings
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     *
     * @return $this
     */
    public function init($websiteId, $customerGroupId, $couponCode)
    {
        parent::init($websiteId, $customerGroupId, $couponCode);

        return $this;
    }

    /**
     * @return \Magento\SalesRule\Model\ResourceModel\Rule\Collection|array
     */
    public function getRules()
    {
        return $this->_getRules();
    }

    /**
     * @inheritdoc
     */
    protected function _getRules(Address $address = null)
    {
        $quote = $quote = $this->getData('quote');
        $isGillette = $quote->getData('is_gillette_quote');
        $cartRuleIds = [];
        if ($quote->getData('cart_rules_can_apply')) {
            $cartRuleIds = explode(',', trim($quote->getData('cart_rules_can_apply')));
        }
        //Get rules with magento default key
        $addressId = $this->getAddressId($address);
        $key = $this->getWebsiteId() . '_'
            . $this->getCustomerGroupId() . '_'
            . $this->getCouponCode() . '_'
            . $addressId;
        if (!isset($this->_rules[$key]) || !isset($this->_rules[$this->_getKey()])) {
            $ruleCollection = $this->_collectionFactory->create()
                ->setValidationFilter(
                    $this->getWebsiteId(),
                    $this->getCustomerGroupId(),
                    $this->getCouponCode(),
                    null,
                    $address
                )
                ->addFieldToFilter('is_active', 1);
            /** Gillette order allow apply some specific cart rule */
            if ($isGillette and sizeof($cartRuleIds) > 0) {
                $ruleCollection->addFieldToFilter('rule_id', ['in' => $cartRuleIds]);
            }
            $this->_rules[$key] = $ruleCollection->load();

            // Add riki_course_id,riki_frequency_id,n_delivery to the sale rule keys to handle subscription course
            if ($quote && !isset($this->_rules[$this->_getKey()])) {
                $this->_rules[$this->_getKey()] = $this->_rules[$key];

                $filteredRules = [];
                if (count($this->_rules[$key])) {
                    $resource = $this->_rules[$key]->getFirstItem()->getResource();

                    $courseId = $quote->getData('riki_course_id');
                    $frequencyId = $quote->getData('riki_frequency_id');
                    $nDelivery = $quote->getData('n_delivery') ? $quote->getData('n_delivery') : 1;

                    $subscriptionRules = $courseId ? $resource->getSubscriptionRules($courseId, $frequencyId) : [];

                    foreach ($this->_rules[$key] as $rule) {
                        if ($courseId) {
                            if (RuleHelper::SUBSCRIPTION_SPOT_SPOT_ONLY != $rule->getSubscription()) {
                                if (!in_array($rule->getId(), $subscriptionRules)) {
                                    continue;
                                }

                                if (SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_ALL == $rule->getSubscriptionDelivery()) {
                                    $filteredRules[] = $rule;
                                    continue;
                                }

                                if (SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_ON_N == $rule->getSubscriptionDelivery()
                                    && (int)$rule->getDeliveryN() == $nDelivery
                                ) {
                                    $filteredRules[] = $rule;
                                    continue;
                                }

                                if (SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_EVERY_N == $rule->getSubscriptionDelivery()
                                    && ($nDelivery % (int)$rule->getDeliveryN() == 0)
                                ) {
                                    $filteredRules[] = $rule;
                                    continue;
                                }

                                if (SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_FROM_N == $rule->getSubscriptionDelivery()
                                    && $nDelivery >= (int)$rule->getDeliveryN()
                                ) {
                                    $filteredRules[] = $rule;
                                }
                            }
                        } elseif (RuleHelper::SUBSCRIPTION_SPOT_SPOT_ONLY == $rule->getSubscription()
                            || RuleHelper::SUBSCRIPTION_SPOT_BOTH == $rule->getSubscription()
                        ) {
                            $filteredRules[] = $rule;
                        }
                    }
                }

                $this->_rules[$this->_getKey()] = $filteredRules;
            }

        }

        return $this->_rules[$this->_getKey()];
    }

    /**
     * Use additional criteria to build cache key if quote object exists
     * 1. subscription/spot/both
     * 2. applicable for current quote's subscription
     * 3. frequency
     * 4. delivery N
     *
     * @return string
     */
    protected function _getKey()
    {
        $keys = [
            $this->getWebsiteId(),
            $this->getCustomerGroupId(),
            $this->getCouponCode()
        ];

        if (($quote = $this->getData('quote'))) {
            $keys = array_merge($keys, [
                $quote->getData('riki_course_id'),
                $quote->getData('riki_frequency_id'),
                $quote->getData('n_delivery') ? $quote->getData('n_delivery') : 1,
            ]);
        }

        return implode('_', $keys);
    }

    /**
     * Return items list sorted by possibility to apply prioritized rules
     *
     * @param array $items
     *
     * @param Address|null $address
     * @return array $items
     */
    public function sortItemsByPriority($items, Address $address = null)
    {
        $itemsSorted = [];

        /** @var $rule \Magento\SalesRule\Model\Rule */
        foreach ($this->_getRules() as $rule) {
            foreach ($items as $itemKey => $itemValue) {
                if ($rule->getActions()->validate($itemValue)) {
                    unset($items[$itemKey]);
                    array_push($itemsSorted, $itemValue);
                }
            }
        }

        if (!empty($itemsSorted)) {
            // Reverse Item
            $itemsSorted = array_reverse($itemsSorted);

            // Sort by highest price
            usort($itemsSorted, function ($a, $b) {
                return $a->getData("price") > $b->getData("price");
            });

            $items = array_merge($itemsSorted, $items);
        }

        return $items;
    }

    /**
     * Calculate quote totals for each rule and save results
     *
     * @param mixed $items
     * @param Address $address
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initTotals($items, Address $address)
    {
        $address->setCartFixedRules([]);

        if (!$items) {
            return $this;
        }

        /** @var \Magento\SalesRule\Model\Rule $rule */
        foreach ($this->_getRules() as $rule) {
            if (\Magento\SalesRule\Model\Rule::CART_FIXED_ACTION == $rule->getSimpleAction()
                && $this->validatorUtility->canProcessRule($rule, $address)
            ) {
                $ruleTotalItemsPrice = 0;
                $ruleTotalBaseItemsPrice = 0;
                $validItemsCount = 0;

                foreach ($items as $item) {
                    //Skipping child items to avoid double calculations
                    if ($item->getParentItemId() || $item->getParentItem()) {
                        continue;
                    }
                    if (!$rule->getActions()->validate($item)) {
                        continue;
                    }
                    if (!$this->canApplyDiscount($item)) {
                        continue;
                    }
                    $qty = $this->validatorUtility->getItemQty($item, $rule);
                    $ruleTotalItemsPrice += $this->getItemPrice($item) * $qty;
                    $ruleTotalBaseItemsPrice += $this->getItemBasePrice($item) * $qty;
                    $validItemsCount++;
                }

                $this->_rulesItemTotals[$rule->getId()] = [
                    'items_price' => $ruleTotalItemsPrice,
                    'base_items_price' => $ruleTotalBaseItemsPrice,
                    'items_count' => $validItemsCount
                ];
            }
        }

        return $this;
    }
}
