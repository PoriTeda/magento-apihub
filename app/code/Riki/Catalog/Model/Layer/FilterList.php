<?php

namespace Riki\Catalog\Model\Layer;

class FilterList extends \Magento\Catalog\Model\Layer\FilterList
{
    protected $_productFilter = [];

    public function getFilters(\Magento\Catalog\Model\Layer $layer)
    {
        if (!count($this->filters)) {

            /** remove filter by category */

            //$this->filters = [
                //$this->objectManager->create($this->filterTypes[self::CATEGORY_FILTER], ['layer' => $layer]),
            //];

            foreach ($this->filterableAttributes->getList() as $attribute) {

                $attributeFilter = $this->createAttributeFilter($attribute, $layer);

                $this->filters[] = $attributeFilter;

                $filterParams = $attributeFilter->getRequestVar();

                $filterData = [];

                if ($attributeFilter->getItemsCount()) {
                    $filterData['itemsCount'] = $attributeFilter->getItemsCount();
                    $filterData['name'] = $attributeFilter->getName();
                    $filterData['items'] = [];
                    foreach ($attributeFilter->getItems() as $item) {
                        array_push($filterData['items'], [
                            'label' => $item->getLabel(),
                            'value' => $item->getValue(),
                            'url' => $item->getUrl(),
                            'count' => $item->getCount()
                        ]);
                    }
                }

                $this->_productFilter[$filterParams] = $filterData;
            }
        }

        return $this->filters;
    }

    /**
     * Get filter data for product, dont apply other filter
     *
     * @return array
     */
    public function getProductFilters()
    {
        return $this->_productFilter;
    }
}
