<?php

namespace Riki\SubscriptionMachine\Exception;

class InputException extends \Magento\Framework\Exception\InputException
{
    const ERROR_TYPE_REQUIRED = 'required';
    const ERROR_TYPE_INVALID_VALUE = 'invalid_value';
    const ERROR_TYPE_MUST_EXIST = 'must_exist';
    const ERROR_TYPE_MIN_VALUE = 'min_value';
    const ERROR_TYPE_MAX_VALUE = 'max_value';
    const ERROR_TYPE_MAX_LENGTH = 'max_length';

    const ERROR_CODE_SYSTEM = 2006;
    const ERROR_CODE_NOT_FOUND_ANY_ORDER = 2007;

    /**
     * @param string $code
     */
    public function setErrorCode($code)
    {
        $this->code = $code;
    }
}
