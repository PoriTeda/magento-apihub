<?php
namespace Riki\NpAtobarai\Cron;

use \Riki\NpAtobarai\Model\Config\Source\TransactionStatus;

class ValidateStatusTransaction
{
    const INVALIDATION_STATUSES = [
        TransactionStatus::BEFORE_VALIDATION_STATUS_VALUE,
        TransactionStatus::IN_VALIDATION_STATUS_VALUE
    ];

    /**
     * @var \Riki\NpAtobarai\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var \Riki\NpAtobarai\Model\Method\Adapter
     */
    protected $adapter;

    /**
     * ValidateStatusTransaction constructor.
     * @param \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Riki\NpAtobarai\Model\Method\Adapter $adapter
     */
    public function __construct(
        \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Riki\NpAtobarai\Model\Method\Adapter $adapter
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->adapter = $adapter;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $transactionGroupByOrder = $this->getTransactions();
        foreach ($transactionGroupByOrder as $transactionList) {
            $this->adapter->getValidationResult($transactionList);
        }
        return $this;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getTransactions()
    {
        $transactionStatus[] = $this->filterBuilder
            ->setField(\Riki\NpAtobarai\Api\Data\TransactionInterface::NP_TRANSACTION_STATUS)
            ->setValue(self::INVALIDATION_STATUSES)
            ->setConditionType('in')->create();

        $transactionStatus[] = $this->filterBuilder
            ->setField(\Riki\NpAtobarai\Api\Data\TransactionInterface::NP_TRANSACTION_STATUS)
            ->setConditionType('null')->create();

        $np_transaction_id = $this->filterBuilder
            ->setField(\Riki\NpAtobarai\Api\Data\TransactionInterface::NP_TRANSACTION_ID)
            ->setConditionType('notnull')->create();

        $filterGroup[] = $this->filterGroupBuilder
            ->setFilters($transactionStatus)
            ->create();

        $filterGroup[] = $this->filterGroupBuilder
            ->addFilter($np_transaction_id)
            ->create();

        $this->searchCriteriaBuilder->setFilterGroups($filterGroup);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->transactionRepository->getList($searchCriteria);
        $result = [];
        foreach ($searchResults->getItems() as $transaction) {
            $result[$transaction->getOrderId()][] = $transaction;
        }
        return $result;
    }
}