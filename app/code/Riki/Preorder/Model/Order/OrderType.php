<?php

namespace Riki\Preorder\Model\Order;

class OrderType implements \Magento\Framework\Option\ArrayInterface
{
    const NORMALORDER = 0;
    const PREORDER = 1;
    const BACKNORMAL = 2;
    const NORMALORDER_LABEL = 'Normal';
    const PREORDER_LABEL = 'Pre-order';
    const BACKNORMAL_LABEL = 'Back Normal';
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __(self::NORMALORDER_LABEL)],
            ['value' => 1, 'label' => __(self::PREORDER_LABEL)],
            ['value' => 2, 'label' => __(self::BACKNORMAL_LABEL)]
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
            0 => __(self::NORMALORDER_LABEL),
            1 => __(self::PREORDER_LABEL),
            2 => __(self::BACKNORMAL_LABEL)
        ];
    }
}
