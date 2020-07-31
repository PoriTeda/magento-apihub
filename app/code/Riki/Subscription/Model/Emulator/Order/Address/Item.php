<?php
namespace Riki\Subscription\Model\Emulator\Order\Address;
class Item extends \Riki\Checkout\Model\Order\Address\Item
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Order\Address\Item');
    }
}