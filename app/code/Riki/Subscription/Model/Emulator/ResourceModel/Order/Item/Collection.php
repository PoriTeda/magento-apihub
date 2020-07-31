<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Item ;

class Collection
    extends \Magento\Sales\Model\ResourceModel\Order\Item\Collection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Order\Item', '\Riki\Subscription\Model\Emulator\ResourceModel\Order\Item');
    }

}