<?php

namespace Riki\NpAtobarai\Observer;

use Psr\Log\LoggerInterface;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;
use Riki\NpAtobarai\Model\Config\Source\TransactionStatus;
use Magento\Framework\Exception\LocalizedException;

class CancelTransactionAfterOrderReAssignation implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Riki\NpAtobarai\Model\Method\Adapter
     */
    private $adapter;

    /**
     * @var \Riki\NpAtobarai\Model\TransactionManagement
     */
    private $transactionManagement;

    /**
     * @var \Riki\NpAtobarai\Api\TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * CancelNpatobaraiOrderAssignation constructor.
     * @param LoggerInterface $logger
     * @param \Riki\NpAtobarai\Model\Method\Adapter $adapter
     * @param \Riki\NpAtobarai\Model\TransactionManagement $transactionManagement
     * @param \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        LoggerInterface $logger,
        \Riki\NpAtobarai\Model\Method\Adapter $adapter,
        \Riki\NpAtobarai\Model\TransactionManagement $transactionManagement,
        \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
    ) {
        $this->logger = $logger;
        $this->adapter = $adapter;
        $this->transactionManagement = $transactionManagement;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $payment = $order->getPayment();
        if ($payment && $payment->getMethod() == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
            if (!$order->hasShipments()) {
                try {
                    // Call [NP API] Cancel Order for the transaction
                    $this->_cancelRegisteredTransaction($order);

                    //delete old transaction
                    $this->_deleteOldTransaction($order);

                    //create new transaction
                    $this->_createNewTransactions($order);

                    $this->resetOrderStateAndStatus($order);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                    throw new LocalizedException(__(
                        '[ReassignNpAtobarai]The transaction has not been cancel'
                    ));
                }
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    private function _cancelRegisteredTransaction($order)
    {
        $transactions = $this->transactionManagement->getOrderTransactions($order);
        $transactionList = [];
        foreach ($transactions as $transaction) {
            if ($transaction->getNpTransactionId() &&
                $transaction->getNpTransactionStatus() != TransactionStatus::CANCELLED_STATUS_VALUE
            ) {
                $transactionList[$transaction->getTransactionId()] = $transaction;
            }
        }

        if (!empty($transactionList)) {
            $this->adapter->cancel($transactionList);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @throws LocalizedException
     */
    private function _deleteOldTransaction($order)
    {
        $transactions = $this->transactionManagement->getOrderTransactions($order);
        if ($transactions) {
            foreach ($transactions as $transaction) {
                $transaction->delete();
                $this->logger->info(
                    __('[ReassignNpAtobarai] The transaction #%1 has been delete', $transaction->getTransactionId())
                );
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @throws LocalizedException
     */
    private function _createNewTransactions(\Magento\Sales\Model\Order $order)
    {
        $this->transactionManagement->createTransactions($order->getId());
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    private function resetOrderStateAndStatus($order)
    {
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus(\Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_PENDING_NP);

        $order->getResource()->saveAttribute($order, ['status', 'state']);
    }
}
