<?php
namespace Riki\Loyalty\Api;

interface CheckoutRewardPointInterface
{
    /**
     * @param int $cartId
     * @param int $usedPoints
     * @param int $option
     *
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function applyRewardPoint($cartId , $usedPoints, $option);

    /**
     * @param int $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @param int $usedPoints
     * @param int $option
     *
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function saveShippingInformationAndApplyRewardPoint(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation,
        $usedPoints,
        $option
    );

    /**
     * Remove current shopping point
     *
     * @param int $cartId
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function removeRewardPoint($cartId);


    /**
     * Use all available point
     *
     * @param int $cartId
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function useAllPoint($cartId);


    /**
     * Use a number of point
     *
     * @param int $cartId
     * @param int $usedPoints
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function usePoint($cartId , $usedPoints);

}

