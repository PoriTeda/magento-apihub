<?php

namespace Riki\Subscription\Model\Emulator\Order;

class Address
    extends \Magento\Sales\Model\Order\Address
{
    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Order\Address');
    }
}