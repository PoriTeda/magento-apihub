<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Cart\Payment;

class Collection
    extends \Magento\Quote\Model\ResourceModel\Quote\Payment\Collection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Cart\Payment', 'Riki\Subscription\Model\Emulator\ResourceModel\Cart\Payment');
    }
}