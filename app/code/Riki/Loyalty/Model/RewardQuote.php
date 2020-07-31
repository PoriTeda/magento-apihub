<?php

namespace Riki\Loyalty\Model;

use Magento\Framework\Model\AbstractModel;
use Riki\Loyalty\Api\Data\RewardQuoteInterface;

class RewardQuote extends AbstractModel implements RewardQuoteInterface
{
    /**
     * Initialize connection and define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Loyalty\Model\ResourceModel\RewardQuote');
    }

    /**
     * @param int $option
     * @return $this
     */
    public function setRewardUserSetting($option)
    {
        $this->setData(self::REWARD_USER_SETTING, $option);
        return $this;
    }

    /**
     * @return int
     */
    public function getRewardUserSetting()
    {
        return $this->getData(self::REWARD_USER_SETTING);
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function setRewardUserRedeem($amount)
    {
        $this->setData(self::REWARD_USER_REDEEM, $amount);
        return $this;
    }

    /**
     * @return int
     */
    public function getRewardUserRedeem()
    {
        return $this->getData(self::REWARD_USER_REDEEM);
    }

    /**
     * @param $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId)
    {
        $this->setData(self::QUOTE_ID, $quoteId);
        return $this;
    }

    /**
     * @return int
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }
}