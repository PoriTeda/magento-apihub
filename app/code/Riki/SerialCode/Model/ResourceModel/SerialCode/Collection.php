<?php

namespace Riki\SerialCode\Model\ResourceModel\SerialCode;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    /**
     * Initialize resource model for collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Riki\SerialCode\Model\SerialCode', 'Riki\SerialCode\Model\ResourceModel\SerialCode');
    }
}
