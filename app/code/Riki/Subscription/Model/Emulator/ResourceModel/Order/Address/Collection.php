<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Address;

class Collection
    extends \Magento\Sales\Model\ResourceModel\Order\Address\Collection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Order\Address', '\Riki\Subscription\Model\Emulator\ResourceModel\Order\Address');
    }
}