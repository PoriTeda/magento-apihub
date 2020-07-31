<?php

namespace Riki\Subscription\Model\Emulator\Order\Status;

class History
    extends \Magento\Sales\Model\Order\Status\History
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Order\Status\History');
    }

}