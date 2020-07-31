<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order;

use Riki\Subscription\Model\Emulator\Config ;

class Item
    extends \Magento\Sales\Model\ResourceModel\Order\Item
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getOrderItemTmpTableName(), 'item_id');
    }
}