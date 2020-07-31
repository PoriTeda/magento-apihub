<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Response\Soap;

use Magento\Cybersource\Gateway\Response;

class RequestIdHandler extends Response\TransactionIdHandler
{
    /**
     * Request id key
     */
    const REQUEST_ID = 'requestID';

    /**
     * Returns field name containing transaction id
     *
     * @return string
     */
    protected function getTransactionIdField()
    {
        return self::REQUEST_ID;
    }
}
