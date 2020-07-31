<?php

namespace Riki\SubscriptionMachine\Api\Data;

use Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileCreationInterface;

interface MonthlyFeeProfileUpdateInterface extends MonthlyFeeProfileCreationInterface
{
    const PROFILE_ID = 'profile_id';
    const IS_MONTHLY_FEE_CONFIRMED = 'is_monthly_fee_confirmed';

    /**
     * @param int $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * @return int|null
     */
    public function getProfileId();

    /**
     * @param boolean $isMonthlyFeeConfirmed
     * @return mixed
     */
    public function setIsMonthlyFeeConfirmed($isMonthlyFeeConfirmed);

    /**
     * @return boolean
     */
    public function getIsMonthlyFeeConfirmed();
}
