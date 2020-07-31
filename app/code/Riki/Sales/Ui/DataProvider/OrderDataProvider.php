<?php
namespace Riki\Sales\Ui\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;


/**
 * Class DataProvider
 */
class OrderDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{

    /**
     * OrderDataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Framework\View\Element\UiComponent\DataProvider\Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        \Magento\Framework\Registry $coreRegistry,
        array $meta = [],
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($name,$primaryFieldName,$requestFieldName,$reporting,$searchCriteriaBuilder,$request,$filterBuilder,$meta,$data);
    }

    /**
     * @inheritdoc
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {

        $field = $filter->getField();
        $filterValue = $filter->getValue();

        if($field == 'customer_membership'){

            if ($filterValue) {
                $filter->setConditionType('finset');
            }
        }
        parent::addFilter($filter);
    }

    /**
     * Returns Search result
     *
     * @return SearchResultInterface
     */
    public function getSearchResult()
    {
         if('sales_subscription_order_grid_data_source' == $this->getName()){
             $this->_coreRegistry->unregister('is_load_subscription_order');
             $this->_coreRegistry->register('is_load_subscription_order',true);
         }
        return $this->reporting->search($this->getSearchCriteria());
    }
}
