<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment;

class Collection
    extends \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('\Riki\Subscription\Model\Emulator\Order\Shipment', 'Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment');
    }
}