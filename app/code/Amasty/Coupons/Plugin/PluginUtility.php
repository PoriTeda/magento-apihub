<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Coupons\Plugin;

class PluginUtility
{
    /**
     * Check if rule can be applied for specific address/quote/custome
     * @param ChildAround $subject
     * @param callable $proceed
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function aroundCanProcessRule($subject, \Closure $proceed, $rule, $address)
    {
        $originalCouponCode = $address->getQuote()->getCouponCode();
        $coupons = explode(',', $originalCouponCode);
        if (in_array($rule->getCode(), $coupons)) {
            $address->getQuote()->setCouponCode($rule->getCode());
            if ($proceed($rule, $address)) {
                //restore original coupon
                $address->getQuote()->setCouponCode($originalCouponCode);
                return true;
            } else {
                //restore original coupon
                $address->getQuote()->setCouponCode($originalCouponCode);
                return false;
            }
        }
        return $proceed($rule, $address);
    }
}
