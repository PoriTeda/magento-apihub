<?php

namespace Riki\NpAtobarai\Cron;

use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderFactory;
use Riki\NpAtobarai\Model\Method\Adapter;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Magento\Sales\Api\OrderRepositoryInterface;
use Riki\Framework\Helper\Cron as CronLocker;
use Riki\NpAtobarai\Api\TransactionRepositoryInterface;
use Riki\NpAtobarai\Model\Config\Source\TransactionStatus;

class RegisterTransaction
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var CronLocker
     */
    protected $cronLockerHelper;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\NpAtobarai\Model\Method\Adapter
     */
    protected $adapter;

    /**
     * @var \Magento\Framework\Api\SortOrderFactory
     */
    protected $sortOrderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * RegisterTransaction constructor.
     * @param LoggerInterface $logger
     * @param CronLocker $cronLockerHelper
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderFactory $sortOrderFactory
     * @param Adapter $adpater
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        LoggerInterface $logger,
        CronLocker $cronLockerHelper,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderFactory $sortOrderFactory,
        Adapter $adpater,
        OrderRepositoryInterface $orderRepositoryInterface,
        TransactionRepositoryInterface $transactionRepository
    ) {
        $this->logger = $logger;
        $this->cronLockerHelper = $cronLockerHelper;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->adapter = $adpater;
        $this->sortOrderFactory = $sortOrderFactory;
        $this->orderRepository = $orderRepositoryInterface;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Process data
     */
    public function execute()
    {
        // Check cron status to avoid overlap.
        $this->cronLockerHelper->setLockFileName('riki_np_atobarai_transaction_register.lock');
        if ($this->cronLockerHelper->isLocked()) {
            $this->logger->info(
                $this->cronLockerHelper->getLockMessage()
            );
            return;
        }

        try {
            $this->cronLockerHelper->lockProcess();
            $this->logger->info('[RegisterTransaction] Cron register transaction beginning run');
            $ordersTransactions = $this->getOrdersTransactions();
            foreach ($ordersTransactions as $transactionList) {
                try {
                    $this->adapter->register($transactionList);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
            $this->logger->info('[RegisterTransaction] Cron register transaction run complete');
        } catch (\Exception $e) {
            $this->logger->critical($e);
        } finally {
            // Delete lock folder after cron has finished running.
            $this->cronLockerHelper->unLockProcess();
        }
    }

    /**
     * Get Orders
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getOrdersTransactions()
    {
        $filters[] = $this->filterBuilder->setField('status')
            ->setValue(OrderStatus::STATUS_ORDER_PENDING_NP)
            ->setConditionType('eq')
            ->create();

        $sortOrder = $this->sortOrderFactory->create()
            ->setField('entity_id')
            ->setDirection(SortOrder::SORT_ASC);

        $searchCriteria = $this->searchCriteriaBuilder
            ->setSortOrders([$sortOrder])
            ->addFilters($filters)
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria);

        $ordersTransactions = [];
        foreach ($orders->getItems() as $order) {
            $transactionList = $this->getListTransactionByOrderId($order->getId());
            if (!empty($transactionList)) {
                $ordersTransactions[] = $transactionList;
            }
        }
        return $ordersTransactions;
    }

    /**
     * @param int $orderId
     * @return null|\Riki\NpAtobarai\Api\Data\TransactionInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getListTransactionByOrderId($orderId)
    {
        $filterGroup[] = $this->filterGroupBuilder
            ->addFilter(
                $this->filterBuilder
                    ->create()
                    ->setField('np_transaction_id')
                    ->setValue(true)
                    ->setConditionType('null')
            )
            ->addFilter(
                $this->filterBuilder
                    ->create()
                    ->setField('np_transaction_id')
                    ->setValue('')
                    ->setConditionType('eq')
            )
            ->create();

        $filterGroup[] = $this->filterGroupBuilder
            ->addFilter(
                $this->filterBuilder
                    ->create()
                    ->setField('np_transaction_status')
                    ->setValue(true)
                    ->setConditionType('null')
            )
            ->addFilter(
                $this->filterBuilder
                    ->create()
                    ->setField('np_transaction_status')
                    ->setValue(TransactionStatus::CANCELLED_STATUS_VALUE)
                    ->setConditionType('neq')
            )
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->setFilterGroups($filterGroup)
            ->addFilter('order_id', $orderId)
            ->create();
        $transactionList = $this->transactionRepository->getList($searchCriteria);
        return $transactionList->getItems();
    }
}
