<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Observer;

class ProductEventApplier
{
    /**
     * Event model factory
     *
     * @var \Magento\CatalogEvent\Model\Category\EventList
     */
    protected $categoryEventList;

    /**
     * @param \Magento\CatalogEvent\Model\Category\EventList $eventList
     */
    public function __construct(\Magento\CatalogEvent\Model\Category\EventList $eventList)
    {
        $this->categoryEventList = $eventList;
    }

    /**
     * Applies event to product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return $this
     */
    public function applyEventToProduct($product)
    {
        if ($product && !$product->hasEvent()) {
            $event = $this->getProductEvent($product);
            $product->setEvent($event);
        }
        return $this;
    }

    /**
     * Get event for product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\CatalogEvent\Model\Event|false
     */
    protected function getProductEvent($product)
    {
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            return false;
        }

        $categoryIds = $product->getCategoryIds();

        $event = false;
        $noOpenEvent = false;
        $eventCount = 0;
        foreach ($categoryIds as $categoryId) {
            $categoryEvent = $this->categoryEventList->getEventInStore($categoryId);
            if ($categoryEvent === false || $categoryEvent === null) {
                continue;
            } elseif ($categoryEvent->getStatus() == \Magento\CatalogEvent\Model\Event::STATUS_OPEN) {
                $event = $categoryEvent;
            } else {
                $noOpenEvent = $categoryEvent;
            }
            $eventCount++;
        }

        if ($eventCount > 1) {
            $product->setEventNoTicker(true);
        }

        return $event ? $event : $noOpenEvent;
    }
}
