<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\Item;

class Collection
    extends \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\Collection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Subscription\Model\Emulator\Order\Shipment\Item',
            'Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\Item'
        );
    }
}