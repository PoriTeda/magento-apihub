<?php
namespace Riki\PointOfSale\Model;

class PointOfSaleRepository implements \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface
{
    /** @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory  */
    protected $collectionFactory;

    /** @var \Wyomind\PointOfSale\Model\PointOfSaleFactory  */
    protected $pointOfSaleFactory;

    /** @var \Magento\Framework\Api\SearchResultsInterface  */
    protected $searchResultsFactory;

    protected $instances = [];

    /**
     * PointOfSaleRepository constructor.
     * @param \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $collectionFactory
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     * @param \Magento\Framework\Api\SearchResultsInterface $searchResults
     */
    public function __construct(
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\CollectionFactory $collectionFactory,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Magento\Framework\Api\SearchResultsInterface $searchResults
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->pointOfSaleFactory = $pointOfSaleFactory;
        $this->searchResultsFactory = $searchResults;
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($id)
    {
        if(!isset($this->instances[$id])){
            $place = $this->pointOfSaleFactory->create()->load($id);
            if (!$place->getId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(__('Requested entity doesn\'t exist'));
            }

            $this->instances[$id] = $place;
        }

        return $this->instances[$id];
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria)
    {
        /** @var \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $collection */
        $collection = $this->collectionFactory->create();

        //Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            foreach ($group->getFilters() as $filter) {

                $conditionType = $filter->getConditionType() ? $filter->getConditionType() : 'eq';

                if ($filter->getField() == 'store_id' && $conditionType == 'eq') {
                    $collection->getPlacesByStoreId($filter->getValue(), null);
                } else {

                    $collection->addFieldToFilter($filter->getField() , [$conditionType => $filter->getValue()]);
                }
            }
        }
        /** @var SortOrder $sortOrder */
        foreach ((array)$searchCriteria->getSortOrders() as $sortOrder) {
            $field = $sortOrder->getField();
            $collection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
            );
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->load();

        $searchResult = $this->searchResultsFactory;
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        return $searchResult;
    }
}