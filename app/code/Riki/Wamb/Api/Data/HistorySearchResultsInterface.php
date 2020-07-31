<?php


namespace Riki\Wamb\Api\Data;

interface HistorySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get CustomerHistory list.
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface[]
     */
    public function getItems();

    /**
     * Set history_id list.
     *
     * @param \Riki\Wamb\Api\Data\HistoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
