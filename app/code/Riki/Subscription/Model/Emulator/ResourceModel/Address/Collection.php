<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Address;

class Collection extends \Magento\Quote\Model\ResourceModel\Quote\Address\Collection
{
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Address', 'Riki\Subscription\Model\Emulator\ResourceModel\Address');
    }
    
}
