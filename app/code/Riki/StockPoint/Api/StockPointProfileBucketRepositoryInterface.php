<?php


namespace Riki\StockPoint\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface StockPointProfileBucketRepositoryInterface
{

    /**
     * Save stock_point_profile_bucket
     * @param \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface $stockPointProfileBucket
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface $stockPointProfileBucket
    );

    /**
     * Retrieve stock_point_profile_bucket
     * @param string $stockPointProfileBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($stockPointProfileBucketId);

    /**
     * Retrieve stock_point_profile_bucket matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete stock_point_profile_bucket
     * @param \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface $stockPointProfileBucket
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface $stockPointProfileBucket
    );

    /**
     * Delete stock_point_profile_bucket by ID
     * @param string $stockPointProfileBucketId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($stockPointProfileBucketId);
}
