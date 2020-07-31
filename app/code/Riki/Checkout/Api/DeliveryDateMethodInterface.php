<?php

namespace Riki\Checkout\Api;


interface DeliveryDateMethodInterface
{

    /**
     *  Return payment information for multi checkout
     * @api
     * @param string $cartId
     * @param string[] $customerAddressInfo
     * @throws \Exception $exception
     * @throws \Magento\Framework\Exception\InputException $exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException $exception
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function saveDeliveryInformation(
        $cartId , $customerAddressInfo
    );

}
