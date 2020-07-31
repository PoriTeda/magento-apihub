<?php

namespace Riki\SubscriptionMachine\Model\ResourceModel;

class MachineCustomer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * MachineCustomer constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_machine_customer', 'id');
    }
}
