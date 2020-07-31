<?php

namespace Riki\CustomerCustomAttributes\Plugin;

class SalesQuoteAfterSaveSimulation
{
    public function aroundExecute($subject, $proceed, $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if (!$quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return $proceed($observer);
        }

        return $observer;
    }
}
