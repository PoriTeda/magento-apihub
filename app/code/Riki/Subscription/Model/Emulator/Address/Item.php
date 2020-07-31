<?php

namespace Riki\Subscription\Model\Emulator\Address;

class Item extends \Magento\Quote\Model\Quote\Address\Item
{

    public function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Address\Item');
    }
}