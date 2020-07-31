<?php

namespace Riki\Checkout\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class CheckCreditCardOnly implements ObserverInterface
{

    const CREDIT_CARD_PAYMENT = 'payment/paygent/active';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlInterface;
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    protected $_activeCreditCard = null;

    /**
     * CheckCreditCardOnly constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Framework\Webapi\Rest\Request $request
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Webapi\Rest\Request $request
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->_messageManager = $messageManager;
        $this->_urlInterface = $urlInterface;
        $this->_request = $request;
    }

    /**
     * check request website for frontend
     *
     * @return bool
     */
    public function checkRequestWebApi()
    {
        $pathInfo = $this->_request->getPathInfo();
        $pattern = '#/V1/carts/mine/payment-information#';
        if (preg_match($pattern, $pathInfo, $match)) {
            return true;
        }
        return false;
    }

    /**
     * Check condition can checkout allow credit card only
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this|bool
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //check quote exist
        $quote = $this->_checkoutSession->getQuote();
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return false;
        }

        //check request when process checkout of frontend
        if ($this->checkRequestWebApi()) {
            //get all cart item
            $items = $quote->getAllItems();
            foreach ($items as $item) {
                $productAllowCardOnly = $item->getProduct()->getData('credit_card_only');
                //quote contain the item only allow for credit card
                if ($productAllowCardOnly && $productAllowCardOnly == 1) {
                    //check credit card not active or customer not select credit card
                    if (!$this->checkWebsiteAllowCreditcard() || !$this->checkCustomerSelectPaymentMethod($quote)) {
                        //The Store have no active Credit Card payment method , show error and back to cart page
                        throw new LocalizedException(__("This product is only available for credit card payment"));
                    }
                }
            }
        }
        return $this;
    }

    /**
     *
     * Check customer select payment method
     *
     * @param $quote
     * @return bool
     */
    public function checkCustomerSelectPaymentMethod($quote)
    {
        $paymentMethod = $quote->getPayment()->getMethod();
        if ($paymentMethod != 'paygent') {
            return false;
        }
        return true;
    }

    /**
     * Get config credit card method active by store
     *
     * @return mixed|null
     */
    private function checkWebsiteAllowCreditcard()
    {
        if ($this->_activeCreditCard === null) {
            $this->_activeCreditCard = $this->scopeConfig->getValue(self::CREDIT_CARD_PAYMENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return $this->_activeCreditCard;
    }

}