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
use Riki\Sales\Api\ShippingReasonRepositoryInterface;
use Riki\Sales\Api\Data\ShippingReason\ShippingReasonInterface;
use Riki\Sales\Api\Data\ShippingReason\ShippingReasonInterfaceFactory;
use Riki\Sales\Api\Data\ShippingReason\ShippingReasonSearchResultsInterfaceFactory;
use Riki\Sales\Model\ResourceModel\ShippingReason as ResourceData;
use Riki\Sales\Model\ResourceModel\ShippingReason\CollectionFactory as DataCollectionFactory;

class ShippingReasonRepository implements ShippingReasonRepositoryInterface
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
     * @var ShippingReasonSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var ShippingReasonInterfaceFactory
     */
    protected $shippingReasonInterfaceFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    public function __construct(
        ResourceData $resource,
        DataCollectionFactory $dataCollectionFactory,
        ShippingReasonSearchResultsInterfaceFactory $dataSearchResultsInterfaceFactory,
        ShippingReasonInterfaceFactory $shippingReasonInterfaceFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->resource = $resource;
        $this->dataCollectionFactory = $dataCollectionFactory;
        $this->searchResultsFactory = $dataSearchResultsInterfaceFactory;
        $this->shippingReasonInterfaceFactory = $shippingReasonInterfaceFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param ShippingReasonInterface $reason
     * @return ShippingReasonInterface
     * @throws CouldNotSaveException
     */
    public function save(ShippingReasonInterface $reason)
    {
        try {
            /** @var ShippingReasonInterface|\Magento\Framework\Model\AbstractModel $reason */
            $this->resource->save($reason);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the data: %1',
                $exception->getMessage()
            ));
        }
        return $reason;
    }

    /**
     * Get data record
     *
     * @param $reasonId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getById($reasonId)
    {
        if (!isset($this->instances[$reasonId])) {
            /** @var \Riki\Sales\Api\Data\ShippingReason\ShippingReasonInterface|\Magento\Framework\Model\AbstractModel $reason */
            $reason = $this->shippingReasonInterfaceFactory->create();
            $this->resource->load($reason, $reasonId);
            if (!$reason->getId()) {
                throw new NoSuchEntityException(__('Requested Shipping Reason doesn\'t exist'));
            }
            $this->instances[$reasonId] = $reason;
        }
        return $this->instances[$reasonId];
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Riki\Sales\Api\Data\ShippingReason\ShippingReasonSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Riki\Sales\Api\Data\ShippingReason\ShippingReasonSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \Riki\Sales\Model\ResourceModel\ShippingReason\Collection $collection */
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
            $dataDataObject = $this->shippingReasonInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray($dataDataObject, $datum->getData(), ShippingReasonInterface::class);
            $data[] = $dataDataObject;
        }
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults->setItems($data);
    }

    /**
     * @param ShippingReasonInterface $reason
     * @return bool
     * @throws CouldNotSaveException
     * @throws StateException
     */
    public function delete(ShippingReasonInterface $reason)
    {
        /** @var \Riki\Sales\Api\Data\ShippingReason\ShippingReasonInterface|\Magento\Framework\Model\AbstractModel $reason */
        $id = $reason->getId();
        try {
            unset($this->instances[$id]);
            $this->resource->delete($reason);
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
     * @param $reasonId
     * @return bool
     */
    public function deleteById($reasonId)
    {
        $reason = $this->getById($reasonId);
        return $this->delete($reason);
    }
}
