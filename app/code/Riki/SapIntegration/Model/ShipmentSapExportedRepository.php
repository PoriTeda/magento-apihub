<?php

namespace Riki\SapIntegration\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;

class ShipmentSapExportedRepository implements \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface
{
    /**
     * @var \Riki\SapIntegration\Api\Data\ShipmentSapExportedSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Riki\SapIntegration\Model\ShipmentSapExportedFactory
     */
    protected $objectFactory;

    /**
     * ShipmentSapExportedRepository constructor.
     *
     * @param \Riki\SapIntegration\Api\Data\ShipmentSapExportedSearchResultsInterfaceFactory $searchResultsFactory
     * @param \Riki\SapIntegration\Model\ShipmentSapExportedFactory $objectFactory
     */
    public function __construct(
        \Riki\SapIntegration\Api\Data\ShipmentSapExportedSearchResultsInterfaceFactory $searchResultsFactory,
        \Riki\SapIntegration\Model\ShipmentSapExportedFactory $objectFactory
    ) {
        $this->searchResultsFactory = $searchResultsFactory;
        $this->objectFactory        = $objectFactory;
    }

    /**
     * @param \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface $object
     * @return \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface
     * @throws CouldNotSaveException
     */
    public function save(\Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface $object)
    {
        try {
            $object->save();
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $object;
    }

    /**
     * @param $id
     * @return \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $object = $this->objectFactory->create();
        $object->load($id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(__('Object with id "%1" does not exist.', $id));
        }
        return $object;
    }

    /**
     * @param \Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface $object
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(\Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface $object)
    {
        try {
            $object->delete();
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @param SearchCriteriaInterface $criteria
     * @return \Riki\SapIntegration\Api\Data\ShipmentSapExportedSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $searchResults->addFieldToFilter($fields, $conditions);
            }
        }

        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $searchResults->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $searchResults->setCurPage($criteria->getCurrentPage());
        $searchResults->setPageSize($criteria->getPageSize());
        return $searchResults;
    }
}
