<?php
namespace Riki\Subscription\Model\Emulator\ResourceModel\Cart\Item\Option;

class Collection
    extends \Magento\Quote\Model\ResourceModel\Quote\Item\Option\Collection
{
    /**
     * Define resource model for collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Cart\Item\Option', 'Riki\Subscription\Model\Emulator\ResourceModel\Cart\Item\Option');
    }
}