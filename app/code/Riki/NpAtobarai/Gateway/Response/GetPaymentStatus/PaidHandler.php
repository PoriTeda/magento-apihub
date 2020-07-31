<?php

namespace Riki\NpAtobarai\Gateway\Response\GetPaymentStatus;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus;
use Riki\NpAtobarai\Model\ResourceModel\Transaction as TransactionResource;
use Riki\NpAtobarai\Model\Config\Source\TransactionPaymentStatus;
use Riki\NpAtobarai\Model\Transaction;
use Riki\NpAtobarai\Api\TransactionRepositoryInterface;
use Psr\Log\LoggerInterface;
use Riki\Framework\Helper\Transaction\Database;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment;

/**
 * Class TransactionHandler
 */
class PaidHandler implements HandlerInterface
{
    const SUCCESSES = 'results';

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var TransactionResource
     */
    protected $transactionResource;

    /**
     * @var Database
     */
    protected $dbTransaction;

    /**
     * @var \Riki\ShipmentImporter\Helper\Data
     */
    private $orderHelper;

    /**
     * PaidHandler constructor.
     *
     * @param TransactionRepositoryInterface $transactionRepository
     * @param LoggerInterface $logger
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionResource $transactionResource
     * @param Database $dbTransaction
     * @param \Riki\ShipmentImporter\Helper\Data $orderHelper
     */
    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        LoggerInterface $logger,
        ShipmentRepositoryInterface $shipmentRepository,
        OrderRepositoryInterface $orderRepository,
        TransactionResource $transactionResource,
        Database $dbTransaction,
        \Riki\ShipmentImporter\Helper\Data $orderHelper
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
        $this->shipmentRepository = $shipmentRepository;
        $this->orderRepository = $orderRepository;
        $this->transactionResource = $transactionResource;
        $this->dbTransaction = $dbTransaction;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $successes = [];
        if (isset($response[self::SUCCESSES])) {
            $successes = $response[self::SUCCESSES];
        }
        $processData = [];
        foreach ($successes as $transactionData) {
            $npTransaction = $handlingSubject[$transactionData['np_transaction_id']];
            $orderId = $npTransaction->getOrderId();
            if ($transactionData['payment_status'] == TransactionPaymentStatus::PAID_STATUS_VALUE) {
                $processData[$orderId][] = [
                    'transactionData' => $transactionData,
                    'npTransaction' => $npTransaction
                ];
            }
        }
        foreach ($processData as $orderId => $orderTransactions) {
            try {
                $this->dbTransaction->beginTransaction();
                foreach ($orderTransactions as $transactionData) {
                    $npTransaction = $transactionData['npTransaction'];
                    $data = $transactionData['transactionData'];
                    $this->processPaid(
                        $npTransaction,
                        $data
                    );
                }
                $this->processOrder($orderId);
                $this->dbTransaction->commit();
            } catch (Exception $e) {
                $this->logger->info(
                    __('Currently getting this error when trying to update the Transaction.'),
                    [$e->getMessage()]
                );
                $this->dbTransaction->rollback();
            }
        }
    }

    /**
     * @param Transaction $npTransaction
     * @param array $transactionData
     *
     * @throws LocalizedException
     */
    private function processPaid($npTransaction, $transactionData)
    {
        try {
            /** @var Shipment $shipment */
            $shipment = $this->shipmentRepository->get($npTransaction->getShipmentId());
            $shipment->setPaymentStatus(Payment::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
            $shipment->setCollectionDate($transactionData['customer_payment_date']);
            $this->shipmentRepository->save($shipment);

            $npTransaction->setNpCustomerPaymentStatus(TransactionPaymentStatus::PAID_STATUS_VALUE);
            $npTransaction->setNpCustomerPaymentDate($transactionData['customer_payment_date']);
            $this->transactionResource->saveAttribute(
                $npTransaction,
                ['np_customer_payment_status', 'np_customer_payment_date']
            );
        } catch (Exception $e) {
            throw new LocalizedException(__('Can\'t update NP Transaction payment status: %1', $e->getMessage()));
        }
    }

    /**
     * @param string $orderId
     *
     * @throws LocalizedException
     */
    private function processOrder($orderId)
    {
        try {
            $orderTransactions = $this->transactionRepository
                ->getListByOrderId($orderId);
            $countPaid = 0;
            foreach ($orderTransactions->getItems() as $item) {
                if ($item->getNpCustomerPaymentStatus() == TransactionPaymentStatus::PAID_STATUS_VALUE) {
                    $countPaid++;
                }
            }
            if ($countPaid == $orderTransactions->getTotalCount()) {
                /** @var Order $order */
                $order = $this->orderRepository->get($orderId);
                $order->setPaymentStatus(PaymentStatus::PAYMENT_COLLECTED);
                $this->orderRepository->save($order);
            }
        } catch (Exception $e) {
            throw new LocalizedException(__('Error happen when process Order(%1): %2', [$orderId, $e->getMessage()]));
        }
    }
}
