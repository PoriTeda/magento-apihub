<?php
namespace Riki\NpAtobarai\Api\Data;

interface TransactionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get transaction list.
     * @return TransactionInterface[]
     */
    public function getItems();

    /**
     * Set transaction_id list.
     *
     * @param TransactionInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items);
}
