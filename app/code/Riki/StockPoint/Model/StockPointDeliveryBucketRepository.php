<?php

namespace Riki\StockPoint\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Riki\StockPoint\Model\ResourceModel\StockPointDeliveryBucket\CollectionFactory as DeliveryBucketCollectionFactory;
use Riki\StockPoint\Api\StockPointDeliveryBucketRepositoryInterface;
use Riki\StockPoint\Api\Data\StockPointDeliveryBucketSearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Riki\StockPoint\Model\ResourceModel\StockPointDeliveryBucket as ResourceStockPointDeliveryBucket;
use Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;

class StockPointDeliveryBucketRepository implements StockPointDeliveryBucketRepositoryInterface
{
    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;
    /**
     * @var StockPointDeliveryBucketFactory
     */
    protected $stockPointDeliveryBucketFactory;
    /**
     * @var ResourceStockPointDeliveryBucket
     */
    protected $resource;
    /**
     * @var StockPointDeliveryBucketSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;
    /**
     * @var DeliveryBucketCollectionFactory
     */
    protected $deliveryBucketCollectionFactory;
    /**
     * @var StockPointDeliveryBucketInterfaceFactory
     */
    protected $dataStockPointDeliveryBucketFactory;
    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceStockPointDeliveryBucket $resource
     * @param StockPointDeliveryBucketFactory $stockPointDeliveryBucketFactory
     * @param StockPointDeliveryBucketInterfaceFactory $dataStockPointDeliveryBucketFactory
     * @param DeliveryBucketCollectionFactory $deliveryBucketCollectionFactory
     * @param StockPointDeliveryBucketSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceStockPointDeliveryBucket $resource,
        StockPointDeliveryBucketFactory $stockPointDeliveryBucketFactory,
        StockPointDeliveryBucketInterfaceFactory $dataStockPointDeliveryBucketFactory,
        DeliveryBucketCollectionFactory $deliveryBucketCollectionFactory,
        StockPointDeliveryBucketSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->stockPointDeliveryBucketFactory = $stockPointDeliveryBucketFactory;
        $this->deliveryBucketCollectionFactory = $deliveryBucketCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataStockPointDeliveryBucketFactory = $dataStockPointDeliveryBucketFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface $stockPointDeliveryBucket
    ) {
        try {
            $this->resource->save($stockPointDeliveryBucket);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the stockPointDeliveryBucket: %1',
                $exception->getMessage()
            ));
        }
        return $stockPointDeliveryBucket;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($stockPointDeliveryBucketId)
    {
        $stockPointDeliveryBucket = $this->stockPointDeliveryBucketFactory->create();
        $this->resource->load($stockPointDeliveryBucket, $stockPointDeliveryBucketId);
        if (!$stockPointDeliveryBucket->getId()) {
            throw new NoSuchEntityException(
                __('stock_point_delivery_bucket with id "%1" does not exist.', $stockPointDeliveryBucketId)
            );
        }
        return $stockPointDeliveryBucket;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->deliveryBucketCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'store_id') {
                    $collection->addStoreFilter($filter->getValue(), false);
                    continue;
                }
                $fields[] = $filter->getField();
                $condition = $filter->getConditionType() ?: 'eq';
                $conditions[] = [$condition => $filter->getValue()];
            }
            $collection->addFieldToFilter($fields, $conditions);
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
     * {@inheritdoc}
     */
    public function delete(
        \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface $stockPointDeliveryBucket
    ) {
        try {
            $this->resource->delete($stockPointDeliveryBucket);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the stock_point_delivery_bucket: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($stockPointDeliveryBucketId)
    {
        return $this->delete($this->getById($stockPointDeliveryBucketId));
    }
}
