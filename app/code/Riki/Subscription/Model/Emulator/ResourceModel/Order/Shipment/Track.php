<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment;

use Riki\Subscription\Model\Emulator\Config ;

class Track
    extends \Magento\Sales\Model\ResourceModel\Order\Shipment\Track
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getShipmentTrackTmpTableName(), 'entity_id');
    }
}