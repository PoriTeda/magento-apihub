<?php

namespace Riki\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;

class ValidateQuoteBeforePlaceOrder implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //check quote exist
        $quote = $observer->getEvent()->getQuote();

        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return $this;
        }

        $validate = $quote->canPlaceOrder();

        if ($validate['error']) {
            throw new \Magento\Framework\Exception\LocalizedException($validate['message']);
        }
    }
}