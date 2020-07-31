<?php

namespace Riki\NpAtobarai\Observer\Adminhtml;

use Magento\Framework\Event\ObserverInterface;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;
use Riki\NpAtobarai\Model\Config\Source\TransactionPaymentStatus as PaymentStatus;

class GetPaymentDate implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Riki\NpAtobarai\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var \Riki\NpAtobarai\Model\Method\Adapter
     */
    protected $adapter;

    /**
     * GetPaymentDate constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Riki\NpAtobarai\Model\Method\Adapter $adapter
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository,
        \Riki\NpAtobarai\Model\Method\Adapter $adapter
    ) {
        $this->orderRepository = $orderRepository;
        $this->transactionRepository = $transactionRepository;
        $this->adapter = $adapter;
    }

    /**
     * Get the latest Payment status all of transactions by order from NP-Atobarai
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderId = $observer->getRequest()->getParam('order_id');

        if (!$orderId) {
            return;
        }

        try {
            /** @var $order \Magento\Sales\Model\Order */
            $order = $this->orderRepository->get($orderId);

            if (!$order->getId()) {
                return;
            }

            $payment = $order->getPayment();
            if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
                return;
            }

            $paymentMethod = $payment->getMethod();
            if ($paymentMethod != NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
                return;
            }

            // Get all transactions of order
            $transactionsNotPaidYet = [];
            $transactions = $this->transactionRepository->getListByOrderId($order->getId());
            if ($transactions->getTotalCount()) {
                /** @var \Riki\NpAtobarai\Model\Transaction $transaction */
                foreach ($transactions->getItems() as $transaction) {
                    if ($transaction->getNpCustomerPaymentStatus() != PaymentStatus::PAID_STATUS_VALUE) {
                        $transactionsNotPaidYet[$transaction->getTransactionId()] = $transaction;
                    }
                }
            }

            // Call [NP API] Get Payment status to get update the latest status of Payment from NP-Atobarai
            if ($transactionsNotPaidYet) {
                $this->adapter->getPaymentStatus($transactionsNotPaidYet);
            }
        } catch (\Exception $e) {
            return;
        }
    }
}
