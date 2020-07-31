<?php
namespace Riki\Loyalty\Api;

interface RewardPointManagementInterface
{
    /**
     * Get point balance
     *
     * @param $consumerDbId
     *
     * @return int
     */
    public function getPointBalance($consumerDbId);

    /**
     * Get amount from point
     *
     * @param $point
     *
     * @return float
     */
    public function getAmountFromPoint($point);
}