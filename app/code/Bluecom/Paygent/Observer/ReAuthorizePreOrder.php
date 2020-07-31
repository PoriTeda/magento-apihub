<?php

namespace Bluecom\Paygent\Observer;

use Bluecom\Paygent\Model\Paygent;
use Bluecom\Paygent\Model\PaygentManagement;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus;
use Riki\Preorder\Logger\Logger;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;

class ReAuthorizePreOrder implements ObserverInterface
{
    /**
     * @var \Riki\Preorder\Logger\Logger $logger
     */
    protected $logger;

    /**
     * @var \Bluecom\Paygent\Model\PaygentManagement
     */
    protected $paygentManagement;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * ReAuthorizePreOrder constructor.
     * @param PaygentManagement $paygentManagement
     * @param TransactionRepositoryInterface $transactionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Logger $logger
     */
    public function __construct(
        PaygentManagement $paygentManagement,
        TransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger
    ) {
        $this->paygentManagement = $paygentManagement;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->getAssignation()
            && $order->getPayment()
            && $order->getPayment()->getMethod() == Paygent::CODE
        ) {
            try {
                $unclosedAuthorizeTransactions = $this->getUnclosedAuthorizeTransactionsByPayment(
                    $order->getPayment()->getEntityId()
                );

                $this->reAuthorize($order);

                $order->save();

                //void old transactions
                foreach ($unclosedAuthorizeTransactions as $transaction) {
                    try {
                        $this->paygentManagement->voidTransaction($transaction);
                        $transaction->save();
                    } catch (\Exception $e) {
                        $this->logger->error(__(
                            'Void transaction "%1" error: %2',
                            $transaction->getTxnId(),
                            $e->getMessage()
                        ));
                    }
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * @param Order $order
     * @return $this
     * @throws LocalizedException
     */
    protected function reAuthorize(Order $order)
    {
        list($status, $result, $paymentObject) = $this->paygentManagement->authorize($order);

        if ($status) {
            $order->setPaymentAgent($order->getPayment()->getAdditionalInformation('payment_agent'));

            $order->setIsNotified(false);
            $order->addStatusHistoryComment(__('Re-authorized for pre-order successfully.'), false);
            $order->setRefTradingId($order->getIncrementId());

            $order->setPaymentStatus(PaymentStatus::PAYMENT_AUTHORIZED);

            $order->setState(
                Order::STATE_PROCESSING
            );

            $order->setStatus(
                OrderStatus::STATUS_ORDER_NOT_SHIPPED
            );
        } else {
            $errorDetail = $paymentObject->getResponseDetail() ? $paymentObject->getResponseDetail() : 'Others';
            $errorMessage = $this->paygentManagement->getPaygentModel()
                ->getErrorMessageByErrorCode($paymentObject->getResponseDetail());

            $message = __(
                'Order %1 has been authorized unsuccessfully due to issue from Paygent: %2',
                $order->getIncrementId(),
                $errorMessage
            );

            $order->setPaymentErrorCode($errorDetail);
            $order->setPaymentStatus(
                PaymentStatus::PAYMENT_AUTHORIZED_FAILED
            );
            $order->setState(Order::STATE_PROCESSING);
            $order->addStatusHistoryComment(
                __('Authorize failed: %1', $errorMessage),
                OrderStatus::STATUS_ORDER_PENDING_CC
            );

            $this->logger->info($message);
        }

        return $this;
    }

    /**
     * @param $paymentId
     * @return TransactionInterface[]
     */
    protected function getUnclosedAuthorizeTransactionsByPayment($paymentId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('payment_id', $paymentId, 'eq')
            ->addFilter('txn_type', Transaction::TYPE_AUTH, 'eq')
            ->addFilter('is_closed', 1, 'neq')
            ->create();

        $searchResults = $this->transactionRepository->getList($searchCriteria);

        return $searchResults->getItems();
    }
}
