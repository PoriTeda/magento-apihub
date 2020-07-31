<?php
namespace Riki\Subscription\Model\Emulator\ResourceModel\Address;

use Riki\Subscription\Model\Emulator\Config ;

class Item extends \Magento\Quote\Model\ResourceModel\Quote\Address\Item
{
    protected function _construct()
    {
        $this->_init( Config::getAddressItemTmpTableName() , 'address_item_id');
    }

}