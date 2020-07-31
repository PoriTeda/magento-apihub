<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model;

/**
 * @package Magento\VisualMerchandiser\Model
 * @api
 * @since 100.0.2
 */
class Sorting
{
    /**
     * @var array
     */
    protected $sortClasses = [
        'UserDefined',
        'LowStockTop',
        'OutStockBottom',
        'SpecialPriceTop',
        'SpecialPriceBottom',
        'NewestTop',
        'SortColor',
        'Name\Ascending',
        'Name\Descending',
        'Sku\Ascending',
        'Sku\Descending',
        'Price\HighToLow',
        'Price\LowToHigh',
    ];

    /**
     * @var Sorting\Factory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $sortInstances = [];

    /**
     * @param Sorting\Factory $factory
     */
    public function __construct(Sorting\Factory $factory)
    {
        $this->factory = $factory;
        foreach ($this->sortClasses as $className) {
            $this->sortInstances[] = $this->factory->create($className);
        }
    }

    /**
     * @return array
     */
    public function getSortingOptions()
    {
        $options = [];
        foreach ($this->sortInstances as $idx => $instance) {
            $options[$idx] = $instance->getLabel();
        }
        return $options;
    }

    /**
     * Get the instance of the first option which is None
     *
     * @param int $sortOption
     * @return \Magento\VisualMerchandiser\Model\Sorting\SortInterface|null
     */
    public function getSortingInstance($sortOption)
    {
        if (isset($this->sortInstances[$sortOption])) {
            return $this->sortInstances[$sortOption];
        }
        return $this->sortInstances[0];
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function applySorting(
        \Magento\Catalog\Model\Category $category,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
    ) {
        $sortBuilder = $this->getSortingInstance($category->getAutomaticSorting());
        $_collection = $sortBuilder->sort($collection);

        // We need the collection to be clear for it to take effect after sorting is applied
        if ($_collection->isLoaded()) {
            $_collection->clear();
        }

        return $_collection;
    }
}
