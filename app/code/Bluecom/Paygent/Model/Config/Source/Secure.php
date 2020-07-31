<?php

namespace Bluecom\Paygent\Model\Config\Source;

class Secure
{
    /**
     * 3DSecure config
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __('Yes')
            ],
            [
                'value' => 1,
                'label' => __('No')
            ]
        ];
    }
}
