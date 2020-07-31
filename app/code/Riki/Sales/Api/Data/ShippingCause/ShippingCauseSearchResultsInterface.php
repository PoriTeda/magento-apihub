<?php

namespace Riki\Sales\Api\Data\ShippingCause;

use Magento\Framework\Api\SearchResultsInterface;

interface ShippingCauseSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get data list.
     *
     * @return \Riki\Sales\Api\Data\ShippingCause\ShippingCauseInterface[]
     */
    public function getItems();

    /**
     * Set data list.
     *
     * @param \Riki\Sales\Api\Data\ShippingCause\ShippingCauseInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
