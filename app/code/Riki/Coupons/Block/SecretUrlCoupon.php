<?php
namespace Riki\Coupons\Block;

class SecretUrlCoupon extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Riki\Coupons\Observer\SecretUrlCouponObserver
     */
    protected $couponObserver;

    /**
     * SecretUrlCoupon constructor.
     *
     * @param \Riki\Coupons\Observer\SecretUrlCouponObserver $couponObserver
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Coupons\Observer\SecretUrlCouponObserver $couponObserver,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->couponObserver = $couponObserver;
        $this->sessionManager = $context->getSession();
        parent::__construct($context, $data);
    }

    /**
     * Get cookie domain
     *
     * @return string
     */
    public function getCookieDomain()
    {
        return '.' . trim($this->sessionManager->getCookieDomain(), '.');
    }

    /**
     * Get cookie path
     *
     * @return string
     */
    public function getCookiePath()
    {
        return $this->sessionManager->getCookiePath();
    }

    /**
     * Get cookie secure
     *
     * @return bool
     */
    public function getCookieSecure()
    {
        return $this->getRequest()->isSecure();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function toHtml()
    {
        $applyActions = $this->couponObserver->getApplyActions();
        if (isset($applyActions[$this->getRequest()->getFullActionName()])) {
            return '';
        }

        return parent::toHtml();
    }
}