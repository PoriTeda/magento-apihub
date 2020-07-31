<?php

namespace Bluecom\Paygent\Model\Config\Source;

class Paymenttype
{
    /**
     * Payment type option for paygent
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __('One Time')
            ],
            [
                'value' => 1,
                'label' => __('Use Bonus')
            ],
            [
                'value' => 2,
                'label' => __('Except Bonus')
            ]
        ];
    }
}
