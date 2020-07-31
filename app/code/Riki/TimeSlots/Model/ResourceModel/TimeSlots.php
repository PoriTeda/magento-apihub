<?php

namespace Riki\TimeSlots\Model\ResourceModel;

class TimeSlots extends  \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_timeslots', 'id');
    }
}