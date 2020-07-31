<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Tax;

class Collection
    extends \Magento\Sales\Model\ResourceModel\Order\Tax\Collection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Order\Tax', 'Riki\Subscription\Model\Emulator\ResourceModel\Order\Tax');
    }

}