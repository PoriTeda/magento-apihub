<?php
namespace Riki\Subscription\Model\Emulator\Cart\Item;

class Option
    extends \Magento\Quote\Model\Quote\Item\Option
{
    public function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Cart\Item');
    }
}