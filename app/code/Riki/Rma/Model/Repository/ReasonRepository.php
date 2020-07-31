<?php
namespace Riki\Rma\Model\Repository;

class ReasonRepository extends \Riki\Framework\Model\AbstractRepository implements \Riki\Rma\Api\ReasonRepositoryInterface
{
    /**
     * ReasonRepository constructor.
     *
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory
     * @param \Riki\Rma\Model\ReasonFactory $factory
     */
    public function __construct(
        \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory,
        \Riki\Rma\Model\ReasonFactory $factory
    ) {
        parent::__construct($resultFactory, $factory);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Riki\Rma\Api\Data\ReasonInterface $entity
     *
     * @return \Riki\Rma\Api\Data\ReasonInterface
     */
    public function save(\Riki\Rma\Api\Data\ReasonInterface $entity)
    {
        return parent::executeSave($entity);
    }

}