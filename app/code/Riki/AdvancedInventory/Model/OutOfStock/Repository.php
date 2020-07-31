<?php
namespace Riki\AdvancedInventory\Model\OutOfStock;

use Riki\Framework\Model\AbstractRepository;
use Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface;

class Repository extends AbstractRepository implements OutOfStockRepositoryInterface
{
    /**
     * Repository constructor.
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $searchResultFactory
     * @param \Riki\AdvancedInventory\Api\Data\OutOfStockInterfaceFactory $factory
     */
    public function __construct(
        \Magento\Framework\Api\Search\SearchResultInterfaceFactory $searchResultFactory,
        \Riki\AdvancedInventory\Api\Data\OutOfStockInterfaceFactory $factory
    ) {
        parent::__construct($searchResultFactory, $factory);
    }

    /**
     * Save entity
     *
     * @param \Riki\AdvancedInventory\Api\Data\OutOfStockInterface $entity
     * @return mixed
     */
    public function save(\Riki\AdvancedInventory\Api\Data\OutOfStockInterface $entity)
    {
        return $this->executeSave($entity);
    }
}
