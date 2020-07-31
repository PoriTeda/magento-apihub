<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Status\History;

class Collection
    extends \Magento\Sales\Model\ResourceModel\Order\Status\History\Collection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Subscription\Model\Emulator\Order\Status\History',
            'Riki\Subscription\Model\Emulator\ResourceModel\Order\Status\History'
        );
    }
}