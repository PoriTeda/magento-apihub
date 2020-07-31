<?php

namespace Riki\NpAtobarai\Gateway\Response\GetPaymentStatus;

use Exception;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Riki\NpAtobarai\Model\Config\Source\TransactionPaymentStatus;
use Riki\NpAtobarai\Model\Transaction;
use Riki\NpAtobarai\Api\TransactionRepositoryInterface;
use Psr\Log\LoggerInterface;
use Riki\NpAtobarai\Model\ResourceModel\Transaction as TransactionResource;

/**
 * Class TransactionHandler
 */
class NotPayHandler implements HandlerInterface
{
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
     * NotPayHandler constructor.
     *
     * @param TransactionRepositoryInterface $transactionRepository
     * @param LoggerInterface $logger
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionResource $transactionResource
     */
    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        LoggerInterface $logger,
        ShipmentRepositoryInterface $shipmentRepository,
        OrderRepositoryInterface $orderRepository,
        TransactionResource $transactionResource
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
        $this->shipmentRepository = $shipmentRepository;
        $this->transactionResource = $transactionResource;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $successes = [];
        if (isset($response[PaidHandler::SUCCESSES])) {
            $successes = $response[PaidHandler::SUCCESSES];
        }

        foreach ($successes as $transactionData) {
            if ($transactionData['payment_status'] == TransactionPaymentStatus::NOT_PAID_YET_STATUS_VALUE) {
                $this->processNotpaidYet(
                    $handlingSubject[$transactionData['np_transaction_id']]
                );
            }
        }
    }

    /**
     * @param Transaction $npTransaction
     */
    private function processNotPaidYet($npTransaction)
    {
        try {
            $npTransaction->setNpCustomerPaymentStatus(TransactionPaymentStatus::NOT_PAID_YET_STATUS_VALUE);
            $this->transactionResource->saveAttribute(
                $npTransaction,
                'np_customer_payment_status'
            );
        } catch (Exception $e) {
            $this->logger->info(__('Can\'t update NP Transaction payment status'), [$e->getMessage()]);
        }
    }
}
