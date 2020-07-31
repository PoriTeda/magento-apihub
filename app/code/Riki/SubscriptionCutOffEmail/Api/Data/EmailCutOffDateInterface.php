<?php
namespace Riki\SubscriptionCutOffEmail\Api\Data;


interface EmailCutOffDateInterface
{

    const PROFILE_ID    = 'profile_id';
    const CUT_OFF_DATE  = 'cut_off_date';
    const EMAIL_LOG     = 'email';

    /**
     * @return int
     */
    public function getProfileId();

    /**
     * @param int $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email);

    /**
     * @return string
     */
    public function getCutOffDate();

    /**
     * @param string $cutOffDate
     * @return $this
     */
    public function setCutOffDate($cutOffDate);


}