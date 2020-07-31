<?php


namespace Riki\StockPoint\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface StockPointDeliveryBucketRepositoryInterface
{

    /**
     * Save stock_point_delivery_bucket
     * @param \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface $stockPointDeliveryBucket
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface $stockPointDeliveryBucket
    );

    /**
     * Retrieve stock_point_delivery_bucket
     * @param string $stockPointDeliveryBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($stockPointDeliveryBucketId);

    /**
     * Retrieve stock_point_delivery_bucket matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete stock_point_delivery_bucket
     * @param \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface $stockPointDeliveryBucket
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface $stockPointDeliveryBucket
    );

    /**
     * Delete stock_point_delivery_bucket by ID
     * @param string $stockPointDeliveryBucketId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($stockPointDeliveryBucketId);
}
