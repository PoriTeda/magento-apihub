<?php
namespace Riki\NpAtobarai\Gateway\Response\ShippedOutRegistration;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;
use Riki\NpAtobarai\Model\Transaction;

/**
 * Class TransactionHandler
 */
class ErrorsHandler implements HandlerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ErrorHandler constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (isset($response['errors']) && !empty($response['errors'])) {
            foreach ($response['errors'] as $itemError) {
                $npTransactionId = $itemError['id'];
                $code = implode(',', $itemError['codes']);
                /** @var Transaction $transaction */
                foreach ($handlingSubject as $transaction) {
                    if ($transaction->getNpTransactionId() == $npTransactionId) {
                        $transaction->setShippedOutRegisterErrorCodes($code);
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
