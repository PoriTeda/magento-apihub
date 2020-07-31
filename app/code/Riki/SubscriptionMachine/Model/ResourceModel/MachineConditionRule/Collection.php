<?php

namespace Riki\SubscriptionMachine\Model\ResourceModel\MachineConditionRule;

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
            'Riki\SubscriptionMachine\Model\MachineConditionRule',
            'Riki\SubscriptionMachine\Model\ResourceModel\MachineConditionRule'
        );
    }
}
