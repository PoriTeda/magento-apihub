<?php

namespace Riki\Subscription\Model\Emulator\Order;

class Tax
    extends \Magento\Sales\Model\Order\Tax
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Order\Tax');
    }
}