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

class PluginObserver
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
     * @param \Magento\SalesRule\Model\Coupon $coupon
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage
     */
    public function __construct(
        \Magento\SalesRule\Model\Coupon $coupon,
        \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage
    ) {
        $this->_coupon = $coupon;
        $this->_couponUsage = $couponUsage;
    }

    public function aroundExecute($subject, \Closure $proceed, $observer)
    {
        $result = $proceed($observer);
        $order = $observer->getEvent()->getOrder();
        $customerId = $order->getCustomerId();
        $coupons =  array_map('trim', explode(',', $order->getCouponCode()));
        if (is_array($coupons)) {
            foreach ($coupons as $coupon) {
                $this->_coupon->load($coupon, 'code');
                if ($this->_coupon->getId()) {
                    $this->_coupon->setTimesUsed($this->_coupon->getTimesUsed() + 1);
                    $this->_coupon->save();
                    if ($customerId) {
                        $this->_couponUsage->updateCustomerCouponTimesUsed($customerId, $this->_coupon->getId());
                    }
                }
            }
        }
        return $result;
    }
}
