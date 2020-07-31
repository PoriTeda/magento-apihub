<?php
namespace Riki\Subscription\Api\Data;
/**
 * @api
 */
interface ProductCartSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @api
     * @return \Riki\Subscription\Api\Data\ApiProductCartInterface[]
     */
    public function getItems();

    /**
     * @api
     * @param \Riki\Subscription\Api\Data\ApiProductCartInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}