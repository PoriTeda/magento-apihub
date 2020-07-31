<?php

namespace Riki\Checkout\Api;


interface ShippingAddressInterface
{

    /**
     * save address information for quote item
     *
     * @param string $cartId
     * @param string $itemAddressInformation
     * @return string[]
     */
    public function saveItemAddressInformation($cartId,$itemAddressInformation);

}
