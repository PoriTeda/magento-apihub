<?php

namespace Riki\NpAtobarai\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use \Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class TransactionDataBuilder
 */
class TransactionIdDataBuilder implements BuilderInterface
{
    /**
     * @param \Riki\NpAtobarai\Api\Data\TransactionInterface[] $transactions
     * @return mixed
     * @throws LocalizedException
     */
    public function build(array $transactions)
    {
        $transaction = isset($transactions['transaction']) ? $transactions['transaction'] : '';
        
        if (!$transaction instanceof \Riki\NpAtobarai\Api\Data\TransactionInterface) {
            throw new LocalizedException(__('Transaction must be an instance of NpTransaction'));
        }

        return ['np_transaction_id' => $transaction->getNpTransactionId()];
    }
}
