<?php

namespace Riki\Sales\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Sales\Api\ShippingCauseRepositoryInterface;
use Riki\Sales\Api\Data\ShippingCause\ShippingCauseInterface;
use Riki\Sales\Api\Data\ShippingCause\ShippingCauseInterfaceFactory;
use Riki\Sales\Api\Data\ShippingCause\ShippingCauseSearchResultsInterfaceFactory;
use Riki\Sales\Model\ResourceModel\ShippingCause as ResourceData;
use Riki\Sales\Model\ResourceModel\ShippingCause\CollectionFactory as DataCollectionFactory;

class ShippingCauseRepository implements ShippingCauseRepositoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var ResourceData
     */
    protected $resource;

    /**
     * @var DataCollectionFactory
     */
    protected $dataCollectionFactory;

    /**
     * @var ShippingCauseSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var ShippingCauseInterfaceFactory
     */
    protected $shippingCauseInterfaceFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    public function __construct(
        ResourceData $resource,
        DataCollectionFactory $dataCollectionFactory,
        ShippingCauseSearchResultsInterfaceFactory $dataSearchResultsInterfaceFactory,
        ShippingCauseInterfaceFactory $shippingCauseInterfaceFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->resource = $resource;
        $this->dataCollectionFactory = $dataCollectionFactory;
        $this->searchResultsFactory = $dataSearchResultsInterfaceFactory;
        $this->shippingCauseInterfaceFactory = $shippingCauseInterfaceFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param ShippingCauseInterface $cause
     * @return ShippingCauseInterface
     * @throws CouldNotSaveException
     */
    public function save(ShippingCauseInterface $cause)
    {
        try {
            /** @var ShippingCauseInterface|\Magento\Framework\Model\AbstractModel $cause */
            $this->resource->save($cause);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the data: %1',
                $exception->getMessage()
            ));
        }
        return $cause;
    }

    /**
     * Get data record
     *
     * @param $causeId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getById($causeId)
    {
        if (!isset($this->instances[$causeId])) {
            /** @var \Riki\Sales\Api\Data\ShippingCause\ShippingCauseInterface|\Magento\Framework\Model\AbstractModel $cause */
            $cause = $this->shippingCauseInterfaceFactory->create();
            $this->resource->load($cause, $causeId);
            if (!$cause->getId()) {
                throw new NoSuchEntityException(__('Requested Shipping Cause doesn\'t exist'));
            }
            $this->instances[$causeId] = $cause;
        }
        return $this->instances[$causeId];
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Riki\Sales\Api\Data\ShippingCause\ShippingCauseSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Riki\Sales\Api\Data\ShippingCause\ShippingCauseSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \Riki\Sales\Model\ResourceModel\ShippingCause\Collection $collection */
        $collection = $this->dataCollectionFactory->create();

        //Add filters from root filter group to the collection
        /** @var FilterGroup $group */
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }
        $sortOrders = $searchCriteria->getSortOrders();
        /** @var SortOrder $sortOrder */
        if ($sortOrders) {
            foreach ($searchCriteria->getSortOrders() as $sortOrder) {
                $field = $sortOrder->getField();
                $collection->addOrder(
                    $field,
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        } else {
            $field = 'id';
            $collection->addOrder($field, 'ASC');
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        $data = [];
        foreach ($collection as $datum) {
            $dataDataObject = $this->shippingCauseInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray($dataDataObject, $datum->getData(), ShippingCauseInterface::class);
            $data[] = $dataDataObject;
        }
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults->setItems($data);
    }

    /**
     * @param ShippingCauseInterface $cause
     * @return bool
     * @throws CouldNotSaveException
     * @throws StateException
     */
    public function delete(ShippingCauseInterface $cause)
    {
        /** @var \Riki\Sales\Api\Data\ShippingCause\ShippingCauseInterface|\Magento\Framework\Model\AbstractModel $cause */
        $id = $cause->getId();
        try {
            unset($this->instances[$id]);
            $this->resource->delete($cause);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Exception $e) {
            throw new StateException(
                __('Unable to remove data %1', $id)
            );
        }
        unset($this->instances[$id]);
        return true;
    }

    /**
     * @param $causeId
     * @return bool
     */
    public function deleteById($causeId)
    {
        $cause = $this->getById($causeId);
        return $this->delete($cause);
    }
}
