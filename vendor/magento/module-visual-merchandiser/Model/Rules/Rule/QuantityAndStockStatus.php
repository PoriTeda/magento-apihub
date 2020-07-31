<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules\Rule;

class QuantityAndStockStatus extends \Magento\VisualMerchandiser\Model\Rules\Rule
{
    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    public function applyToCollection($collection)
    {
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

        $selectedOption = strtolower($this->_rule['value']);
        $collection->getSelect()
            ->group('entity_id')
            ->having(
                'IF(SUM(`at_parent_qty`.`qty`), SUM(`at_parent_qty`.`qty`), SUM(`at_qty`.`qty`)) '
                . $this->getOperatorExpression($this->_rule['operator']),
                $selectedOption
            )
            ->reset(\Magento\Framework\DB\Select::ORDER);
    }

    /**
     * @return int
     */
    protected function getStockId()
    {
        return \Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID;
    }
}
