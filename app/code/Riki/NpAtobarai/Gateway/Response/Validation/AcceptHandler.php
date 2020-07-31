<?php
namespace Riki\NpAtobarai\Gateway\Response\Validation;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Riki\NpAtobarai\Model\Config\Source\TransactionStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Psr\Log\LoggerInterface;
use Riki\NpAtobarai\Api\Data\TransactionInterface;
use Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus;

/**
 * Class TransactionHandler
 */
class AcceptHandler implements HandlerInterface
{
    /**
     * @var \Riki\NpAtobarai\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var
     */
    protected $authoriRequiredDate;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * AcceptHandler constructor.
     * @param \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->resourceConnection = $resourceConnection;
        $this->timezone = $timezone;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($response['results'])) {
            return;
        }
        $salesConnection = $this->resourceConnection->getConnection('sales');
        $groupTransactionList = [];
        foreach ($response['results'] as $item) {
            $statusId = $item['authori_result'];
            $npTransactionId = $item['np_transaction_id'];
            if ($statusId == TransactionStatus::OK_STATUS_VALUE) {
                foreach ($handlingSubject as $transaction) {
                    if ($transaction->getNpTransactionId() == $npTransactionId) {
                        $transaction->setNpTransactionStatus($statusId);
                        if (isset($item['authori_required_date'])) {
                            $authoriDate = $item['authori_required_date'];
                            $authoriDate = $this->dateTime->date('Y-m-d H:i:s', $authoriDate);
                            $transaction->setAuthorizeRequiredAt($authoriDate);
                        }
                        $transaction->setAuthorizeErrorCodes(null);
                        $groupTransactionList[$transaction->getOrderId()][] = $transaction;
                    }
                }
            }
        }
        foreach ($groupTransactionList as $transactionList) {
            try {
                $salesConnection->beginTransaction();
                $this->_processHandle($transactionList);
                $salesConnection->commit();
            } catch (\Exception $e) {
                $salesConnection->rollBack();
                $this->logger->critical($e->getMessage());
            }
        }
    }

    /**
     * @param array $transactionList
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _processHandle($transactionList)
    {
        $orderId = null;
        foreach ($transactionList as $transaction) {
            $orderId = $transaction->getOrderId();
            $transaction->save();
        }

        if ($this->canCompleteOrder($orderId)) {
            $this->completeOrder($orderId);
        }
    }

    /**
     * Change status order
     * @param int $orderId
     */
    private function completeOrder($orderId)
    {
        if (!$orderId) {
            return;
        }
        $order = $this->orderFactory->create()->load($orderId);
        $order->setStatus(OrderStatus::STATUS_ORDER_NOT_SHIPPED);
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->addStatusHistoryComment($this->getCommentHistory());
        $order->setPaymentStatus(
            PaymentStatus::PAYMENT_AUTHORIZED
        );
        $order->save();
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    protected function getCommentHistory()
    {
        if ($this->authoriRequiredDate) {
            return __('The order has been accepted by NP-Atobarai system on %1', $this->authoriRequiredDate);
        }
        return '';
    }

    /**
     * @param int $orderId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function canCompleteOrder($orderId)
    {
        if ($orderId) {
            $searchTransactions = $this->transactionRepository->getListByOrderId($orderId);
            $totalTransaction = $searchTransactions->getTotalCount();
            $transactionStatusOk = 0;
             /** @var TransactionInterface $transaction */
            foreach ($searchTransactions->getItems() as $transaction) {
                if ($transaction->getNpTransactionStatus() == TransactionStatus::OK_STATUS_VALUE) {
                    $transactionStatusOk ++;
                }
                $this->authoriRequiredDate = max(
                    $this->authoriRequiredDate,
                    $this->timezone->date(new \DateTime($transaction->getAuthorizeRequiredAt()))
                        ->format('Y-m-d H:i:s')
                );
            }
            if ($totalTransaction > 0 && $transactionStatusOk == $totalTransaction) {
                return true;
            }
        }
        return false;
    }
}
