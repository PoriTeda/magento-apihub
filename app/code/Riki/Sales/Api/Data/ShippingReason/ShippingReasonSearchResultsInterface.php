<?php

namespace Riki\Sales\Api\Data\ShippingReason;

use Magento\Framework\Api\SearchResultsInterface;

interface ShippingReasonSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get data list.
     *
     * @return \Riki\Sales\Api\Data\ShippingReason\ShippingReasonInterface[]
     */
    public function getItems();

    /**
     * Set data list.
     *
     * @param \Riki\Sales\Api\Data\ShippingReason\ShippingReasonInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
