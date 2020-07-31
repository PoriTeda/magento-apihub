<?php

namespace Riki\Sales\Model\ResourceModel\OrderColor;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Riki\Sales\Model\OrderColor', 'Riki\Sales\Model\ResourceModel\OrderColor');
    }
}
