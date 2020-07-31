<?php

namespace Riki\Catalog\Plugin\Model\Layer;

class FilterList
{
    public function aroundGetFilters(
        \Magento\Catalog\Model\Layer\FilterList $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Layer $layer
    )
    {
        $result = $proceed($layer);

        foreach($result as  $index  => $filter){
            if($filter instanceof \Magento\CatalogSearch\Model\Layer\Filter\Category){
                unset($result[$index]);
                break;
            }
        }

        return $result;
    }
}
