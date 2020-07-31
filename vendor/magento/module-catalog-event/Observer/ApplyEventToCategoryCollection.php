<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Observer;

use Magento\Framework\Event\ObserverInterface;

class ApplyEventToCategoryCollection implements ObserverInterface
{
    /**
     * @var \Magento\CatalogEvent\Helper\Data
     */
    protected $catalogEventData;

    /**
     * Event model factory
     *
     * @var \Magento\CatalogEvent\Model\Category\EventList
     */
    protected $categoryEventList;

    /**
     * @var CategoryEventApplier
     */
    protected $eventApplier;

    /**
     * @param \Magento\CatalogEvent\Helper\Data $catalogEventData
     * @param \Magento\CatalogEvent\Model\Category\EventList $categoryEventList
     * @param CategoryEventApplier $eventApplier
     */
    public function __construct(
        \Magento\CatalogEvent\Helper\Data $catalogEventData,
        \Magento\CatalogEvent\Model\Category\EventList $categoryEventList,
        CategoryEventApplier $eventApplier
    ) {
        $this->catalogEventData = $catalogEventData;
        $this->categoryEventList = $categoryEventList;
        $this->eventApplier = $eventApplier;
    }

    /**
     * Applies event to category collection
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->catalogEventData->isEnabled()) {
            return $this;
        }

        /** @var $categoryCollection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $categoryCollection = $observer->getEvent()->getCategoryCollection();
        $categoryIds = [];

        foreach ($categoryCollection->getColumnValues('path') as $path) {
            $categoryIds = array_merge($categoryIds, $this->eventApplier->parseCategoryPath($path));
        }

        if (!empty($categoryIds)) {
            $eventCollection = $this->categoryEventList->getEventCollection($categoryIds);
            foreach ($categoryCollection as $category) {
                $this->eventApplier->applyEventToCategory($category, $eventCollection);
            }
        }
        return $this;
    }
}
