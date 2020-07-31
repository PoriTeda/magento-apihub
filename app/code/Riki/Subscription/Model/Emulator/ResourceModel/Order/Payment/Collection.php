<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Payment;

class Collection
    extends \Magento\Sales\Model\ResourceModel\Order\Payment\Collection
{
    /**
     * Model initialization\
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Order\Payment', 'Riki\Subscription\Model\Emulator\ResourceModel\Order\Payment');
    }
}