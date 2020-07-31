<?php


namespace Riki\StockPoint\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\StockPoint\Api\StockPointProfileBucketRepositoryInterface;
use Riki\StockPoint\Api\Data\StockPointProfileBucketInterfaceFactory;
use Riki\StockPoint\Model\ResourceModel\StockPointProfileBucket as ResourceStockPointProfileBucket;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Riki\StockPoint\Api\Data\StockPointProfileBucketSearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Riki\StockPoint\Model\ResourceModel\StockPointProfileBucket\CollectionFactory as ProfileBucketCollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;

class StockPointProfileBucketRepository implements StockPointProfileBucketRepositoryInterface
{
    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;
    /**
     * @var StockPointProfileBucketFactory
     */
    protected $stockPointProfileBucketFactory;
    /**
     * @var ResourceStockPointProfileBucket
     */
    protected $resource;
    /**
     * @var ProfileBucketCollectionFactory
     */
    protected $profileBucketCollectionFactory;
    /**
     * @var StockPointProfileBucketSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;
    /**
     * @var StockPointProfileBucketInterfaceFactory
     */
    protected $dataStockPointProfileBucketFactory;
    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var StockPointProfileBucket
     */
    protected $stockPointProfileBucket;

    /**
     * @param ResourceStockPointProfileBucket $resource
     * @param StockPointProfileBucketFactory $stockPointProfileBucketFactory
     * @param StockPointProfileBucketInterfaceFactory $dataStockPointProfileBucketFactory
     * @param ProfileBucketCollectionFactory $profileBucketCollectionFactory
     * @param StockPointProfileBucketSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceStockPointProfileBucket $resource,
        StockPointProfileBucketFactory $stockPointProfileBucketFactory,
        StockPointProfileBucketInterfaceFactory $dataStockPointProfileBucketFactory,
        ProfileBucketCollectionFactory $profileBucketCollectionFactory,
        StockPointProfileBucketSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        \Riki\StockPoint\Model\StockPointProfileBucket $stockPointProfileBucket
    ) {
        $this->resource = $resource;
        $this->stockPointProfileBucketFactory = $stockPointProfileBucketFactory;
        $this->profileBucketCollectionFactory = $profileBucketCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataStockPointProfileBucketFactory = $dataStockPointProfileBucketFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->stockPointProfileBucket = $stockPointProfileBucket;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface $stockPointProfileBucket
    ) {
        try {
            $this->resource->save($stockPointProfileBucket);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the stockPointProfileBucket: %1',
                $exception->getMessage()
            ));
        }
        return $stockPointProfileBucket;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($stockPointProfileBucketId)
    {
        $stockPointProfileBucket = $this->stockPointProfileBucketFactory->create();
        $this->resource->load($stockPointProfileBucket, $stockPointProfileBucketId);
        if (!$stockPointProfileBucket->getId()) {
            throw new NoSuchEntityException(
                __('stock_point_profile_bucket with id "%1" does not exist.', $stockPointProfileBucketId)
            );
        }
        return $stockPointProfileBucket;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->profileBucketCollectionFactory->create();
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
        \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface $stockPointProfileBucket
    ) {
        try {
            $this->resource->delete($stockPointProfileBucket);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the stock_point_profile_bucket: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($stockPointProfileBucketId)
    {
        return $this->delete($this->getById($stockPointProfileBucketId));
    }

    /**
     * @param $externalBucketId
     * @return null
     */
    public function getProfileBucketById($externalBucketId)
    {
        $bucketCollection = $this->profileBucketCollectionFactory->create();
        $bucketCollection->addFieldToFilter("external_profile_bucket_id", $externalBucketId);
        $bucketCollection->setPageSize(1);
        if ($bucketCollection->getSize()) {
            return $bucketCollection->getFirstItem();
        }
        return null;
    }

    /**
     * @param $stockPointId
     * @param $externalBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointProfileBucketInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createProfileBucket($stockPointId, $externalBucketId)
    {
        $bucketData = $this->stockPointProfileBucket;
        $bucketData->setStockPointId($stockPointId);
        $bucketData->setExternalProfileBucketId($externalBucketId);
        return $this->save($bucketData);
    }
}
