<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Invoice;

class Collection
    extends \Magento\Sales\Model\ResourceModel\Order\Invoice\Collection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Order\Invoice', 'Riki\Subscription\Model\Emulator\ResourceModel\Order\Invoice');
    }
}