<?php

namespace Riki\Prize\Api;

interface PrizeRepositoryInterface
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
     * @param \Riki\Prize\Api\Data\PrizeInterface $entity
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface
     */
    public function save(\Riki\Prize\Api\Data\PrizeInterface $entity);

    /**
     * Delete the entity by id
     *
     * @param string|int $id
     *
     * @return bool
     */
    public function deleteById($id);
}
