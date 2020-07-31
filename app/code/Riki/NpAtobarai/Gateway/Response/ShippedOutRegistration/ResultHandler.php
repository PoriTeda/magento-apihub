<?php
namespace Riki\NpAtobarai\Gateway\Response\ShippedOutRegistration;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;
use Riki\NpAtobarai\Model\Transaction;

/**
 * Class TransactionHandler
 */
class ResultHandler implements HandlerInterface
{
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * AcceptHandler constructor.
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        LoggerInterface $logger
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws \Exception
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (isset($response['results'])) {
            foreach ($response['results'] as $item) {
                $npTransactionId = $item['np_transaction_id'];
                /** @var Transaction $transaction */
                foreach ($handlingSubject as $transaction) {
                    if ($transaction->getNpTransactionId() == $npTransactionId) {
                        $transaction->setIsShippedOutRegistered(Transaction::REGISTERED_SHIPPED_OUT);
                        $transaction->setShippedOutRegisterErrorCodes(null);
                        try {
                            $transaction->save();
                        } catch (\Exception $e) {
                            $this->logger->critical($e);
                        }
                        break;
                    }
                }
            }
        }
    }
}
