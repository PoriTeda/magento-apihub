<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Helper;

class SilentOrderHelper
{
    /**
     * Signs fields
     *
     * @param array $fieldsToSign
     * @param string $key
     * @return string
     */
    public static function signFields(array $fieldsToSign, $key)
    {
        array_walk(
            $fieldsToSign,
            function (&$value, $key) {
                $value = sprintf('%s=%s', $key, (string)$value);
            }
        );

        return base64_encode(
            hash_hmac(
                'sha256',
                implode(',', $fieldsToSign),
                $key,
                true
            )
        );
    }
}
