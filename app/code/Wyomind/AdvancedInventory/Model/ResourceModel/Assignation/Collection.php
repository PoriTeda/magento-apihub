<?php

namespace Wyomind\AdvancedInventory\Model\ResourceModel\Assignation;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected function _construct()
    {
        $this->_init('Wyomind\AdvancedInventory\Model\Assignation', 'Wyomind\AdvancedInventory\Model\ResourceModel\Assignation');
    }
}
