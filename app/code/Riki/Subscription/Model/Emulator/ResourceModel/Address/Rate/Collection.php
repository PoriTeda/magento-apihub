<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Address\Rate;

class Collection
    extends \Magento\Quote\Model\ResourceModel\Quote\Address\Rate\Collection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Address\Rate', 'Riki\Subscription\Model\Emulator\ResourceModel\Address\Rate');
    }

}