<?php

namespace Riki\NpAtobarai\Observer;

use Riki\NpAtobarai\Api\Data\TransactionInterface;
use Riki\NpAtobarai\Model\Config\Source\TransactionStatus;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class CancelTransaction implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\NpAtobarai\Model\Method\Adapter
     */
    protected $adapter;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Riki\NpAtobarai\Model\Method\Adapter $adapter
     * @param \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Riki\NpAtobarai\Model\Method\Adapter $adapter,
        \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->adapter = $adapter;
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $orderId = $order->getId();
        if ($orderId && $order->getPayment()->getMethod() == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
            try {
                $transactions = $this->transactionRepository->getListByOrderId($orderId)->getItems();
                $registerTrans = [];
                foreach ($transactions as $transaction) {
                    if ($transaction->getData('np_transaction_id')) {
                        $status = $transaction->getData('np_transaction_status');
                        if (!$status || in_array($status, [
                                TransactionStatus::OK_STATUS_VALUE,
                                TransactionStatus::BEFORE_VALIDATION_STATUS_VALUE,
                                TransactionStatus::IN_VALIDATION_STATUS_VALUE,
                                TransactionStatus::NG_STATUS_VALUE,
                                TransactionStatus::PENDING_STATUS_VALUE,
                                TransactionStatus::ER_STATUS_VALUE
                            ])) {
                            $registerTrans[] = $transaction;
                        }
                    } else {
                        $this->cancelNpTransaction($transaction);
                    }
                }

                if ($registerTrans) {
                    $this->adapter->cancel($registerTrans);
                    $observer->getOrder()->resetShipmentsCollection();
                }
            } catch (\Exception $ex) {
                $this->logger->critical($ex);
            }
        }
    }

    /**
     * @param TransactionInterface $transaction
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function cancelNpTransaction(TransactionInterface $transaction)
    {
        $transaction->setData(['np_transaction_status' => TransactionStatus::CANCELLED_STATUS_VALUE]);
        $this->transactionRepository->save($transaction);
    }
}
