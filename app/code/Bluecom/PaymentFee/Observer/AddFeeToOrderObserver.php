<?php

namespace Bluecom\PaymentFee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddFeeToOrderObserver implements ObserverInterface
{
    /**
     * Model Session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * AddFeeToOrderObserver constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession checkout session
     */
    public function __construct(\Magento\Checkout\Model\Session $checkoutSession)
    {
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Set payment fee to order
     *
     * @param EventObserver $observer observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $paymentFee = $quote->getFee();
        $paymentBaseFee = $quote->getBaseFee();
        if (!$paymentFee || !$paymentBaseFee) {
            return $this;
        }
        //Set fee data to order
        $order = $observer->getOrder();
        $order->setData('fee', $paymentFee);
        $order->setData('base_fee', $paymentBaseFee);

        return $this;
    }
}