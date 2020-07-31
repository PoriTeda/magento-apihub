<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order;

use Riki\Subscription\Model\Emulator\Config ;

class Tax
    extends \Magento\Sales\Model\ResourceModel\Order\Tax
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getOrderTaxTmpTableName(), 'tax_id');
    }
}