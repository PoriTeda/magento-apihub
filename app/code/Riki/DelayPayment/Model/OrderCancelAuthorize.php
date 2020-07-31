<?php
namespace Riki\DelayPayment\Model;

use Magento\Sales\Model\Order\Payment;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;

/**
 * Class OrderCancelAuthorize
 *
 * @package Riki\DelayPayment\Model
 */
class OrderCancelAuthorize
{
    const PAYMENT_TRANSACTION_TYPE_AUTHORIZATION = 'authorization';
    /**
     * @var \Riki\DelayPayment\Helper\Data
     */
    protected $helperData;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteria;
    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $paymentTransactionRepository;
    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;
    /**
     * @var array
     */
    protected $exceptOrderStatus;
    /**
     * OrderCancelAuthorize constructor.
     *
     * @param \Riki\DelayPayment\Helper\Data $helperData
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteria
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        \Riki\DelayPayment\Helper\Data $helperData,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteria,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
    ) {
        $this->helperData = $helperData;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->searchCriteria = $searchCriteria;
        $this->paymentTransactionRepository = $transactionRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->exceptOrderStatus = [
            OrderStatus::STATUS_ORDER_COMPLETE,
            OrderStatus::STATUS_ORDER_CANCELED,
            OrderStatus::STATUS_ORDER_CAPTURE_FAILED,
            OrderStatus::STATUS_ORDER_CRD_FEEDBACK,
            OrderStatus::STATUS_ORDER_SUSPECTED_FRAUD,
            OrderStatus::STATUS_ORDER_SUSPICIOUS,
        ];
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getAuthorizedOrders()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('riki_type', \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT);
        $orderCollection->addFieldToFilter('is_incomplete_generate_profile_order', 0);
        $orderCollection->join(
            'sales_payment_transaction',
            'main_table.entity_id = sales_payment_transaction.order_id',
            'sales_payment_transaction.order_id'
        );
        $orderCollection->addFieldToFilter('status', ['nin'=>$this->exceptOrderStatus]);
        $orderCollection->addFieldToFilter(
            'sales_payment_transaction.txn_type',
            self::PAYMENT_TRANSACTION_TYPE_AUTHORIZATION
        );
        $orderCollection->addFieldToFilter('sales_payment_transaction.is_closed', 0);
        $orderCollection->setOrder('main_table.entity_id', 'DESC')
            ->distinct(true);

        return $orderCollection->getItems();
    }

    /**
     * cancel payment authorization
     * @param \Magento\Sales\Model\Order $order
     */
    public function cancelAuthorization(\Magento\Sales\Model\Order $order)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();
        if ($payment &&
            $payment->getMethod() == \Bluecom\Paygent\Model\Paygent::CODE &&
            !in_array($order->getStatus(), $this->exceptOrderStatus)
        ) {
            $lastTransaction = $this->getLastPaymentTransaction($order->getEntityId());
            if ($lastTransaction instanceof \Magento\Sales\Model\Order\Payment\Transaction) {
                if ($lastTransaction->getTxnType() == self::PAYMENT_TRANSACTION_TYPE_AUTHORIZATION) {
                    //cancel authorize
                    try {
                        $payment->cancel();
                        $this->helperData->writeToLog(__(
                            'Cancel authorization for order: %1 succesfully',
                            $order->getIncrementId()
                        ));
                        $order->save();
                    } catch (\Exception $e) {
                        $this->helperData->writeToLog(__(
                            'Cancel authorization order: %1 failed',
                            $order->getIncrementId()
                        ));
                        $this->helperData->writeToLog($e->getTraceAsString());
                    }
                }
            }
        }
    }

    /**
     * get last payment transaction
     * @param $orderId
     * @return bool|\Magento\Sales\Api\Data\TransactionInterface
     */
    private function getLastPaymentTransaction($orderId)
    {
        $sortOrder = $this->sortOrderBuilder
                          ->setField('transaction_id')
                          ->setDirection(\Magento\Framework\Api\SortOrder::SORT_DESC)
                          ->create();
        $criteria = $this->searchCriteria
                        ->addFilter('order_id', $orderId)
                        ->addFilter('is_closed', 0)
                        ->addSortOrder($sortOrder)
                        ->setPageSize(1)
                        ->setCurrentPage(1)
                        ->create();
        $transactionCollection = $this->paymentTransactionRepository->getList($criteria);
        if ($transactionCollection->getTotalCount()) {
            foreach ($transactionCollection->getItems() as $item) {
                return $item;
            }
        }
        return false;
    }
}
