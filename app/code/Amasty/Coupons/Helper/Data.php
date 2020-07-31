<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */

namespace Amasty\Coupons\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_objectManager = $objectManager;
        $this->_cart = $cart;
        $this->resource = $resource;
    }

    public function getRealAppliedCodes()
    {
        $quote = $this->_cart->getQuote();

        if (!$quote->getCouponCode()) {
            return false;
        }

        $coupons =  array_map('trim', explode(',', $quote->getCouponCode()));
        $appliedRules = array_map('trim', explode(',', $quote->getAppliedRuleIds()));

        if (!$appliedRules) {
            return false;
        }

        foreach ($coupons as $key => $coupon) {
            $rule = $this->_objectManager->get('Magento\SalesRule\Model\Coupon')->loadByCode($coupon);
            if (!$this->_objectManager->get('Magento\SalesRule\Model\ResourceModel\Coupon')->exists($coupon)
                || !in_array($rule->getRuleId(), $appliedRules)) {
                unset($coupons[$key]);
            }
        }

        $coupons = array_unique($coupons);

        return $coupons;
    }

}