<?php
namespace Riki\AdvancedInventory\Plugin\Quote\Model\Quote\Address;

use Magento\Quote\Model\Quote\Address\ToOrder as QuoteAddressToOrder;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

class SetBaseSubtotalInclTax
{
    /**
     * @param QuoteAddressToOrder $subject
     * @param QuoteAddress $address
     * @param array $additional
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeConvert(QuoteAddressToOrder $subject, QuoteAddress $address, $additional = [])
    {
        /**
         * Purpose: in order to set base_subtotal_incl_tax when generating OOS order
         * Reason: quote address total was not collected, this cause shipping address missing base_subtotal_incl_tax
         */
        if ($address->getBaseSubtotalInclTax() === null) {
            $address->setBaseSubtotalInclTax($address->getSubtotalInclTax());
        }
        return [$address, $additional];
    }
}
