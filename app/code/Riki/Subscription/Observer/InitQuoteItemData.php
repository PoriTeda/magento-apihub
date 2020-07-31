<?php
namespace Riki\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class InitQuoteItemData implements ObserverInterface
{
    protected $copiedFields = [
        'is_spot',
        'is_addition'
    ];

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();

        if ($quoteItem->getQuote() && $quoteItem->getQuote()->getData('profile_id')) {
            $copyObject = $quoteItem->getProduct();

            if ($parentItem = $quoteItem->getParentItem()) {
                $copyObject = $parentItem;
            }

            foreach ($this->copiedFields as $copiedField) {
                if ($copyObject->hasData($copiedField)) {
                    $quoteItem->setData($copiedField, $copyObject->getData($copiedField));
                }
            }
        }
    }
}
