<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment;

use Riki\Subscription\Model\Emulator\Config ;

class Item
    extends \Magento\Sales\Model\ResourceModel\Order\Shipment\Item
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getShipmentItemTmpTableName(), 'entity_id');
    }
}