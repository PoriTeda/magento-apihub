<?php
namespace Riki\Rma\Api\Rma\Status;

interface HistoryRepositoryInterface
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
     * @return \Riki\Rma\Api\Data\Rma\Status\HistoryInterface
     */
    public function createFromArray(array $data = []);

    /**
     * Save the entity
     *
     * @param \Riki\Rma\Api\Data\Rma\Status\HistoryInterface $entity
     *
     * @return \Riki\Rma\Api\Data\Rma\Status\HistoryInterface
     */
    public function save(\Riki\Rma\Api\Data\Rma\Status\HistoryInterface $entity);

    /**
     * Delete the entity by id
     *
     * @param string|int $id
     *
     * @return bool
     */
    public function deleteById($id);
}