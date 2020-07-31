<?php

namespace Riki\SubscriptionMachine\Api\Data;

interface MonthlyFeeProfileResultInterface
{
    const REFERENCE_PROFILE_ID = 'reference_profile_id';
    const CREATED_PROFILE_ID = 'created_profile_id';

    /**
     * @param int $referenceProfileId
     * @return $this
     */
    public function setReferenceProfileId($referenceProfileId);

    /**
     * @return int
     */
    public function getReferenceProfileId();

    /**
     * @param int $profileId
     * @return $this
     */
    public function setCreatedProfileId($profileId);

    /**
     * @return int
     */
    public function getCreatedProfileId();
}
