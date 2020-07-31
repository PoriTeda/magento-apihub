<?php

namespace Riki\Catalog\Plugin;

class AddQuantityFieldToCollection
{
    public function aroundAddField(\Magento\CatalogInventory\Ui\DataProvider\Product\AddQuantityFieldToCollection $subject, $proceed ,\Magento\Framework\Data\Collection $collection,$field, $alias = null)
    {
        $stringCollection = (string)$collection->getSelectSql();
        if(!(strpos($stringCollection,'AS `at_qty`') !== false)){
            $return = $proceed($collection,$field,$alias);
        }
        return false;
    }
}
