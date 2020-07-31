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

namespace Riki\Coupons\Observer;

/**
 * Class AdminhtmlSalesOrderCreateProcessDataBefore
 *
 * @category  RIKI
 * @package   Riki\Coupons\Observer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class AdminhtmlSalesOrderCreateProcessDataBefore
    implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * LastPostCoupon
     *
     * @var string
     */
    protected $lastPostCoupon;

    /**
     * CouponHelper
     *
     * @var \Riki\Coupons\Helper\Coupon
     */
    protected $couponHelper;

    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * AdminhtmlSalesOrderCreateProcessDataBefore constructor.
     *
     * @param \Magento\Framework\Registry $registry     registry
     * @param \Riki\Coupons\Helper\Coupon $couponHelper couponHelper
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\Coupons\Helper\Coupon $couponHelper
    ) {
        $this->registry = $registry;
        $this->couponHelper = $couponHelper;
    }

    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $request = $observer->getRequestModel();
        $session = $observer->getSession();

        $postValue = $request->getPost('order');
        if (!isset($postValue['coupon']['code'])
            || strpos($postValue['coupon']['code'], ',') === false
        ) {
            return;
        }

        $this->lastPostCoupon = $postValue['coupon']['code'];
        $quote = $session->getQuote();
        if ($quote && $quote->getId() && $quote->getCouponCode()) {
            $currentCoupon = $this->registry
                ->registry('riki_coupons_current_coupon');
            if (is_null($currentCoupon)) {
                $appliedCoupon = $this->couponHelper
                    ->getAppliedCouponByQuote($quote);
                $this->registry
                    ->register('riki_coupons_current_coupon', $appliedCoupon);
            }
        }

        $postValue['coupon']['code'] = $this->couponHelper
            ->getValidCouponCode($postValue['coupon']['code']);
        $request->setPostValue('order', $postValue);
    }

    /**
     * Getter
     *
     * @return string|null
     */
    public function getLastPostCoupon()
    {
        return $this->lastPostCoupon;
    }
}
