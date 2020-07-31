<?php
namespace Riki\Subscription\Api\Data;
/**
 * @api
 */
interface ProfileSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @api
     * @return \Riki\Subscription\Api\Data\ApiProfileInterface[]
     */
    public function getItems();

    /**
     * @api
     * @param \Riki\Subscription\Api\Data\ApiProfileInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}