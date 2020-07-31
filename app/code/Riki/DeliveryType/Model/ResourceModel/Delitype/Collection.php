<?php

namespace Riki\DeliveryType\Model\ResourceModel\Delitype;

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
        $this->_init('Riki\DeliveryType\Model\Delitype', 'Riki\DeliveryType\Model\ResourceModel\Delitype');
    }
}
