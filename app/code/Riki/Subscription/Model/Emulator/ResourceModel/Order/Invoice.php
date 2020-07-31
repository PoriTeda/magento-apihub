<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order;

use Riki\Subscription\Model\Emulator\Config ;

class Invoice
    extends \Magento\Sales\Model\ResourceModel\Order\Invoice
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getInvoiceTmpTableName(), 'entity_id');
    }
}