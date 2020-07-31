<?php
namespace Riki\Subscription\Observer;

class ProcessCustomerSegmentQuoteObserver extends \Magento\CustomerSegment\Observer\ProcessQuoteObserver
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $quote \Magento\Quote\Model\Quote
         */
        $quote = $observer->getEvent()->getQuote();

        if (!$quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return parent::execute($observer);
        }
    }
}
