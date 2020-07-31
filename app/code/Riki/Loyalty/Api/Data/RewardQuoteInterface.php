<?php

namespace Riki\Loyalty\Api\Data;

interface RewardQuoteInterface
{
    const USER_DO_NOT_USE_POINT = 0;
    const USER_USE_ALL_POINT = 1;
    const USER_USE_SPECIFIED_POINT = 2;
    
    const REWARD_QUOTE_ID      = 'id';
    const REWARD_USER_SETTING  = 'reward_user_setting';
    const REWARD_USER_REDEEM   = 'reward_user_redeem';
    const QUOTE_ID             = 'quote_id';

    /**
     * @param int $option
     * @return $this
     */
    public function setRewardUserSetting($option);

    /**
     * @return int
     */
    public function getRewardUserSetting();

    /**
     * @param int $amount
     * @return $this
     */
    public function setRewardUserRedeem($amount);

    /**
     * @return int
     */
    public function getRewardUserRedeem();

    /**
     * @param $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId);

    /**
     * @return int
     */
    public function getQuoteId();
}