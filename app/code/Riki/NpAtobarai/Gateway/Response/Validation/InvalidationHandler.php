<?php
namespace Riki\NpAtobarai\Gateway\Response\Validation;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Riki\NpAtobarai\Model\Config\Source\TransactionStatus;
use Riki\NpAtobarai\Api\Data\TransactionInterface;

/**
 * Class TransactionHandler
 */
class InvalidationHandler implements HandlerInterface
{
    const INVALIDATION_STATUS = [
        TransactionStatus::BEFORE_VALIDATION_STATUS_VALUE,
        TransactionStatus::IN_VALIDATION_STATUS_VALUE
    ];

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (isset($response['results']) && !empty($response['results'])) {
            foreach ($response['results'] as $item) {
                $statusId = $item['authori_result'];
                $npTransactionId = $item['np_transaction_id'];
                if (in_array($statusId, self::INVALIDATION_STATUS)) {
                    /** @var TransactionInterface $transaction */
                    foreach ($handlingSubject as $transaction) {
                        if ($transaction->getNpTransactionId() == $npTransactionId) {
                            $transaction->setNpTransactionStatus($statusId);
                            $transaction->setAuthorizeErrorCodes(null);
                            $transaction->save();
                        }
                    }
                }
            }
        }
    }
}
