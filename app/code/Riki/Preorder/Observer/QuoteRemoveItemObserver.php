<?php
namespace Riki\Preorder\Observer;

use Magento\Framework\Event\ObserverInterface;


class QuoteRemoveItemObserver implements ObserverInterface
{
    protected $_adminQuoteSession;

    /***
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     */
    public function __construct(
        \Magento\Backend\Model\Session\Quote $quoteSession
    ){
        $this->_adminQuoteSession = $quoteSession;
    }

    /**
     * unset pre-order session if quote do not have item
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!count($this->_adminQuoteSession->getQuote()->getAllItems()))
            $this->_adminQuoteSession->setData(\Riki\Preorder\Model\Config\PreOrderType::SESSION_FLAG_NAME, null);
    }
}
