<?php

namespace Riki\SubscriptionMachine\Model\ResourceModel\MachineSkus;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\SubscriptionMachine\Model\MachineSkus',
            'Riki\SubscriptionMachine\Model\ResourceModel\MachineSkus'
        );
    }
}
