<?php
namespace Riki\Chirashi\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesQuoteItemSetCustomAttribute implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();
        $quoteItem = $observer->getQuoteItem();

        $quoteItem->setChirashi($product->getChirashi());
    }
}