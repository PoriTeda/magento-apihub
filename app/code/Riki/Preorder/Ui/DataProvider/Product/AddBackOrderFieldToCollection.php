<?php
namespace Riki\Preorder\Ui\DataProvider\Product;

use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFieldToCollectionInterface;

class AddBackOrderFieldToCollection implements AddFieldToCollectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function addField(Collection $collection, $field, $alias = null)
    {
        $stringCollection = (string)$collection->getSelectSql();
        if((strpos($stringCollection,'AS `at_backorders`') === false)) {
            $collection->joinField(
                'backorders',
                'cataloginventory_stock_item',
                'backorders',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );
        }
    }
}
