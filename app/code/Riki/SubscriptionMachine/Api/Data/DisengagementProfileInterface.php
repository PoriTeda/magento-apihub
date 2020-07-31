<?php

namespace Riki\SubscriptionMachine\Api\Data;

interface DisengagementProfileInterface
{
    const PROFILE_ID = 'profile_id';
    const DISENGAGEMENT_USER = 'disengagement_user';
    const DISENGAGEMENT_DATE = 'disengagement_date';
    const DISENGAGEMENT_REASONS = 'disengagement_reasons';

    /**
     * @param int $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * @return int
     */
    public function getProfileId();

    /**
     * @param string $disengagementUser
     * @return $this
     */
    public function setDisengagementUser($disengagementUser);

    /**
     * @return string
     */
    public function getDisengagementUser();

    /**
     * @param string $disengagementDate
     * @return $this
     */
    public function setDisengagementDate($disengagementDate);

    /**
     * @return string
     */
    public function getDisengagementDate();

    /**
     * @param int[] $disengagementReasons
     * @return $this
     */
    public function setDisengagementReasons($disengagementReasons);

    /**
     * @return int[]
     */
    public function getDisengagementReasons();
}
