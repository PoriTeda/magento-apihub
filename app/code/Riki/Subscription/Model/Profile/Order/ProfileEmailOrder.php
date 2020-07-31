<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Model\Profile\Order;

use Riki\Subscription\Api\GenerateOrder\ProfileEmailOrderInterface;

/**
 * @codeCoverageIgnore
 */
class ProfileEmailOrder implements ProfileEmailOrderInterface
{
    /**
     * @var int
     */
    private $profileId;
    private $profileData;


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
    /**
     * @param string $profileData
     * @return $this
     */
    public function setProfileData(  $profileData){
        $this->profileData = $profileData;
        return $this;
    }

    /**
     * @return string
     */
    public function getProfileData(){
        return $this->profileData ;

    }

}
