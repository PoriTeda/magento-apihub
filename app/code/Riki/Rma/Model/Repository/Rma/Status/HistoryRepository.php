<?php
namespace Riki\Rma\Model\Repository\Rma\Status;

class HistoryRepository extends \Riki\Framework\Model\AbstractRepository implements \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface
{
    /**
     * HistoryRepository constructor.
     *
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory
     * @param \Riki\Rma\Model\Rma\Status\HistoryFactory $factory
     */
    public function __construct(
        \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory,
        \Riki\Rma\Model\Rma\Status\HistoryFactory $factory
    ) {
        parent::__construct($resultFactory, $factory);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Riki\Rma\Api\Data\Rma\Status\HistoryInterface $entity
     *
     * @return \Riki\Rma\Api\Data\Rma\Status\HistoryInterface
     */
    public function save(\Riki\Rma\Api\Data\Rma\Status\HistoryInterface $entity)
    {
        return parent::executeSave($entity);
    }
}
