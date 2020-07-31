<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel;

use Riki\Subscription\Model\Emulator\Config ;

class Order
    extends \Magento\Sales\Model\ResourceModel\Order
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getOrderTmpTableName(), 'entity_id');
    }
}