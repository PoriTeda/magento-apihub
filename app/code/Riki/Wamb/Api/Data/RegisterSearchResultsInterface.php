<?php


namespace Riki\Wamb\Api\Data;

interface RegisterSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{


    /**
     * Get Customer list.
     * @return \Riki\Wamb\Api\Data\RegisterInterface[]
     */
    public function getItems();

    /**
     * Set customer_id list.
     * @param \Riki\Wamb\Api\Data\RegisterInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
