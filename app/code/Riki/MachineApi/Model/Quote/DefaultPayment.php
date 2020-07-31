<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\MachineApi\Model\Quote;

class DefaultPayment extends \Magento\Quote\Model\Quote\Payment
{
    /**
     * Overwrite function default
     *
     * @return \Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuote()
    {
        $quote = parent::getQuote();
        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The requested Payment Method is not available.')
            );
        }
        return $quote;
    }

}