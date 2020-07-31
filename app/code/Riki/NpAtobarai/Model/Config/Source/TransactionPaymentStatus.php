<?php

namespace Riki\NpAtobarai\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class TransactionPaymentStatus implements ArrayInterface
{
    const NOT_PAID_YET_STATUS_VALUE = '10';
    const PAID_STATUS_VALUE = '20';
    const SECRET_STATUS_VALUE = '30';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Not Paid yet'), 'value' => self::NOT_PAID_YET_STATUS_VALUE],
            ['label' => __('Paid'), 'value' => self::PAID_STATUS_VALUE],
            ['label' => __('Secret'), 'value' => self::SECRET_STATUS_VALUE]
        ];
    }
}
