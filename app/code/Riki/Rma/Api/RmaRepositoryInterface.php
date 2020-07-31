<?php
namespace Riki\Rma\Api;

interface RmaRepositoryInterface
{

    /**
     * Get entity by id
     *
     * @param string|int $id
     *
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function getById($id);

    /**
     * @param int|string $incrementId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByIncrementId($incrementId);

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
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function createFromArray(array $data = []);

    /**
     * Save the entity
     *
     * @param \Riki\Rma\Api\Data\RmaInterface $entity
     *
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function save(\Riki\Rma\Api\Data\RmaInterface $entity);

    /**
     * Delete the entity by id
     *
     * @param string|int $id
     *
     * @return bool
     */
    public function deleteById($id);

    /**
     * Lock record to update
     *
     * @param int $id
     *
     * @return int
     */
    public function lockIdForUpdate($id);

    /**
     * Lock records to update
     *
     * @param int[] $ids
     *
     * @return int[]
     */
    public function lockIdsForUpdate($ids);
}
