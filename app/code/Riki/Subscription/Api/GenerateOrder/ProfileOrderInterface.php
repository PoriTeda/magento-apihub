<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Api\GenerateOrder;

/**
 * Interface ProfileOrderInterface
 * @package Riki\Subscription\Api\GenerateOrder
 */
interface ProfileOrderInterface
{
    /**
     * @param int $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * @return int $profileId
     */
    public function getProfileId();
}
