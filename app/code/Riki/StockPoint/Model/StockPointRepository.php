<?php


namespace Riki\StockPoint\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\StockPoint\Model\ResourceModel\StockPoint\CollectionFactory as StockPointCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Riki\StockPoint\Api\Data\StockPointInterfaceFactory;
use Riki\StockPoint\Model\ResourceModel\StockPoint as ResourceStockPoint;
use Riki\StockPoint\Api\StockPointRepositoryInterface;
use Riki\StockPoint\Api\Data\StockPointSearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;

class StockPointRepository implements StockPointRepositoryInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var StockPointCollectionFactory
     */
    protected $stockPointCollectionFactory;
    /**
     * @var StockPointInterfaceFactory
     */
    protected $dataStockPointFactory;
    /**
     * @var ResourceStockPoint
     */
    protected $resource;
    /**
     * @var StockPointSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;
    /**
     * @var StockPointFactory
     */
    protected $stockPointFactory;
    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;
    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param ResourceStockPoint $resource
     * @param StockPointFactory $stockPointFactory
     * @param StockPointInterfaceFactory $dataStockPointFactory
     * @param StockPointCollectionFactory $stockPointCollectionFactory
     * @param StockPointSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceStockPoint $resource,
        StockPointFactory $stockPointFactory,
        StockPointInterfaceFactory $dataStockPointFactory,
        StockPointCollectionFactory $stockPointCollectionFactory,
        StockPointSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->resource = $resource;
        $this->stockPointFactory = $stockPointFactory;
        $this->stockPointCollectionFactory = $stockPointCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataStockPointFactory = $dataStockPointFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Riki\StockPoint\Api\Data\StockPointInterface $stockPoint
    ) {
        try {
            $this->resource->save($stockPoint);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the stockPoint: %1',
                $exception->getMessage()
            ));
        }
        return $stockPoint;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($stockPointId)
    {
        $stockPoint = $this->stockPointFactory->create();
        $this->resource->load($stockPoint, $stockPointId);
        if (!$stockPoint->getId()) {
            throw new NoSuchEntityException(__('stock_point with id "%1" does not exist.', $stockPointId));
        }
        return $stockPoint;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->stockPointCollectionFactory->create();
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
        \Riki\StockPoint\Api\Data\StockPointInterface $stockPoint
    ) {
        try {
            $this->resource->delete($stockPoint);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the stock_point: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($stockPointId)
    {
        return $this->delete($this->getById($stockPointId));
    }

    /**
     * @param $data
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveAndReturnStockPointId($data)
    {
        $query = $this->searchCriteriaBuilder
            ->addFilter('external_stock_point_id', $data["stock_point_id"])
            ->create();
        $result = $this->getList($query);
        if ($result->getTotalCount() == 0) {
            $stockPoint = $this->stockPointFactory->create();
            $stockPoint->setData("external_stock_point_id", $data["stock_point_id"]);
        } else {
            foreach ($result->getItems() as $item) {
                $id = $item->getStockPointId();
                break;
            }
            $stockPoint = $this->stockPointFactory->create()->load($id);
        }

        $stockPoint->setData("firstname", $data["stock_point_firstname"])
            ->setData("lastname", $data["stock_point_lastname"])
            ->setData("firstname_kana", $data["stock_point_firstnamekana"])
            ->setData("lastname_kana", $data["stock_point_lastnamekana"])
            ->setData("street", $data["stock_point_address"])
            ->setData("region_id", $data["stock_point_prefecture"])
            ->setData("postcode", $data["stock_point_postcode"])
            ->setData("telephone", $data["stock_point_telephone"]);

        $this->save($stockPoint);

        return $stockPoint->getStockPointId();
    }
}
