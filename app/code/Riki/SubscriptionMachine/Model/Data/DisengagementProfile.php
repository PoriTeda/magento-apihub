<?php

namespace Riki\SubscriptionMachine\Model\Data;

use \Magento\Framework\DataObject;
use Riki\SubscriptionMachine\Api\Data\DisengagementProfileInterface;

class DisengagementProfile extends DataObject implements DisengagementProfileInterface
{
    /**
     * {@inheritdoc}
     */
    public function setProfileId($profileId)
    {
        return $this->setData(self::PROFILE_ID, $profileId);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfileId()
    {
        return $this->getData(self::PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setDisengagementUser($disengagementUser)
    {
        return $this->setData(self::DISENGAGEMENT_USER, $disengagementUser);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisengagementUser()
    {
        return $this->getData(self::DISENGAGEMENT_USER);
    }

    /**
     * {@inheritdoc}
     */
    public function setDisengagementDate($disengagementDate)
    {
        return $this->setData(self::DISENGAGEMENT_DATE, $disengagementDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisengagementDate()
    {
        return $this->getData(self::DISENGAGEMENT_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function setDisengagementReasons($disengagementReasons)
    {
        return $this->setData(self::DISENGAGEMENT_REASONS, $disengagementReasons);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisengagementReasons()
    {
        return $this->getData(self::DISENGAGEMENT_REASONS);
    }
}
