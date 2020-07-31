<?php
namespace Riki\Subscription\Model\ProductCart;

use Magento\Framework\Exception\CouldNotSaveException;
use Riki\Subscription\Api\Data\ProductCartSearchResultsInterfaceFactory;
use Riki\Subscription\Api\ProfileProductCartRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Riki\Subscription\Model\Constant;
use Magento\Framework\Api\SortOrder;

class ProfileProductCartRepository implements ProfileProductCartRepositoryInterface
{
    /**
     * @var ProductCartSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    protected $productCartFactory;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var ResourceModel\ProductCart
     */
    protected $resource;
    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    public function __construct(
        ProductCartSearchResultsInterfaceFactory $searchResultsFactory,
        FilterBuilder $filterBuilder,
        \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart $resource,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->searchResultsFactory = $searchResultsFactory;
        $this->filterBuilder = $filterBuilder;
        $this->resource = $resource;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productCartFactory = $productCartFactory;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->logger = $logger;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function get($id)
    {
        $profileProductCart = $this->productCartFactory->create()->load($id);
        return $profileProductCart->getDataModel();
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return mixed
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria)
    {
        //@TODO: fix search logic
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);

        $collection = $this->productCartFactory->create()->getCollection();
        foreach ($searchCriteria->getFilterGroups() as $group) {
            foreach ($group->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection() == SortOrder::SORT_ASC ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setItems($collection->getItems());

        return $searchResult;
    }

    /**
     * @param \Riki\Subscription\Api\Data\ApiProductCartInterface $profile
     */
    public function validate(\Riki\Subscription\Api\Data\ApiProductCartInterface $profile)
    {
    }

    /**
     * @param \Riki\Subscription\Api\Data\ApiProductCartInterface $profile
     */
    public function save(\Riki\Subscription\Api\Data\ApiProductCartInterface $productCartDataModel)
    {
        $productCartModel = $this->productCartFactory->create();

        if( $productCartDataModel->getCartId() ){
            $productCartModel->load($productCartDataModel->getCartId());
        }

        $productCartModel->addData(
            $this->extensibleDataObjectConverter->toNestedArray(
                $productCartDataModel,
                [],
                '\Riki\Subscription\Api\Data\ApiProductCartInterface'
            )
        );
        try {
            $this->resource->save($productCartModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $productCartModel;
    }

    /**
     * @param \Riki\Subscription\Api\Data\ApiProductCartInterface $productCart
     * @return bool
     */
    public function delete(\Riki\Subscription\Api\Data\ApiProductCartInterface $productCart)
    {
        return $this->deleteById($productCart->getCartId());
    }

    /**
     * @param $cartId
     * @return bool
     */
    public function deleteById($cartId)
    {
        $profileModel = $this->productCartFactory->create()->load($cartId);
        $profileModel->delete();
        return true;
    }
}