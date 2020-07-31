<?php
namespace Riki\NpAtobarai\Gateway\Response\Cancel;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Riki\NpAtobarai\Api\Data\TransactionInterface;
use Riki\NpAtobarai\Model\Config\Source\TransactionStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;

/**
 * Class TransactionHandler
 */
class SuccessHandler implements HandlerInterface
{
    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->dbTransaction = $dbTransaction;
        $this->shipmentRepository = $shipmentRepository;
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (isset($response['results'])) {
            foreach ($response['results'] as $result) {
                foreach ($handlingSubject as $index => $transaction) {
                    if ($transaction instanceof TransactionInterface
                        && $transaction->getNpTransactionId() == $result['np_transaction_id']
                    ) {
                        try {
                            $shipment = $transaction->getShipment();
                        } catch (\Exception $e) {
                            $this->logger->info($e->getMessage());
                            $shipment = false;
                        }

                        try {
                            $this->dbTransaction->beginTransaction();
                            if ($shipment) {
                                $this->addDataShipment($shipment, [
                                    'payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE
                                ]);
                            }

                            $this->addDataTransaction($transaction, [
                                'np_transaction_status' => TransactionStatus::CANCELLED_STATUS_VALUE
                            ]);
                            $this->dbTransaction->commit();
                        } catch (\Exception $ex) {
                            $this->dbTransaction->rollback();
                            $this->logger->info($ex->getMessage());
                        }
                        unset($handlingSubject[$index]);
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param TransactionInterface $transaction
     * @param array $data
     */
    protected function addDataTransaction(TransactionInterface $transaction, array $data)
    {
        $transaction->addData($data);
        $this->transactionRepository->save($transaction);
    }

    /**
     * @param ShipmentInterface $shipment
     * @param array $data
     */
    protected function addDataShipment(ShipmentInterface $shipment, array $data)
    {
        $shipment->addData($data);
        $this->shipmentRepository->save($shipment);
    }
}
