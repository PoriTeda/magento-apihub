<?php

namespace Riki\NpAtobarai\Gateway\Response\Registration;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;
use Riki\NpAtobarai\Api\Data\TransactionInterfaceFactory;
use Riki\NpAtobarai\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;

class SuccessHandler implements HandlerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var HistoryFactory
     */
    protected $orderHistoryFactory;

    /**
     * SuccessHandler constructor.
     * @param LoggerInterface $logger
     * @param TransactionRepositoryInterface $transactionRepositoryInterface
     * @param HistoryFactory $orderHistoryFactory
     */
    public function __construct(
        LoggerInterface $logger,
        TransactionRepositoryInterface $transactionRepositoryInterface,
        HistoryFactory $orderHistoryFactory
    ) {
        $this->logger = $logger;
        $this->transactionRepository = $transactionRepositoryInterface;
        $this->orderHistoryFactory = $orderHistoryFactory;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        /**
         * Update transaction data after call api
         */
        if (isset($response['results']) && !empty($response['results'])) {
            $transactionIds = [];
            foreach ($response['results'] as $item) {
                try {
                    if ($this->_updateTransaction($handlingSubject, $item)) {
                        $this->logger->info(
                            __('[RegisterTransaction]Transaction #%1 has been register successfully'),
                            $item
                        );
                        $transactionIds[$item['shop_transaction_id']] = $item['shop_transaction_id'];
                    } else {
                        $this->logger->info(
                            __('[RegisterTransaction]Transaction #%1 has not been register successfully'),
                            $item
                        );
                    }
                } catch (\Exception $e) {
                    $this->logger->info($e->getMessage(), $item);
                }
            }

            /**
             * If "np_transaction_id" of all dummy shipments are NOT NULL.Add a order history into this order:
             */
            if (!empty($transactionIds)) {
                $orders = [];
                foreach ($transactionIds as $transactionId) {
                    if (isset($handlingSubject[$transactionId])) {
                        $transaction = $handlingSubject[$transactionId];
                        $orders[$transaction->getOrderId()] = $transaction->getOrder();
                    }
                }

                if (!empty($orders)) {
                    foreach ($orders as $order) {
                        try {
                            $this->_addOrderHistory($order);
                        } catch (\Exception $e) {
                            $this->logger->critical($e);
                        }
                    }
                }
            }
        }
    }

    /**
     * Update transaction data after call api.Can not update status.it handle on api get status transaction
     * @param mixed $handlingSubject
     * @param array $item
     * @return bool
     */
    protected function _updateTransaction($handlingSubject, array $item)
    {
        if (isset($handlingSubject[$item['shop_transaction_id']])) {
            $transaction = $handlingSubject[$item['shop_transaction_id']];
            $transaction->setNpTransactionId($item['np_transaction_id']);
            $transaction->setRegisterErrorCodes(null);
            $transaction->save();
            return true;
        }
        return false;
    }

    /**
     * Add order history
     *
     * @param \Magento\Sales\Model\Order $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _addOrderHistory(\Magento\Sales\Model\Order $order)
    {
        try {
            $isUpdate = true;
            $transactionList = $this->transactionRepository->getListByOrderId($order->getId());
            if (!empty($transactionList)) {
                foreach ($transactionList->getItems() as $transaction) {
                    if (empty($transaction->getData('np_transaction_id'))) {
                        $isUpdate = false;
                        break;
                    }
                }
            }
            if ($isUpdate) {
                $this->orderHistoryFactory->create()->setStatus(
                    $order->getStatus()
                )->setComment(
                    __('The order has been registered successfully with the NP-Atobarai system')
                )->setEntityName(
                    $order->getEntityType()
                )->setParentId(
                    $order->getId()
                )->save();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
