<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Observer;

class CategoryEventApplier
{
    /**
     * Apply event to category
     *
     * @param \Magento\Framework\Data\Tree\Node|\Magento\Catalog\Model\Category $category
     * @param \Magento\Framework\Data\Collection $eventCollection
     * @return $this
     */
    public function applyEventToCategory($category, \Magento\Framework\Data\Collection $eventCollection)
    {
        foreach (array_reverse($this->parseCategoryPath($category->getPath())) as $categoryId) {
            // Walk through category path, search event for category
            $event = $eventCollection->getItemByColumnValue('category_id', $categoryId);
            if ($event) {
                $category->setEvent($event);
                return $this;
            }
        }

        return $this;
    }

    /**
     * Parse categories ids from category path
     *
     * @param string $path
     * @return string[]
     */
    public function parseCategoryPath($path)
    {
        return explode('/', $path);
    }
}
