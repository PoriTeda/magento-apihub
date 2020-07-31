<?php

namespace Riki\SubscriptionCourse\Model\DelayedPayment;

class RikiType implements \Magento\Framework\Option\ArrayInterface
{
    const SPOT = 'SPOT';
    const RIKI_TYPE_NULL = '';
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::RIKI_TYPE_NULL, 'label' => ' '],
            ['value' => \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT, 'label' => __(\Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT)],
            ['value' => \Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI, 'label' => __(\Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI)],
            ['value' => \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION, 'label' => __(\Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION)],
            ['value' => self::SPOT, 'label' => __(self::SPOT)]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::RIKI_TYPE_NULL => ' ',
            \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT => __(\Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT),
            \Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI => __(\Riki\Sales\Helper\Order::RIKI_TYPE_HANPUKAI),
            \Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION => __(\Riki\Sales\Helper\Order::RIKI_TYPE_SUBSCRIPTION),
            self::SPOT => __(self::SPOT),
        ];
    }
}