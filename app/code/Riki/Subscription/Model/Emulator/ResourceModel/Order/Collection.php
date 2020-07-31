<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order;

class Collection
    extends \Magento\Sales\Model\ResourceModel\Order\Collection {


    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Order', 'Riki\Subscription\Model\Emulator\ResourceModel\Order');
    }
}