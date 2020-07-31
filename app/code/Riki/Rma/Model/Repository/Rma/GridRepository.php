<?php
namespace Riki\Rma\Model\Repository\Rma;

class GridRepository extends \Riki\Framework\Model\AbstractRepository implements \Riki\Rma\Api\GridRepositoryInterface
{
    /**
     * GridRepository constructor.
     *
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory
     * @param \Riki\Rma\Model\GridFactory $factory
     */
    public function __construct(
        \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory,
        \Riki\Rma\Model\GridFactory $factory
    ) {
        parent::__construct($resultFactory, $factory);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Riki\Rma\Api\Data\GridInterface $entity
     *
     * @return \Riki\Rma\Api\Data\GridInterface
     */
    public function save(\Riki\Rma\Api\Data\GridInterface $entity)
    {
        return parent::executeSave($entity);
    }
}
