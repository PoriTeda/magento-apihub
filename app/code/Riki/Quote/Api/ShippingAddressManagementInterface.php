<?php
namespace Riki\Quote\Api;

interface ShippingAddressManagementInterface
{
    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $addressId
     * @return boolean
     */
    public function canAssignAddressToQuote(\Magento\Quote\Model\Quote $quote, $addressId);
}
