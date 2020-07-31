<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Cart\Item;

class Collection extends \Magento\Quote\Model\ResourceModel\Quote\Item\Collection
{
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Cart\Item', 'Riki\Subscription\Model\Emulator\ResourceModel\Cart\Item');
    }

}