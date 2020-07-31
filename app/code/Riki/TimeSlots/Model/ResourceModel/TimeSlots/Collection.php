<?php

namespace Riki\TimeSlots\Model\ResourceModel\TimeSlots;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Riki\TimeSlots\Model\TimeSlots', 'Riki\TimeSlots\Model\ResourceModel\TimeSlots');
    }
}