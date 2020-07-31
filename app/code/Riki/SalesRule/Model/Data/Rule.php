<?php
namespace Riki\SalesRule\Model\Data;

class Rule extends \Magento\SalesRule\Model\Data\Rule
{

    const WBS_SHOPPING_POINT = 'wbs_shopping_point';
    const ACCOUNT_CODE = 'account_code';
    const POINT_EXPIRATION_PERIOD = 'point_expiration_period';
    const TO_TIME = 'to_time';
    const FROM_TIME = 'from_time';

    /**
     * @return mixed|null
     */
    public function getWbsShoppingPoint()
    {
        return $this->_get(self::WBS_SHOPPING_POINT);
    }

    /**
     * @return mixed|null
     */
    public function getAccountCode()
    {
        return $this->_get(self::ACCOUNT_CODE);
    }

    /**
     * @return mixed|null
     */
    public function getPointExpirationPeriod()
    {
        return $this->_get(self::POINT_EXPIRATION_PERIOD);
    }

    /**
     * @return mixed|null
     */
    public function getToTime()
    {
        return $this->_get(self::TO_TIME);
    }

    /**
     * @return mixed|null
     */
    public function getFromTime()
    {
        return $this->_get(self::FROM_TIME);
    }
}
