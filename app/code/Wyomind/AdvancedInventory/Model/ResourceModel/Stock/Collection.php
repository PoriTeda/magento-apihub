<?php

namespace Wyomind\AdvancedInventory\Model\ResourceModel\Stock;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Wyomind\AdvancedInventory\Model\Stock', 'Wyomind\AdvancedInventory\Model\ResourceModel\Stock');
    }
}
