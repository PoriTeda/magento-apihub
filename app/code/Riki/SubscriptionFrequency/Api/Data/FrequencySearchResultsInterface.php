<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionFrequency\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for frequency page search results.
 * @api
 */
interface FrequencySearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get pages list.
     *
     * @return \Riki\SubscriptionFrequency\Api\Data\FrequencyInterface[]
     */
    public function getItems();

    /**
     * Set pages list.
     *
     * @param \Riki\SubscriptionFrequency\Api\Data\FrequencyInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
