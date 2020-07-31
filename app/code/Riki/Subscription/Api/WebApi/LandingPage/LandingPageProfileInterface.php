<?php
namespace Riki\Subscription\Api\WebApi\LandingPage;

interface LandingPageProfileInterface
{
    /**
     * @api
     *
     * @param int|null $profileId
     * @param int $customerId
     * @return mixed
     */
    public function getLandingPageProfile($profileId, $customerId);

    /**
     * @api
     *
     * @param int $customerId
     * @return mixed
     */
    public function getLandingPageProfileList ($customerId);

    /**
     * @param int $customerId
     * @param string $deliveryDate
     * @param int|string $deliveryTime
     * @param int $profileId
     * @return mixed
     */
    public function setLandingPageDeliveryDate ($customerId, $deliveryDate, $deliveryTime, $profileId);

    /**
     * @param int $profileId
     * @param int $customerId
     * @return mixed
     */
    public function getLandingPageFrequency ($profileId, $customerId);

    /**
     * @param int $customerId
     * @param int $frequencyId
     * @param int $profileId
     * @return mixed
     */
    public function setLandingPageFrequency ($customerId, $frequencyId, $profileId);

    /**
     * @param int $profileId
     * @param int $customerId
     * @return mixed
     */
    public function getLandingPagePaymentMethod ($profileId, $customerId);

    /**
     * @param int $customerId
     * @param string $paymentMethod
     * @param string $redirectUrl
     * @param int $profileId
     * @return mixed
     */
    public function setLandingPagePaymentMethod ($customerId, $paymentMethod, $redirectUrl, $profileId);

    /**
     * @param int $profileId
     * @param string $redirectUrl
     * @param int $customerId
     * @return mixed
     */
    public function getLandingPageShippingAddress ($profileId, $redirectUrl, $customerId);

    /**
     * @param int $customerId
     * @param string|int $shippingAddress
     * @param bool $isStockpoint
     * @param int $profileId
     * @return mixed
     */
    public function setLandingPageShippingAddress ($customerId, $shippingAddress, $isStockpoint, $profileId);

    /**
     * @api
     *
     * @param int $customerId
     * @return mixed
     */
    public function getLandingPageProfileListAll($customerId);

    /**
     * @api
     *
     * @param int|null $profileId
     * @param int $customerId
     * @return mixed
     */
    public function getLandingPageProfileDetail($profileId, $customerId);

    /**
     * @api
     *
     * @param int $customerId
     * @return mixed
     */
    public function getPromotionBanner($customerId);

    /**
     * @api
     *
     * @return mixed
     */
    public function getNavigationBanner();


    /**
     * @param int $profileId
     * @param string $redirectUrl
     * @param int $customerId
     * @return mixed
     */
    public function getStockPoint($profileId, $redirectUrl, $customerId);

    /**
     * @param int $customerId
     * @return mixed
     */
    public function getPointAndCoin($customerId);

    /**
     * @param int $customerId
     * @param int $usePointType
     * @param int $usePointAmount
     * @return mixed
     */
    public function setPoint($customerId, $usePointType, $usePointAmount);

    /**
     * @param int $customerId
     * @param string $serialCode
     * @return mixed
     */
    public function applySerialCode($customerId, $serialCode);
}