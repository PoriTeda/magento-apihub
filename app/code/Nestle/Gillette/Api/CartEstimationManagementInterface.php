<?php
namespace Nestle\Gillette\Api;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Nestle\Gillette\Api\Data\CartEstimationInterface;
use Nestle\Gillette\Api\Data\CartEstimationResultInterface;

/**
 * Interface CartEstimationManagementInterface
 * @package Nestle\Gillette\Api
 */
interface CartEstimationManagementInterface
{
    /**
     * @param CartEstimationInterface $cartEstimation
     * @return \Nestle\Gillette\Api\Data\CartEstimationResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function cartEstimation(CartEstimationInterface $cartEstimation);


}
