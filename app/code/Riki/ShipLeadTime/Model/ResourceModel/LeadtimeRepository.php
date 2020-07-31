<?php
namespace Riki\ShipLeadTime\Model\ResourceModel;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

class LeadtimeRepository implements \Riki\ShipLeadTime\Api\LeadtimeRepositoryInterface
{
    /** @var \Riki\ShipLeadTime\Model\LeadtimeFactory  */
    protected $leadTimeFactory;

    /**
     * @var \Magento\Framework\Api\SearchResultsInterface
     */
    protected $searchResults;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    protected $instances = [];

    public function __construct (
        \Magento\Framework\Event\ManagerInterface $eventManagerInterface,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Riki\ShipLeadTime\Model\LeadtimeFactory $leadTimeFactory,
        \Magento\Framework\Api\SearchResultsInterface $searchResults
    ) {
        $this->eventManager = $eventManagerInterface;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->leadTimeFactory = $leadTimeFactory;
        $this->searchResults = $searchResults;
    }

    /**
     * @param \Riki\ShipLeadTime\Api\Data\LeadtimeInterface $leadTime
     * @return \Riki\ShipLeadTime\Model\Leadtime
     */
    public function save(\Riki\ShipLeadTime\Api\Data\LeadtimeInterface $leadTime)
    {
        $this->validate($leadTime);

        $leadTimeData = $this->extensibleDataObjectConverter->toNestedArray(
            $leadTime,
            [],
            '\Riki\ShipLeadTime\Api\Data\LeadtimeInterface'
        );

        /** @var \Riki\ShipLeadTime\Model\Leadtime $leadTimeModel */
        $leadTimeModel = $this->leadTimeFactory->create();

        $leadTimeModel->addData($leadTimeData);

        $leadTimeModel->setId($leadTime->getId());

        $leadTimeModel->save();

        $this->instances[$leadTime->getId()] = $leadTimeModel;

        $this->eventManager->dispatch(
            'ship_leadtime_save_after_data_object',
            ['leadtime' => $leadTimeModel, 'orig_leadtime' => $leadTime]
        );

        return $leadTimeModel;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, $editMode = false)
    {
        if(!isset($this->instances[$id])){
            $leadTimeModel = $this->leadTimeFactory->create()->load($id);
            if (!$leadTimeModel->getId()) {
                throw new NoSuchEntityException(__('Requested entity doesn\'t exist'));
            }

            if ($editMode) {
                $leadTimeModel->setData('_edit_mode', true);
            }

            $this->instances[$id] = $leadTimeModel;
        }

        return $this->instances[$id];
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param bool $forEdit
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria, $forEdit = false)
    {
        /** @var \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection $collection */
        $collection = $this->leadTimeFactory->create()->getCollection();

        //Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }

        if (!$forEdit) {
            $collection->addFieldToFilter('is_active', 1);
            $collection->addFieldToFilter('priority', ['notnull'   =>  true]);
        }

        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($searchCriteria->getSortOrders() as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $searchResult = $this->searchResults;
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(\Riki\ShipLeadTime\Api\Data\LeadtimeInterface $leadTime)
    {
        return $this->deleteById($leadTime->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        /** @var \Riki\ShipLeadTime\Model\Leadtime $leadTimeModel */
        $leadTimeModel = $this->leadTimeFactory->create()->load($id);
        $leadTimeModel->delete();
        unset($this->instances[$id]);
        return true;
    }

    /**
     * Check warehouse with a prefecture is valid in shipping lead time
     *
     * @param $posCode
     * @param $prefecture
     * @param $deliveryType
     * @return bool
     */
    public function checkWarehouseIsValid($posCode,$prefecture,$deliveryType) {

        $leadTimeModel = $this->leadTimeFactory->create()->getCollection();
        $leadTimeModel->addFieldToFilter('warehourse_id',$posCode);
        $leadTimeModel->addFieldToFilter('pref_id',$prefecture);
        $leadTimeModel->addFieldToFilter('delivery_type_code',$deliveryType);
        $leadTimeModel->addFieldToFilter('is_active',1);
        $leadTimeModel->setPageSize(1);
        if(sizeof($leadTimeModel) == 1) {
            return true;
        } else {
            return false;
        }
    }

    private function validate(\Riki\ShipLeadTime\Api\Data\LeadtimeInterface $leadTime)
    {
        $exception = new InputException();
        if (!\Zend_Validate::is(trim($leadTime->getShippingLeadTime()), 'NotEmpty')) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'shipping_lead_time']));
        }

        if ($exception->wasErrorAdded()) {
            throw $exception;
        }
    }

    /**
     *
     */
    protected function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection $collection
    ) {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';

            $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }
}
