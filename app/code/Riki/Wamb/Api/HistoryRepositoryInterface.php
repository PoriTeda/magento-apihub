<?php


namespace Riki\Wamb\Api;

interface HistoryRepositoryInterface
{
    /**
     * Save History
     * @param \Riki\Wamb\Api\Data\HistoryInterface $history
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Riki\Wamb\Api\Data\HistoryInterface $history
    );

    /**
     * Retrieve History
     * @param string $historyId
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($historyId);

    /**
     * Retrieve History matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Riki\Wamb\Api\Data\HistorySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete History
     * @param \Riki\Wamb\Api\Data\HistoryInterface $history
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Riki\Wamb\Api\Data\HistoryInterface $history
    );

    /**
     * Delete History by ID
     * @param string $historyId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($historyId);
}
