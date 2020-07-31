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

class RuleCollection
{

    /**
     * @var \Magento\SalesRule\Model\Coupon
     */
    protected $_coupon;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\Usage
     */
    protected $_couponUsage;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * RuleCollection constructor.
     * @param \Magento\SalesRule\Model\Coupon $coupon
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\SalesRule\Model\Coupon $coupon,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Registry $registry
    ) {
        $this->_coupon = $coupon;
        $this->_scopeConfig = $scopeConfig;
        $this->_couponUsage = $couponUsage;
        $this->timezone = $timezone;
        $this->registry = $registry;
    }


    public function aroundSetValidationFilter(
        $subject,
        \Closure $proceed,
        $websiteId,
        $customerGroupId,
        $couponCode = '',
        $now = null
    ) {

        //$result = $proceed($websiteId, $customerGroupId, $couponCode, $now);
        //return $result;


        $uniqueCodes = $this->_scopeConfig->getValue(
            'amcoupons/general/unique_codes',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $uniqueCodesArray = explode(',', $uniqueCodes);

        if (array_intersect($uniqueCodesArray, explode(',', $couponCode))) {
            $couponCode = array_intersect($uniqueCodesArray, explode(',', $couponCode));
            $couponCode = $couponCode[0];
        }

        $result = $proceed($websiteId, $customerGroupId, $couponCode, $now);
        if ($this->registry->registry('is_gillette_order')) {
            return $result;
        }
        $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
        if ($couponCode !== '' && $couponCode) {
            $select = $subject->getSelect();
            $coupons = explode(',', $couponCode);
            foreach ($coupons as $coupon) {
                $select->orWhere('rule_coupons.code = ? AND main_table.is_active = 1', $coupon);
            }

            $select->where('(main_table.from_time IS NOT NULL AND main_table.to_time IS NULL AND main_table.from_time <= ? ) 
                OR (main_table.from_time IS NULL AND main_table.to_time IS NOT NULL AND main_table.to_time >= ? ) 
                OR (main_table.from_time IS NOT NULL AND main_table.to_time IS NOT NULL AND main_table.from_time <= ? AND main_table.to_time >= ?)'
                , $currentTime
            );

        }


        return $result;


    }
}
