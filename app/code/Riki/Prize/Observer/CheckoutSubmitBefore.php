<?php
namespace Riki\Prize\Observer;

class CheckoutSubmitBefore implements \Magento\Framework\Event\ObserverInterface
{
    protected $_prizeHelper;

    public function __construct(
        \Riki\Prize\Helper\Prize $prizeHelper
    )
    {
        $this->_prizeHelper = $prizeHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        /*Simulate don't need to add Prize and Winer*/
        if($quote instanceof \Riki\Subscription\Model\Emulator\Cart){
            return;
        }
        $this->_prizeHelper->applyToQuote($quote);
    }
}
