<?php


namespace Riki\Wamb\Api\Data;

interface RuleSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{


    /**
     * Get Rule list.
     * @return \Riki\Wamb\Api\Data\RuleInterface[]
     */
    public function getItems();

    /**
     * Set id list.
     * @param \Riki\Wamb\Api\Data\RuleInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
