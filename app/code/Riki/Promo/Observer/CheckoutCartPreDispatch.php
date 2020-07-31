<?php
namespace Riki\Promo\Observer;

class CheckoutCartPreDispatch  extends \Amasty\Promo\Observer\AddressCollectTotalsAfterObserver
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session $resourceSession,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ){
        $this->_messageManager = $messageManager;
        $this->_checkoutSession = $resourceSession;
    }

    /**
     * Show free gift messages
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $arrShow = $this->_checkoutSession->getShowAmpromoMessages();

        if(is_array($arrShow)){
            foreach($arrShow as $message){
                $this->_messageManager->addNotice($message);
            }
        }
    }
}
