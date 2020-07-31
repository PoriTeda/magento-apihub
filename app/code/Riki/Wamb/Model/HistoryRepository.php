<?php


namespace Riki\Wamb\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\NoSuchEntityException;

class HistoryRepository implements \Riki\Wamb\Api\HistoryRepositoryInterface
{
    /**
     * @var \Riki\Wamb\Model\HistoryFactory
     */
    protected $historyFactory;

    /**
     * @var \Riki\Wamb\Api\Data\HistorySearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;


    /**
     * CustomerHistoryRepository constructor.
     *
     * @param \Riki\Wamb\Model\HistoryFactory $historyFactory
     * @param \Riki\Wamb\Api\Data\HistorySearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        \Riki\Wamb\Model\HistoryFactory $historyFactory,
        \Riki\Wamb\Api\Data\HistorySearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->historyFactory = $historyFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(\Riki\Wamb\Api\Data\HistoryInterface $history)
    {
        try {
            $history->save();
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the history: %1',
                $exception->getMessage()
            ));
        }

        return $history;
    }

    /**
     * Get by ID
     *
     * {@inheritdoc}
     */
    public function getById($historyId)
    {
        $history = $this->historyFactory->create();
        $history->load($historyId);
        if (!$history->getId()) {
            throw new NoSuchEntityException(__('History with id "%1" does not exist.', $historyId));
        }

        return $history;
    }

    /**
     * Get list
     *
     * {@inheritdoc}
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $collection = $this->historyFactory->create()->getCollection();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * Delete
     *
     * {@inheritdoc}
     */
    public function delete(\Riki\Wamb\Api\Data\HistoryInterface $history)
    {
        try {
            $history->delete();
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the CustomerHistory: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }

    /**
     * Delete by ID
     *
     * {@inheritdoc}
     */
    public function deleteById($historyId)
    {
        return $this->delete($this->getById($historyId));
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     *
     * @return \Riki\Wamb\Api\Data\HistoryInterface
     */
    public function createFromArray($data = [])
    {
        return $this->historyFactory->create()->addData($data);
    }
}
