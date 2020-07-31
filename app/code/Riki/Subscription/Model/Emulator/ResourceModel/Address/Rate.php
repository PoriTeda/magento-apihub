<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Address;

use Riki\Subscription\Model\Emulator\Config ;

class Rate
    extends \Magento\Quote\Model\ResourceModel\Quote\Address\Rate
{
    /**
     * Main table and field initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getCartShippingRateTmpTableName(), 'rate_id');
    }
}