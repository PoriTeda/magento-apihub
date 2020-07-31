<?php

namespace Riki\NpAtobarai\Gateway\Response\GetPaymentStatus;

use Exception;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Riki\NpAtobarai\Model\Transaction;
use Riki\NpAtobarai\Api\TransactionRepositoryInterface;
use Psr\Log\LoggerInterface;
use Riki\NpAtobarai\Model\ResourceModel\Transaction as TransactionResource;

/**
 * Class TransactionHandler
 */
class ErrorHandler implements HandlerInterface
{
    const ERRORS = 'errors';

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
     * ErrorHandler constructor.
     *
     * @param TransactionRepositoryInterface $transactionRepository
     * @param LoggerInterface $logger
     * @param TransactionResource $transactionResource
     */
    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        LoggerInterface $logger,
        TransactionResource $transactionResource
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
        $this->transactionResource = $transactionResource;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $errors = [];
        if (isset($response[self::ERRORS])) {
            $errors = $response[self::ERRORS];
        }

        foreach ($errors as $errorData) {
            $this->processError(
                $handlingSubject[$errorData['id']],
                $errorData
            );
        }
    }

    /**
     * @param Transaction $npTransaction
     * @param array $errorData
     */
    public function processError($npTransaction, $errorData)
    {
        try {
            $npTransaction->setRegisterErrorCodes(implode(',', $errorData['codes']));
            $this->transactionResource->saveAttribute($npTransaction, 'register_error_codes');
        } catch (Exception $e) {
            $this->logger->info(__('Can\'t update NP Transaction payment status'), [$e->getMessage()]);
        }
    }
}
