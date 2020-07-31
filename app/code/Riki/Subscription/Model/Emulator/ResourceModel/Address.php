<?php
namespace Riki\Subscription\Model\Emulator\ResourceModel;

use Riki\Subscription\Model\Emulator\Config ;

class Address extends \Magento\Quote\Model\ResourceModel\Quote\Address
{
    /**
     * Initialize table nad PK name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( Config::getAddressTmpTableName() , 'address_id');
    }



}