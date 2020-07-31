<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Response\SilentOrder;

use Magento\Cybersource\Gateway\Response;

class TransactionIdHandler extends Response\TransactionIdHandler
{
    /**
     * Transaction Id key
     */
    const TRANSACTION_ID = 'transaction_id';

    /**
     * Returns field name containing transaction id
     *
     * @return string
     */
    protected function getTransactionIdField()
    {
        return self::TRANSACTION_ID;
    }
}
