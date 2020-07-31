<?php

namespace Riki\DeliveryType\Api\Data;

interface QuoteItemAddressDdateProcessorInterface
{
    /**
     * Calculate delivery information follow group of cart items & quote address
     * @param \Magento\Quote\Api\Data\AddressInterface $quoteAddressInterface
     * @param array $cartItem
     * @return mixed
     */
    public function calDeliveryDateFollowAddressItem(
        \Magento\Customer\Api\Data\AddressInterface $customerAddressInterface,
        \Magento\Quote\Api\Data\CartInterface $cart,
        array $cartItems
    );
}
