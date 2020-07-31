<?php

namespace Bluecom\PaymentFee\Plugin\Quote\Model\Quote\TotalsCollector;

use Magento\Quote\Model\Quote;

class InitPaymentFee
{
    /**
     * @param Quote\TotalsCollector $subject
     * @param Quote $quote
     * @return array
     */
    public function beforeCollect(
        \Magento\Quote\Model\Quote\TotalsCollector $subject,
        Quote $quote
    ) {
        $quote->setFee(0);
        $quote->setBaseFee(0);

        return [$quote];
    }
}
