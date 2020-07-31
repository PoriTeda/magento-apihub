<?php
/**
 * Coupons
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Coupons
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Coupons\Plugin\Checkout\Controller\Cart;

/**
 * Class CouponPost
 *
 * @category  RIKI
 * @package   Riki\Coupons\Plugin
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CouponPost
{
    /**
     * Cart
     *
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * CouponHelper
     *
     * @var \Riki\Coupons\Helper\Coupon
     */
    protected $couponHelper;

    /**
     * CouponPost constructor.
     * @param \Riki\Coupons\Helper\Coupon $couponHelper
     * @param \Magento\Checkout\Model\Cart $cart
     */
    public function __construct(
        \Riki\Coupons\Helper\Coupon $couponHelper,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->couponHelper = $couponHelper;
        $this->cart = $cart;
    }

    /**
     * Extend execute
     *
     * @param \Magento\Checkout\Controller\Cart\CouponPost $subject subject
     *
     * @return array
     */
    public function beforeExecute(
        \Magento\Checkout\Controller\Cart\CouponPost $subject
    ) {
        $quote = $this->cart->getQuote();
        if ($quote && $quote->getId() && $quote->getTotalsCollectedFlag()) {
            // make sure collect total on couponPost action @see RIKI-4911
            $quote->setTotalsCollectedFlag(false);
        }

        $couponCode = $subject->getRequest()->getParam('coupon_code');
        $couponCode = str_replace(',', '&#44;', (string)$couponCode);
        if ($couponCode) {
            $couponCode = $this->couponHelper->getValidCouponCode($couponCode);
            $subject->getRequest()->setParam('coupon_code', $couponCode);
        }

        return [$subject];
    }
}