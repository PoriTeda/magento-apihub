<?php
namespace Riki\Preorder\Observer;

use Magento\Framework\Event\ObserverInterface;

class ConvertOrderToQuote implements ObserverInterface
{
    protected $_helper;

    protected $_adminQuoteSession;


    public function __construct(
        \Riki\PreOrder\Helper\Data $helper,
        \Magento\Backend\Model\Session\Quote $adminQuoteSession
    ){
        $this->_helper = $helper;
        $this->_adminQuoteSession = $adminQuoteSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();

        if($this->_helper->getOrderIsPreorderFlag($order)){
            $this->_adminQuoteSession->setData(\Riki\Preorder\Model\Config\PreOrderType::SESSION_FLAG_NAME, 1);
        }
    }
}