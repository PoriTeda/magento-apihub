<?php
namespace Riki\Subscription\Model\Emulator\ResourceModel\Cart;

use Riki\Subscription\Model\Emulator\Config ;

class Item extends \Magento\Quote\Model\ResourceModel\Quote\Address\Item
{
    protected function _construct()
    {
        $this->_init( Config::getCartItemTmpTableName() , 'item_id');
    }

}