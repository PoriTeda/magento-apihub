<?php
namespace Riki\Subscription\Model\Emulator\ResourceModel\Cart\Item;

use Riki\Subscription\Model\Emulator\Config ;

class Option extends \Magento\Quote\Model\ResourceModel\Quote\Item\Option
{

    /**
     * Main table and field initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( Config::getCartItemOptionTmpTableName(), 'option_id');
    }
}