<?php
namespace Riki\NpAtobarai\Gateway\Response\Cancel;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Riki\NpAtobarai\Api\Data\TransactionInterface;

/**
 * Class TransactionHandler
 */
class ErrorHandler implements HandlerInterface
{
    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @param \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        \Riki\NpAtobarai\Api\TransactionRepositoryInterface $transactionRepository
    ) {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (isset($response['errors'])) {
            foreach ($response['errors'] as $error) {
                foreach ($handlingSubject as $index => $transaction) {
                    if ($transaction instanceof TransactionInterface &&
                        $transaction->getNpTransactionId() == $error['id']) {
                        $this->addDataTransaction($transaction, [
                            'cancel_error_codes' => implode(',', $error['codes'])
                        ]);
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
}
