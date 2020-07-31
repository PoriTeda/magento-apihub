<?php

namespace Riki\NpAtobarai\Cron;

use Exception;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
use Riki\NpAtobarai\Api\TransactionRepositoryInterface;
use Riki\NpAtobarai\Model\Config\Source\TransactionPaymentStatus;
use Riki\NpAtobarai\Model\Method\Adapter;
use Riki\NpAtobarai\Model\Transaction;
use \Riki\Framework\Helper\Cron as CronLocker;

class TransactionGetPaymentStatus
{
    const FILE_NAME = 'riki_np_atobarai_transaction_get_payment_status';
    const LIMIT_TRANSACTION = 100;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CronLocker
     */
    protected $cronLockerHelper;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * TransactionGetPaymentStatus constructor.
     *
     * @param LoggerInterface $logger
     * @param CronLocker $cronLockerHelper
     * @param TransactionRepositoryInterface $transactionRepository
     * @param Adapter $adapter
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        LoggerInterface $logger,
        CronLocker $cronLockerHelper,
        TransactionRepositoryInterface $transactionRepository,
        Adapter $adapter,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->logger = $logger;
        $this->cronLockerHelper = $cronLockerHelper;
        $this->transactionRepository = $transactionRepository;
        $this->adapter = $adapter;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get Payment Status
     */
    public function execute()
    {
        // Check cron status to avoid overlap.
        $this->cronLockerHelper->setLockFileName(self::FILE_NAME);
        if ($this->cronLockerHelper->isLocked()) {
            $this->logger->info(
                $this->cronLockerHelper->getLockMessage()
            );
            return;
        }

        try {
            $this->cronLockerHelper->lockProcess();
            $filters = [
                $this->filterBuilder
                    ->create()
                    ->setField('np_customer_payment_status')
                    ->setValue(true)
                    ->setConditionType('null'),
                $this->filterBuilder
                    ->create()
                    ->setField('np_customer_payment_status')
                    ->setValue(TransactionPaymentStatus::NOT_PAID_YET_STATUS_VALUE)
                    ->setConditionType('eq'),
            ];
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('is_shipped_out_registered', Transaction::REGISTERED_SHIPPED_OUT)
                ->addFilters($filters)
                ->setPageSize(self::LIMIT_TRANSACTION)
                ->setCurrentPage(1)
                ->create();

            $result = $this->transactionRepository->getList($searchCriteria);
            $npTransactions = $result->getItems();

            try {
                if (!empty($npTransactions)) {
                    $this->adapter->getPaymentStatus($npTransactions);
                }
            } catch (Exception $e) {
                $this->logger->info(
                    'Something wrong is happening when Cron riki_np_atobarai_transaction_get_payment_status run',
                    [$e->getMessage()]
                );
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        } finally {
            // Delete lock folder after cron has finished running.
            $this->cronLockerHelper->unLockProcess();
        }
    }
}
