<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Address;

use Riki\Subscription\Model\Emulator\Config ;

class Item
    extends \Riki\Checkout\Model\ResourceModel\Order\Address\Item
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getOrderAddressItemTmpTableName(), 'entity_id');
    }
}