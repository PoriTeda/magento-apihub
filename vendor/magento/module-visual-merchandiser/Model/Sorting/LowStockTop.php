<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Sorting;

use \Magento\Framework\DB\Select;

class LowStockTop extends SortAbstract implements SortInterface
{
    const XML_PATH_MIN_STOCK_THRESHOLD = 'visualmerchandiser/options/minimum_stock_threshold';

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function sort(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
    ) {
        if (!$this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            return $collection;
        }

        $minStockThreshold = (int)$this->scopeConfig->getValue(self::XML_PATH_MIN_STOCK_THRESHOLD);

        $baseSet = clone $collection;
        $finalSet = clone $collection;

        $collection->joinField(
            'child_id',
            'catalog_product_relation',
            'child_id',
            'parent_id=entity_id',
            null,
            'left'
        );

        $collection->joinField(
            'qty',
            'cataloginventory_stock_item',
            'qty',
            'product_id=entity_id',
            ['stock_id' => $this->getStockId()],
            'left'
        );

        $collection->joinField(
            'parent_qty',
            'cataloginventory_stock_item',
            'qty',
            'product_id=child_id',
            ['stock_id' => $this->getStockId()],
            'left'
        );

        $collection->getSelect()
            ->columns('IF(SUM(`at_parent_qty`.`qty`), SUM(`at_parent_qty`.`qty`), SUM(`at_qty`.`qty`)) as final_qty')
            ->group('entity_id')
            ->having('final_qty <= ?', $minStockThreshold)
            ->reset(Select::ORDER)
            ->order('final_qty '.$collection::SORT_ORDER_ASC);

        $resultIds = [];

        $collection->load();

        foreach ($collection as $item) {
            $resultIds[] = $item->getId();
        }

        $ids = array_unique(array_merge($resultIds, $baseSet->getAllIds()));

        $finalSet->getSelect()
            ->reset(Select::ORDER)
            ->reset(Select::WHERE);

        $finalSet->addAttributeToFilter('entity_id', ['in' => $ids]);
        if (count($ids)) {
            $finalSet->getSelect()->order(new \Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $ids). ')'));
        }

        return $finalSet;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return __("Move low stock to top");
    }
}
