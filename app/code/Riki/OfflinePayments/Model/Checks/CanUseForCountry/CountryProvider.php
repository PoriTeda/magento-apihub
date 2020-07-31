<?php

namespace Riki\OfflinePayments\Model\Checks\CanUseForCountry;

use Magento\Quote\Model\Quote;
use Magento\Directory\Helper\Data as DirectoryHelper;

class CountryProvider extends \Magento\Payment\Model\Checks\CanUseForCountry\CountryProvider
{
    /**
     * Get payment country
     *
     * @param Quote $quote
     * @return int
     */
    public function getCountry(Quote $quote)
    {
        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        return $address
            ? ($address->getCountry() ? $address->getCountry() : 'JP')
            : $this->directoryHelper->getDefaultCountry();
    }
}
