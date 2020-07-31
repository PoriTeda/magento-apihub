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
 * Class SalesQuoteCollectTotalsAfter
 *
 * @category  RIKI
 * @package   Riki\Coupons\Observer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class SalesQuoteCollectTotalsAfter
    implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * CouponHelper
     *
     * @var \Riki\Coupons\Helper\Coupon
     */
    protected $couponHelper;
    /**
     * Request
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * AdminhtmlSalesOrderCreateProcessDataBeforePlugin
     *
     * @var AdminhtmlSalesOrderCreateProcessDataBefore
     */
    protected $adminhtmlSalesOrderCreateProcessDataBefore;
    /**
     * FunctionCache
     *
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * SalesQuoteCollectTotalsAfter constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache Helper
     * @param AdminhtmlSalesOrderCreateProcessDataBefore $actionPlugin  Plugin
     * @param \Magento\Framework\App\RequestInterface    $request       Request
     * @param \Riki\Coupons\Helper\Coupon                $couponHelper  Helper
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        AdminhtmlSalesOrderCreateProcessDataBefore $actionPlugin,
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Coupons\Helper\Coupon $couponHelper
    ) {
        $this->functionCache = $functionCache;
        $this->adminhtmlSalesOrderCreateProcessDataBefore = $actionPlugin;
        $this->request = $request;
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
        $quote = $observer->getQuote();

        if (!$quote->getCouponCode()) {
            return;
        }

        $quoteId = (string)$quote->getId();
        $rulesApply = (string)$quote->getAppliedRuleIds();
        $cacheKey = $quoteId . $rulesApply;

        if ($this->functionCache->has($cacheKey)) {
            return;
        }

        $couponApply = $this->couponHelper->getAppliedCouponByQuote($quote);

        $postValue = $this->request->getPost('order');
        if (isset($postValue['coupon']['code'])) {
            $lastCoupon = $this->adminhtmlSalesOrderCreateProcessDataBefore
                ->getLastPostCoupon();
            $postValue['coupon']['code'] = $lastCoupon
                ? $lastCoupon
                : $postValue['coupon']['code'];
            $postCoupons = explode(',', $postValue['coupon']['code']);
            $result = array_diff($postCoupons, $couponApply);
            if ($result) {
                $postValue['coupon']['code'] = implode(',', $result);
                // modify post param to custom error message @see \Magento\Sales\Controller\Adminhtml\Order\Create->_processActionData
                $this->request->setPostValue('order', $postValue);
            }
        }

        $this->functionCache->store(true, $cacheKey);
    }
}
