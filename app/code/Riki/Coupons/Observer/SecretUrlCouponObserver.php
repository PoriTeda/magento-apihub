<?php
namespace Riki\Coupons\Observer;

class SecretUrlCouponObserver implements \Magento\Framework\Event\ObserverInterface
{
    const COUPON = 'coupon';
    const CAMPAIGN_ID = 'campaign_id';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var  \Magento\Framework\Stdlib\Cookie\CookieMetadata
     */
    protected $cookieMetadata;

    /**
     * @var \Magento\Framework\Message\Manager
     */
    protected $messageManager;

    /**
     * @var \Magento\Quote\Api\CouponManagementInterface
     */
    protected $couponManagement;

    /**
     * @var \Riki\Coupons\Helper\Coupon
     */
    protected $couponHelper;

    /**
     * SecretUrlCouponObserver constructor.
     *
     * @param \Riki\Coupons\Helper\Coupon $couponHelper
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Quote\Api\CouponManagementInterface $couponManagement
     * @param \Magento\Framework\Message\Manager $messageManager
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Checkout\Model\Cart $cart
     */
    public function __construct(
        \Riki\Coupons\Helper\Coupon $couponHelper,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Api\CouponManagementInterface $couponManagement,
        \Magento\Framework\Message\Manager $messageManager,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->couponHelper = $couponHelper;
        $this->sessionManager = $sessionManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->logger = $logger;
        $this->couponManagement = $couponManagement;
        $this->messageManager = $messageManager;
        $this->cookieManager = $cookieManager;
        $this->cart = $cart;
    }


    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $observer->getEvent()->getRequest();
        $applyAction = $this->getApplyActions();
        if (!isset($applyAction[$request->getFullActionName()])) {
            return;
        }

        $quote = $this->cart->getQuote();
        if (!$quote instanceof \Magento\Quote\Model\Quote
            || !$quote->getItemsCount()
        ) {
            return;
        }

        // apply campaign id
        $campaignId = $request->getParam(static::CAMPAIGN_ID, $this->cookieManager->getCookie(static::CAMPAIGN_ID));
        if ($campaignId) {
            if (!\Zend_Validate::is($campaignId, 'StringLength', ['min' => 7, 'max' => 7])) {
                $this->messageManager->addError(__('Campaign Id is invalid. Campaign Id must have 7 digits.'));
                $campaignId = null;
            } else if (!\Zend_Validate::is($campaignId, 'Alnum')) {
                $this->messageManager->addError(__('Campaign Id is invalid. Campaign ID must be alphanumeric character'));
                $campaignId = null;
            }
        }
        if ($campaignId) {
            try {
                $quote->setCampaignId($campaignId)->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
            $this->cookieManager->deleteCookie(static::CAMPAIGN_ID, $this->getCookieMetadata());
        }

        // apply coupon
        $coupon = $request->getParam(static::COUPON, $this->cookieManager->getCookie(static::COUPON));
        if ($coupon) {
            $validCoupon = $this->couponHelper->getValidCouponCode($coupon);
            if ($validCoupon) {
                try {
                    $this->couponManagement->set($quote->getId(), $validCoupon);
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
            $this->cookieManager->deleteCookie(static::COUPON, $this->getCookieMetadata());
        }
    }

    /**
     * Get apply action
     *
     * @return array
     */
    public function getApplyActions()
    {
        return [
            'checkout_cart_index' => 1,
            'multiCheckout_index_index' => 1,
            'checkout_index_index' => 1,
        ];
    }

    /**
     * Get cookie meta data
     *
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadata
     */
    public function getCookieMetadata()
    {
        if (!$this->cookieMetadata) {
            $cookieDomain = trim($this->sessionManager->getCookieDomain(), '.');
            $this->cookieMetadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration(time() + 86400)
                ->setDomain('.' . $cookieDomain)
                ->setPath($this->sessionManager->getCookiePath());
        }

        return $this->cookieMetadata;
    }
}