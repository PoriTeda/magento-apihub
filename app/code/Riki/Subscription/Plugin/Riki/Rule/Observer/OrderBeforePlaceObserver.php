<?php

namespace Riki\Subscription\Plugin\Riki\Rule\Observer;

class OrderBeforePlaceObserver
{
    /**
     * Ignore for simulate order case
     *
     * @param \Riki\Rule\Observer\OrderBeforePlaceObserver $subject
     * @param $observer
     * @return array
     */
    public function beforeExecute(
        \Riki\Rule\Observer\OrderBeforePlaceObserver $subject,
        $observer
    )
    {
        $quote = $observer->getQuote();

        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart){
            $quote->setSkipCumulativePromotion(true);
        }

        return [$observer];
    }
}
