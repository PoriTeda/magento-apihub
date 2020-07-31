<?php

namespace Riki\Subscription\Api\WebApi\EditPage;

/**
 * Interface WebAppEditProfileInterface
 * @package Riki\Subscription\Api\WebApi\EditPage
 * @api
 */
interface WebAppEditProfileInterface
{
    /**
     * Creates an empty cart and quote for a specified customer if customer does not have a cart yet.
     *
     * @param int $profileId The Sub-Profile Id.
     * @param int $customerId The Customer Id
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified profile does not exist.
     */
    public function getById($profileId, $customerId);

    /**
     * @param \Riki\Subscription\Api\Data\WebAppProfileInterface $profile
     * @param int $customerId
     * @return mixed
     */
    public function applyProfileChange($profile, $customerId);

    /**
     * @param int profileId
     * @param string $couponCode
     * @return mixed
     */
    public function applyCouponCode($profileId, $couponCode);

    /**
     * @param int $profileId
     * @param string $couponCode
     * @return mixed
     */
    public function removeCouponCode($profileId, $couponCode);
}