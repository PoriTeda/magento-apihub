<?php

namespace Riki\Sales\Model\ResourceModel\ShippingReason;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     * @codingStandardsIgnoreStart
     */
    protected $_idFieldName = 'id';

    /**
     * Collection initialisation
     */
    protected function _construct()
    {
        // @codingStandardsIgnoreEnd
        $this->_init('Riki\Sales\Model\ShippingReason', 'Riki\Sales\Model\ResourceModel\ShippingReason');
    }
}
