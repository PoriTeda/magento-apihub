<?php

namespace Riki\NpAtobarai\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class TransactionStatus implements ArrayInterface
{
    const OK_STATUS_VALUE = '00';
    const PENDING_STATUS_VALUE = '10';
    const NG_STATUS_VALUE = '20';
    const ER_STATUS_VALUE = '30';
    const BEFORE_VALIDATION_STATUS_VALUE = '40';
    const IN_VALIDATION_STATUS_VALUE = '50';
    const CANCELLED_STATUS_VALUE = '99';
    const NOT_REGISTERED_STATUS_VALUE = '';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('OK'), 'value' => self::OK_STATUS_VALUE],
            ['label' => __('PENDING'), 'value' => self::PENDING_STATUS_VALUE],
            ['label' => __('NG'), 'value' => self::NG_STATUS_VALUE],
            ['label' => __('ER'), 'value' => self::ER_STATUS_VALUE],
            ['label' => __('Before validation'), 'value' => self::BEFORE_VALIDATION_STATUS_VALUE],
            ['label' => __('In validation'), 'value' => self::IN_VALIDATION_STATUS_VALUE],
            ['label' => __('Cancelled'), 'value' => self::CANCELLED_STATUS_VALUE],
            ['label' => __('Not registered yet'), 'value' => self::NOT_REGISTERED_STATUS_VALUE],
        ];
    }
}
