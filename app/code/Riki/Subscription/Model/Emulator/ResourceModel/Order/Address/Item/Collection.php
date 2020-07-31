<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Address\Item;

class Collection
    extends \Riki\Checkout\Model\ResourceModel\Order\Address\Item\Collection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Subscription\Model\Emulator\Order\Address\Item',
            'Riki\Subscription\Model\Emulator\ResourceModel\Order\Address\Item'
        );
    }
}