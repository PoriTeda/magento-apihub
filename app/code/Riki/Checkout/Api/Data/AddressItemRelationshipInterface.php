<?php

namespace Riki\Checkout\Api\Data;

interface AddressItemRelationshipInterface{

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItemInterface
     * @param \Magento\Quote\Api\Data\AddressInterface $addressInterface
     * @return mixed
     */
    public function saveAddressItemRelation(
        \Magento\Quote\Api\Data\CartInterface $quote,
        \Magento\Quote\Api\Data\CartItemInterface $cartItemInterface,
        \Magento\Quote\Api\Data\AddressInterface $addressInterface
    );

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return mixed
     */
    public function saveOrderAddressItemRelation(
        \Magento\Quote\Api\Data\CartInterface $quote,
        \Magento\Sales\Api\Data\OrderInterface $order
    );
}
