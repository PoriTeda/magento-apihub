<?php


namespace Riki\StockPoint\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface StockPointRepositoryInterface
{

    /**
     * Save stock_point
     * @param \Riki\StockPoint\Api\Data\StockPointInterface $stockPoint
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Riki\StockPoint\Api\Data\StockPointInterface $stockPoint
    );

    /**
     * Retrieve stock_point
     * @param string $stockPointId
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($stockPointId);

    /**
     * Retrieve stock_point matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Riki\StockPoint\Api\Data\StockPointSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete stock_point
     * @param \Riki\StockPoint\Api\Data\StockPointInterface $stockPoint
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Riki\StockPoint\Api\Data\StockPointInterface $stockPoint
    );

    /**
     * Delete stock_point by ID
     * @param string $stockPointId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($stockPointId);
}
