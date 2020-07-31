<?php


namespace Riki\StockPoint\Api\Data;

interface StockPointProfileBucketInterface
{

    const STOCK_POINT_ID = 'stock_point_id';
    const EXTERNAL_PROFILE_BUCKET_ID = 'external_profile_bucket_id';
    const PROFILE_BUCKET_ID = 'profile_bucket_id';
    const STOCK_POINT_PROFILE_BUCKET_ID = 'stock_point_profile_bucket_id';

    /**
     * Get stock_point_profile_bucket_id
     * @return string|null
     */
    public function getStockPointProfileBucketId();

    /**
     * Set stock_point_profile_bucket_id
     * @param string $stockPointProfileBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     */
    public function setStockPointProfileBucketId($stockPointProfileBucketId);

    /**
     * Get profile_bucket_id
     * @return string|null
     */
    public function getProfileBucketId();

    /**
     * Set profile_bucket_id
     * @param string $profileBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     */
    public function setProfileBucketId($profileBucketId);

    /**
     * Get stock_point_id
     * @return string|null
     */
    public function getStockPointId();

    /**
     * Set stock_point_id
     * @param string $stockPointId
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     */
    public function setStockPointId($stockPointId);

    /**
     * Get external_profile_bucket_id
     * @return string|null
     */
    public function getExternalProfileBucketId();

    /**
     * Set external_profile_bucket_id
     * @param string $externalProfileBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     */
    public function setExternalProfileBucketId($externalProfileBucketId);
}
