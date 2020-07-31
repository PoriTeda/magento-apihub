<?php
namespace Nestle\Gillette\Api;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Nestle\Gillette\Api\Data\CartEstimationInterface;
use Nestle\Gillette\Api\Data\CartEstimationResultInterface;

/**
 * Interface OrderManagementInterface
 * @package Nestle\Gillette\Api
 */
interface OrderManagementInterface
{
    /**
     * @param CartEstimationInterface $cartEstimation
     * @return mixed
     */
    public function placeOrder(CartEstimationInterface $cartEstimation);


}
