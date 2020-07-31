<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\Track;

class Collection
    extends \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Subscription\Model\Emulator\Order\Shipment\Track',
            'Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\Track'
        );
    }
}