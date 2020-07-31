<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Status;

use Riki\Subscription\Model\Emulator\Config ;

class History
    extends \Magento\Sales\Model\ResourceModel\Order\Status\History
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Config::getOrderStatusHistoryTmpTableName(), 'entity_id');
    }
}