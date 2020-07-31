<?php

namespace Riki\Subscription\Model\Emulator\Order;

class Invoice
    extends \Magento\Sales\Model\Order\Invoice
{
    /**
     * Initialize invoice resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Order\Invoice');
    }
}