<?php

namespace Riki\Subscription\Model\Emulator\Order;

class Item
    extends \Magento\Sales\Model\Order\Item
{
    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Order\Item');
    }
}