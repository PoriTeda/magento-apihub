<?php
namespace Riki\Subscription\Observer;

/**
 * Revert 'deleted' status and auto add all simple products without required options
 */

class AddressCollectTotalsAfterObserver extends \Amasty\Promo\Observer\AddressCollectTotalsAfterObserver
{

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();

        if($quote->getAbleToAmpromoToAdd()){
            parent::execute($observer);
        }
    }
}
