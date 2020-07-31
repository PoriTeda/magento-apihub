<?php

namespace Riki\Checkout\Observer;

use Magento\Framework\Event\ObserverInterface;

use Zend\Serializer\Serializer;

class RemoveFreeMachineObserver implements ObserverInterface
{
    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $_helperPromoItem;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $sessionQuote;

    public function __construct(
        \Amasty\Promo\Helper\Item $helperPromoItem,
        \Magento\Backend\Model\Session\Quote $sessionQuote
    ) {
        $this->_helperPromoItem = $helperPromoItem;
        $this->sessionQuote = $sessionQuote;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getQuote();
        $count = 0;
        $hasMachine = false;
        $isEditOrder = $this->sessionQuote->getOrderId();
        foreach ($quote->getAllItems() as $quoteItem) {
            // count normal products (not machine + not free gift)
            if (
                !$quoteItem->getIsRikiMachine() &&
                !$this->_helperPromoItem->isPromoItem($quoteItem)
            ) {
                ++$count;
            }
            if ($quoteItem->getIsRikiMachine()) {
                $hasMachine = true;
            }
        }
        // remove cart has only machine
        if ($count === 0 && $hasMachine) {
            $quote->removeAllItems();
        }
    }
}
