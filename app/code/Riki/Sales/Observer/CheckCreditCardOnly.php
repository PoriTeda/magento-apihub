<?php

namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NotFoundException;

class CheckCreditCardOnly implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_quoteSession;
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    protected $_activeCreditCard = null;

    const CREDIT_CARD_PAYMENT = 'payment/paygent/active';

    public function __construct(
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Webapi\Rest\Request $request
    ) {
        $this->_quoteSession = $quoteSession;
        $this->scopeConfig = $scopeConfig;
        $this->_request = $request;
    }

    /**
     * check request website for frontend
     *
     * @return bool
     */
    public function checkRequestWebApi() {
        $pathInfo =  $this->_request->getPathInfo();
        $pattern ='#/sales/order_create/save/#';
        if(preg_match($pattern,$pathInfo,$match)){
            return true;
        }
        return false;
    }

    /**
     * Check condition can checkout allow credit card only
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //check quote exist
        $quote = $this->_quoteSession->getQuote();
        //check request when process checkout of admin
        if($this->checkRequestWebApi()){
            //get all cart item
            $items = $quote->getAllItems();
            foreach ($items as $item) {
                $productAllowCardOnly = $item->getProduct()->getData('credit_card_only');
                //quote contain the item only allow for credit card
                if ($productAllowCardOnly && $productAllowCardOnly == 1) {
                    //check credit card not active or customer not select credit card
                    if (!$this->checkWebsiteAllowCreditcard() || !$this->checkCustomerSelectPaymentMethod($quote) ) {
                        //The Store have no active Credit Card payment method ,customer not select credit card
                        throw new \Magento\Framework\Exception\LocalizedException(__('This product is only available for credit card payment'));
                    }
                }

                //check allow spot order
                if (!$item->getProduct()->getData(\Riki\StockSpotOrder\Helper\Data::SKIP_CHECk_ALLOW_SPOT_ORDER_NAME) && $item->getProduct()->getAllowSpotOrder()!=1){
                    $messageError =  __('I am sorry. Before you finish placing order, %1 has become out of stock. If you do not mind, please consider another product.' , $item->getName());
                    throw new \Magento\Framework\Exception\LocalizedException($messageError);
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
     *
     * @return bool
     */
    public function checkCustomerSelectPaymentMethod($quote){
        $paymentMethod = $quote->getPayment()->getMethod();
        if ($paymentMethod !='paygent') {
            return false;
        }
        return true;
    }

    /**
     * Get config credit card method active by store
     *
     * @return mixed|null
     */
    protected function checkWebsiteAllowCreditcard()
    {
        if ($this->_activeCreditCard === null) {
            $this->_activeCreditCard = $this->scopeConfig->getValue(self::CREDIT_CARD_PAYMENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return $this->_activeCreditCard;
    }

}