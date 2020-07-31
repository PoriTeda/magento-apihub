<?php
namespace Riki\Promo\Observer;

class CheckoutCartPostDispatch  extends \Amasty\Promo\Observer\AddressCollectTotalsAfterObserver
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session $resourceSession
    ){
        $this->_checkoutSession = $resourceSession;
    }

    /**
     * Show free gift messages
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_checkoutSession->unsShowAmpromoMessages();
    }
}
