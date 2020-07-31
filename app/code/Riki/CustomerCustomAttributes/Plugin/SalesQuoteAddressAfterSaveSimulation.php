<?php

namespace Riki\CustomerCustomAttributes\Plugin;

class SalesQuoteAddressAfterSaveSimulation
{
    public function aroundExecute($subject, $proceed, $observer)
    {
        $quoteAddress = $observer->getEvent()->getQuoteAddress();
        if (!$quoteAddress instanceof \Riki\Subscription\Model\Emulator\Address) {
            return $proceed($observer);
        }

        return $observer;
    }
}
