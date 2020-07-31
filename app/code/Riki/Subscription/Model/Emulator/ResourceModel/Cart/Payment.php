<?php
namespace Riki\Subscription\Model\Emulator\ResourceModel\Cart;

use Riki\Subscription\Model\Emulator\Config ;

class Payment
    extends \Magento\Quote\Model\ResourceModel\Quote\Payment
{
    /**
     * Main table and field initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getCartPaymentTmpTableName(), 'payment_id');
    }
}