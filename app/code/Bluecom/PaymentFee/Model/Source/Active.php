<?php

namespace Bluecom\PaymentFee\Model\Source;

class Active implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = \Bluecom\PaymentFee\Model\PaymentFee::getActive();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}