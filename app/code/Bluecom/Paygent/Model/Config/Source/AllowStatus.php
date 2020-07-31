<?php

namespace Bluecom\Paygent\Model\Config\Source;

class AllowStatus
{
    /**
     * Status list allowed for cron cancel
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '',
                'label' => __('-- Please Select --')
            ],
            [
                'value' => 'pending_payment',
                'label' => __('PENDING CC')
            ]
        ];
    }
}