<?php
namespace Riki\Wamb\Api;

interface RegisterRepositoryInterface
{
    /**
     * Save
     *
     * @param \Riki\Wamb\Api\Data\RegisterInterface $register
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Riki\Wamb\Api\Data\RegisterInterface $register);

    /**
     * Retrieve
     *
     * @param int $id
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($id);

    /**
     * Retrieve Customer matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Riki\Wamb\Api\Data\RegisterSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete
     * @param \Riki\Wamb\Api\Data\RegisterInterface $register
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Riki\Wamb\Api\Data\RegisterInterface $register);

    /**
     * Delete by ID
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}
