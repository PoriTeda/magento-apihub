<?php

namespace Riki\AdvancedInventory\Api;

interface OutOfStockRepositoryInterface
{
    /**
     * Get entity by id
     *
     * @param string|int $id
     *
     * @return mixed
     */
    public function getById($id);

    /**
     * Get list entity by searchCriteria
     *
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     *
     * @return \Magento\Framework\Api\Search\SearchResultInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria);

    /**
     * Create entity by array
     *
     * @param array $data
     *
     * @return mixed
     */
    public function createFromArray(array $data = []);

    /**
     * Save the entity
     *
     * @param \Riki\AdvancedInventory\Api\Data\OutOfStockInterface $entity
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function save(\Riki\AdvancedInventory\Api\Data\OutOfStockInterface $entity);

    /**
     * Delete the entity by id
     *
     * @param string|int $id
     *
     * @return bool
     */
    public function deleteById($id);
}
