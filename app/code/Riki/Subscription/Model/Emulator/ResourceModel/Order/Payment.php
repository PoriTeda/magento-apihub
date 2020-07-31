<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order;

use Riki\Subscription\Model\Emulator\Config ;

class Payment
    extends \Magento\Sales\Model\ResourceModel\Order\Payment
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getOrderPaymentTmpTableName(), 'entity_id');
    }
}