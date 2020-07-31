<?php

namespace Riki\Catalog\Block\Search;

class Navigation extends \Magento\LayeredNavigation\Block\Navigation
{
    protected $_productFilter = [];

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Riki\Catalog\Model\Layer\FilterList $filterList,
        \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag,
        array $data = []
    ) {
        parent::__construct($context, $layerResolver, $filterList, $visibilityFlag, $data);
    }

    /**
     * @return mixed
     */
    public function getProductFilters()
    {
        return $this->filterList->getProductFilters();
    }

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\AbstractFilter $filter
     * @return bool
     */
    public function getItemsCount($filter)
    {
        /* current filter for only product name, do not apply other filter like price, product attribute */
        $productFilter = $this->getProductFilters();

        /*param filter of this filter object*/
        $paramFilter = $filter->getRequestVar();

        if (!empty($productFilter[$paramFilter]) && $productFilter[$paramFilter]['itemsCount'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param $filter
     * @param $activeFilter
     * @return array
     */
    public function getFilterData($filter, $activeFilter)
    {
        $rs = [];

        /*active label will show as default for filter box*/
        $activeLabel = __('Please select');

        /*param filter of this filter object*/
        $paramFilter = $filter->getRequestVar();

        /*current filter list*/
        $productFilter = $this->getProductFilters();

        /*filter value*/
        $filterValue = $this->getRequest()->getParam($paramFilter);

        $items = [];

        $resetItems = [];

        if (!empty($productFilter[$paramFilter]) && $productFilter[$paramFilter]['itemsCount'] > 0) {

            /*filter item of this filter object*/
            $filterItems = $productFilter[$paramFilter]['items'];

            foreach ($filterItems as $fi) {

                if (!is_null($filterValue) && $fi['value'] == $filterValue) {

                    $resetItems['label'] = $activeLabel;
                    $resetItems['url'] = $this->getFilterRemoveUrl($paramFilter, $activeFilter);
                    $resetItems['count'] = 1;

                    $activeLabel = $fi['label'];
                } else {
                    array_push($items, $fi);
                }
            }

            $rs['activeLabel'] = $activeLabel;

            if ($resetItems) {
                /*push reset item to top of item array*/
                array_unshift($items,$resetItems);
            }

            $rs['items'] = $items;
        }

        return $rs;
    }

    /**
     * @param $paramFilter
     * @param $activeFilter
     * @return string
     */
    public function getFilterRemoveUrl($paramFilter, $activeFilter)
    {
        $rs = '';

        if ($activeFilter) {
            foreach ( $activeFilter as $item) {
                if ($item->getFilter()->getRequestVar() == $paramFilter) {
                    $rs = $item->getRemoveUrl();
                    break;
                }
            }
        }

        return $rs;
    }

}