<?php


namespace Riki\StockPoint\Api\Data;

interface StockPointDeliveryBucketSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get stock_point_delivery_bucket list.
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface[]
     */
    public function getItems();

    /**
     * Set delivery_bucket_id list.
     * @param \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
