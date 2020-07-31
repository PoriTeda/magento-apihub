<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Address\Item;

class Collection extends \Magento\Quote\Model\ResourceModel\Quote\Address\Item\Collection
{
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Address\Item', 'Riki\Subscription\Model\Emulator\ResourceModel\Address\Item');
    }

}
