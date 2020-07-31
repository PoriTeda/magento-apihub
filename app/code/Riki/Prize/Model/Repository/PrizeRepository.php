<?php
namespace Riki\Prize\Model\Repository;

class PrizeRepository extends \Riki\Framework\Model\AbstractRepository implements \Riki\Prize\Api\PrizeRepositoryInterface
{
    /**
     * PrizeRepository constructor.
     *
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory
     * @param \Riki\Prize\Model\PrizeFactory $factory
     */
    public function __construct(
        \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory,
        \Riki\Prize\Model\PrizeFactory $factory
    ) {
        parent::__construct($resultFactory, $factory);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Riki\Prize\Api\Data\PrizeInterface $entity
     *
     * @return \Riki\Prize\Model\Prize
     */
    public function save(\Riki\Prize\Api\Data\PrizeInterface $entity)
    {
        return $this->executeSave($entity);
    }

}