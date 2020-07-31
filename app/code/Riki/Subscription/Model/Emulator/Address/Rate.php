<?php

namespace Riki\Subscription\Model\Emulator\Address;

class Rate
    extends \Magento\Quote\Model\Quote\Address\Rate
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Address\Rate');
    }
}