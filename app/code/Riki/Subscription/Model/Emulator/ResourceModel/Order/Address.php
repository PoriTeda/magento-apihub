<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order;

use Riki\Subscription\Model\Emulator\Config ;

class Address
    extends \Magento\Sales\Model\ResourceModel\Order\Address
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getOrderAddressTmpTableName(), 'entity_id');
    }
}