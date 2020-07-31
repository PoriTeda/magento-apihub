<?php
namespace Riki\NpAtobarai\Gateway\Response\Validation;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class TransactionHandler
 */
class ErrorHandler implements HandlerInterface
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
                try {
                    $npTransactionId = $itemError['id'];
                    $code = implode(',', $itemError['codes']);
                    foreach ($handlingSubject as $transaction) {
                        if ($transaction->getNpTransactionId() == $npTransactionId) {
                            $transaction->setAuthorizeErrorCodes($code);
                            $transaction->save();
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }
    }
}
