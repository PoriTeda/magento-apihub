<?php
namespace Riki\Fraud\Observer;

class CartThreshold implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    /**
     * @var \Riki\Fraud\Helper\OrderThreshold
     */
    protected $_orderThreshold;

    /**
     * CartThreshold constructor.
     * @param \Riki\Fraud\Helper\OrderThreshold $orderThreshold
     */
    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Riki\Fraud\Helper\OrderThreshold $orderThreshold
    ) {
        $this->_cart = $cart;
        $this->_messageManager = $messageManager;
        $this->_orderThreshold = $orderThreshold;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $this->_cart->getQuote();
        $grandTotal = $quote->getGrandTotal();
        $customer = $this->_cart->getCustomerSession()->getData();
        $customerId = !empty($customer['customer_id']) ? $customer['customer_id'] : 0;

        $thresholdCart = $this->_orderThreshold->isThresholdCart($customerId, $grandTotal);

        if( !empty($thresholdCart) ){
            $this->_messageManager->addError($thresholdCart);
        }

        return $this;
    }
}
