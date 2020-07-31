<?php


namespace Riki\StockPoint\Api\Data;

interface StockPointSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get stock_point list.
     * @return \Riki\StockPoint\Api\Data\StockPointInterface[]
     */
    public function getItems();

    /**
     * Set stock_point_id list.
     * @param \Riki\StockPoint\Api\Data\StockPointInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
