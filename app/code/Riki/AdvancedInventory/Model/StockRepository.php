<?php

namespace Riki\AdvancedInventory\Model;

class StockRepository implements \Riki\AdvancedInventory\Api\StockRepositoryInterface
{

    protected $collectionFactory;

    protected $resourceModel;

    /**
     * @var \Magento\Framework\Api\SearchResultsInterface
     */
    protected $searchResultsFactory;

    public function __construct(
        \Wyomind\AdvancedInventory\Model\ResourceModel\Stock\CollectionFactory $collectionFactory,
        \Magento\Framework\Api\SearchResultsInterface $searchResults,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Stock $resourceModel
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResults;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @param \Riki\AdvancedInventory\Api\Data\StockInterface $stock
     * @return \Riki\AdvancedInventory\Api\Data\StockInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Riki\AdvancedInventory\Api\Data\StockInterface $stock){
        try {
            $this->resourceModel->save($stock);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__('Unable to save stock'));
        }

        return $stock;
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

                $collection->addFieldToFilter($filter->getField() , [$conditionType => $filter->getValue()]);
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
