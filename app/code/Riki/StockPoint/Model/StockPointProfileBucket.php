<?php


namespace Riki\StockPoint\Model;

use Riki\StockPoint\Api\Data\StockPointProfileBucketInterface;

class StockPointProfileBucket extends \Magento\Framework\Model\AbstractModel implements StockPointProfileBucketInterface
{

    protected $_eventPrefix = 'stock_point_profile_bucket';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\StockPoint\Model\ResourceModel\StockPointProfileBucket::class);
    }

    /**
     * Get stock_point_profile_bucket_id
     * @return string
     */
    public function getStockPointProfileBucketId()
    {
        return $this->getData(self::STOCK_POINT_PROFILE_BUCKET_ID);
    }

    /**
     * Set stock_point_profile_bucket_id
     * @param string $stockPointProfileBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     */
    public function setStockPointProfileBucketId($stockPointProfileBucketId)
    {
        return $this->setData(self::STOCK_POINT_PROFILE_BUCKET_ID, $stockPointProfileBucketId);
    }

    /**
     * Get profile_bucket_id
     * @return string
     */
    public function getProfileBucketId()
    {
        return $this->getData(self::PROFILE_BUCKET_ID);
    }

    /**
     * Set profile_bucket_id
     * @param string $profileBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     */
    public function setProfileBucketId($profileBucketId)
    {
        return $this->setData(self::PROFILE_BUCKET_ID, $profileBucketId);
    }

    /**
     * Get stock_point_id
     * @return string
     */
    public function getStockPointId()
    {
        return $this->getData(self::STOCK_POINT_ID);
    }

    /**
     * Set stock_point_id
     * @param string $stockPointId
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     */
    public function setStockPointId($stockPointId)
    {
        return $this->setData(self::STOCK_POINT_ID, $stockPointId);
    }

    /**
     * Get external_profile_bucket_id
     * @return string
     */
    public function getExternalProfileBucketId()
    {
        return $this->getData(self::EXTERNAL_PROFILE_BUCKET_ID);
    }

    /**
     * Set external_profile_bucket_id
     * @param string $externalProfileBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     */
    public function setExternalProfileBucketId($externalProfileBucketId)
    {
        return $this->setData(self::EXTERNAL_PROFILE_BUCKET_ID, $externalProfileBucketId);
    }
}
