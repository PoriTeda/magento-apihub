<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Riki\Coupons\Plugin;

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
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * PluginObserver constructor.
     * @param \Magento\SalesRule\Model\Coupon                     $coupon
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage
     * @param \Magento\Framework\Registry                         $registry
     */
    public function __construct(
        \Magento\SalesRule\Model\Coupon $coupon,
        \Magento\SalesRule\Model\ResourceModel\Coupon\Usage $couponUsage,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_coupon = $coupon;
        $this->_couponUsage = $couponUsage;
        $this->_registry = $registry;
        $this->logger = $logger;
    }

    public function aroundExecute($subject, \Closure $proceed, $observer)
    {
        $order = $observer->getEvent()->getOrder();

        /**
         * Check Simulator order
         */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $subject;
        }

        $result = $proceed($observer);
        $customerId = $order->getCustomerId();
        $coupons = array_map('trim', explode(',', $order->getCouponCode()));
        if (is_array($coupons) && count($coupons) > 1) {
            foreach ($coupons as $coupon) {
                $this->_coupon->load($coupon, 'code');
                if ($this->_coupon->getId()) {
                    try {
                        $this->_coupon->setTimesUsed($this->_coupon->getTimesUsed() + 1);
                        $this->_coupon->save();
                        if ($customerId) {
                            $this->_couponUsage->updateCustomerCouponTimesUsed($customerId, $this->_coupon->getId());
                        }
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                    }
                }
            }
        }

        return $result;
    }
}
