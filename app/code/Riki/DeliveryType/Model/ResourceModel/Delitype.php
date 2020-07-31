<?php

namespace Riki\DeliveryType\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Delitype extends AbstractDb
{
    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = "sales"
    )
    {
        parent::__construct($context,$connectionName);
    }
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('riki_delivery_type', 'id');
    }

}

