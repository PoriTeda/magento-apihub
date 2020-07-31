<?php

namespace Riki\SubscriptionMachine\Model\Data;

use Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileUpdateInterface;
use Riki\SubscriptionMachine\Model\Data\MonthlyFeeProfileCreation;

class MonthlyFeeProfileUpdate extends MonthlyFeeProfileCreation implements MonthlyFeeProfileUpdateInterface
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
    public function setIsMonthlyFeeConfirmed($isMonthlyFeeConfirmed)
    {
        return $this->setData(self::IS_MONTHLY_FEE_CONFIRMED, $isMonthlyFeeConfirmed);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsMonthlyFeeConfirmed()
    {
        return $this->getData(self::IS_MONTHLY_FEE_CONFIRMED);
    }
}
