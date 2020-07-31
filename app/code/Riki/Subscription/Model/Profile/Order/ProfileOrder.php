<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Model\Profile\Order;

use Riki\Subscription\Api\GenerateOrder\ProfileOrderInterface;

/**
 * @codeCoverageIgnore
 */
class ProfileOrder implements ProfileOrderInterface
{
    /**
     * @var int
     */
    private $profileId;


    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getProfileId()
    {
        return $this->profileId;
    }
}
