<?php
namespace Riki\Loyalty\Model;

class RewardPointManagement implements \Riki\Loyalty\Api\RewardPointManagementInterface
{
    /**
     * @var \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint
     */
    protected $shoppingPoint;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;

    /**
     * RewardPointManagement constructor.
     *
     * @param \Riki\Loyalty\Model\RewardManagement $rewardManagement
     * @param \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint
     */
    public function __construct(
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint
    ) {
        $this->rewardManagement = $rewardManagement;
        $this->shoppingPoint = $shoppingPoint;
    }

    /**
     * {@inheritdoc}
     *
     * @param $consumerDbId
     *
     * @return int
     */
    public function getPointBalance($consumerDbId)
    {
        $response = $this->shoppingPoint->getPoint($consumerDbId);
        if($response['error'] || !isset($response['return']['REST_POINT'])) {
            return 0;
        }

        return intval($response['return']['REST_POINT']);
    }

    /**
     * {@inheritdoc}
     *
     * @param $point
     *
     * @return float
     */
    public function getAmountFromPoint($point)
    {
        return $this->rewardManagement->convertPointToAmount($point);
    }

}