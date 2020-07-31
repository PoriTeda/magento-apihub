<?php
namespace Riki\Rma\Model\Repository;

class RewardRepository extends \Riki\Framework\Model\AbstractRepository
{
    /**
     * RewardRepository constructor.
     *
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory
     * @param \Riki\Loyalty\Model\RewardFactory $factory
     */
    public function __construct(
        \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory,
        \Riki\Loyalty\Model\RewardFactory $factory
    ) {
        parent::__construct($resultFactory, $factory);
    }

    /**
     * @param \Riki\Loyalty\Model\Reward $entity
     *
     * @return \Riki\Loyalty\Model\Reward
     */
    public function save(\Riki\Loyalty\Model\Reward $entity)
    {
        return parent::executeSave($entity);
    }
}
