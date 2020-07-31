<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Helper;

/**
 * Class SubjectReader
 */
class SubjectReader
{
    /**
     * Reads access code from subject
     *
     * @param array $subject
     * @return string
     */
    public static function readAccessCode(array $subject)
    {
        if (empty($subject['access_code'])) {
            throw new \InvalidArgumentException('Access code should be provided.');
        }

        return $subject['access_code'];
    }

    /**
     * Read transaction id from subject
     *
     * @param array $subject
     * @return string
     */
    public static function readTransactionId(array $subject)
    {
        if (!isset($subject['request']['transaction_id'])
            || !is_string($subject['request']['transaction_id'])
        ) {
            throw new \InvalidArgumentException('Transaction id does not exist');
        }

        return $subject['request']['transaction_id'];
    }
}
