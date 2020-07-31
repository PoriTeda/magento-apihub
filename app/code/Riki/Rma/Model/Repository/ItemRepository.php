<?php
namespace Riki\Rma\Model\Repository;

class ItemRepository extends \Riki\Framework\Model\AbstractRepository implements \Riki\Rma\Api\ItemRepositoryInterface
{
    /**
     * ItemRepository constructor.
     *
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory
     * @param \Riki\Rma\Model\ItemFactory $factory
     *
     * @throws \Exception
     */
    public function __construct(
        \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory,
        \Riki\Rma\Model\ItemFactory $factory
    ) {
        parent::__construct($resultFactory, $factory);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Riki\Rma\Api\Data\ItemInterface $entity
     *
     * @return \Riki\Rma\Api\Data\ItemInterface
     */
    public function save(\Riki\Rma\Api\Data\ItemInterface $entity)
    {
        return parent::executeSave($entity);
    }
}