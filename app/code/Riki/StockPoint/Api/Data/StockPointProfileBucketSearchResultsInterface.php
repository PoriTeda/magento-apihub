<?php


namespace Riki\StockPoint\Api\Data;

interface StockPointProfileBucketSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get stock_point_profile_bucket list.
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface[]
     */
    public function getItems();

    /**
     * Set profile_bucket_id list.
     * @param \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
