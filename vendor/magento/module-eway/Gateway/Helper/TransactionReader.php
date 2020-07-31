<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Helper;

use Magento\Eway\Gateway\Request\TransactionIdDataBuilder;
use Magento\Eway\Gateway\Validator\AbstractResponseValidator;

/**
 * Class TransactionReader
 */
class TransactionReader
{
    /**
     * Read access code from transaction data
     *
     * @param array $transactionData
     * @return string
     */
    public static function readAccessCode(array $transactionData)
    {
        if (empty($transactionData[AbstractResponseValidator::ACCESS_CODE])) {
            throw new \InvalidArgumentException('Access code should be provided');
        }

        return $transactionData[AbstractResponseValidator::ACCESS_CODE];
    }

    /**
     * Read transaction id from transaction data
     *
     * @param array $transactionData
     * @return string
     */
    public static function readTransactionId(array $transactionData)
    {
        if (!isset($transactionData[TransactionIdDataBuilder::TRANSACTION_ID])) {
            throw new \InvalidArgumentException('Transaction id should be provided');
        }

        return $transactionData[TransactionIdDataBuilder::TRANSACTION_ID];
    }
}
