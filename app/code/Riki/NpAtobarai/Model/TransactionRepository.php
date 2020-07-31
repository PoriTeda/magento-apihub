<?php
namespace Riki\NpAtobarai\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Riki\NpAtobarai\Api\Data\TransactionInterface;
use Riki\NpAtobarai\Model\ResourceModel\Transaction as ResourceTransaction;
use Riki\NpAtobarai\Api\TransactionRepositoryInterface;
use Riki\NpAtobarai\Api\Data\TransactionInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\NpAtobarai\Api\Data\TransactionSearchResultsInterfaceFactory;
use Riki\NpAtobarai\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use Magento\Framework\Api\SortOrder;
use Riki\NpAtobarai\Model\Transaction;

class TransactionRepository implements TransactionRepositoryInterface
{

    /**
     * @var TransactionSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var TransactionCollectionFactory
     */
    protected $transactionCollectionFactory;

    /**
     * @var ResourceTransaction
     */
    protected $resource;

    /**
     * @var Transaction[]
     */
    protected $instances = [];

    /**
     * @var TransactionInterfaceFactory
     */
    protected $dataTransactionFactory;

    /**
     * TransactionRepository constructor.
     *
     * @param ResourceTransaction $resource
     * @param TransactionInterfaceFactory $dataTransactionFactory
     * @param TransactionCollectionFactory $transactionCollectionFactory
     * @param TransactionSearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        ResourceTransaction $resource,
        TransactionInterfaceFactory $dataTransactionFactory,
        TransactionCollectionFactory $transactionCollectionFactory,
        TransactionSearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataTransactionFactory = $dataTransactionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        TransactionInterface $transaction
    ) {
        try {
            $this->resource->save($transaction);
            $this->removeTransactionFromLocalCache($transaction->getId());
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the transaction: %1',
                $exception->getMessage()
            ));
        }
        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function getById(string $transactionId, $forceLoad = false)
    {
        $cachedTransaction = $this->getTransactionFromLocalCache($transactionId);
        if ($cachedTransaction === null || $forceLoad) {
            $transaction = $this->dataTransactionFactory->create();
            $this->resource->load($transaction, $transactionId);
            if (!$transaction->getId()) {
                throw new NoSuchEntityException(__('Transaction with id "%1" does not exist.', $transactionId));
            }
            $this->saveTransactionInLocalCache($transaction);
            $cachedTransaction = $transaction;
        }

        return $cachedTransaction;
    }

    /**
     * {@inheritdoc}
     */
    public function getByShipmentId($shipmentId)
    {
        $transaction = $this->dataTransactionFactory->create();
        if ($shipmentId) {
            $this->resource->load($transaction, $shipmentId, 'shipment_id');
            if (!$transaction->getId()) {
                throw new NoSuchEntityException(__('Shipment with id "%1" does not exist.', $shipmentId));
            }
        }

        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $collection = $this->transactionCollectionFactory->create();
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

        foreach ($collection->getItems() as $transaction) {
            $this->saveTransactionInLocalCache($transaction);
        }

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        TransactionInterface $transaction
    ) {
        try {
            $this->resource->delete($transaction);
            $this->removeTransactionFromLocalCache($transaction->getId());
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the transaction: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($transactionId)
    {
        return $this->delete($this->getById($transactionId));
    }

    /**
     * {@inheritdoc}
     */
    public function getListByOrderId($orderId)
    {
        $collection = $this->transactionCollectionFactory->create();
        $collection->addFieldToFilter('order_id', ['eq' => $orderId]);
        foreach ($collection->getItems() as $transaction) {
            $this->saveTransactionInLocalCache($transaction);
        }

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());
        return $searchResults;
    }

    /**
     * Gets transaction from the local cache by id.
     * @param string $id
     *
     * @return Transaction|null
     */
    private function getTransactionFromLocalCache(string $id)
    {
        return $this->instances[$id] ?? null;
    }

    /**
     * Removes transaction in the local cache.
     *
     * @param string $id
     */
    private function removeTransactionFromLocalCache(string $id)
    {
        unset($this->instances[$id]);
    }

    /**
     * Saves transaction in the local cache.
     *
     * @param Transaction $transaction
     */
    private function saveTransactionInLocalCache(Transaction $transaction)
    {
        $this->instances[$transaction->getId()] = $transaction;
    }
}
