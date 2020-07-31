<?php

namespace Wyomind\AdvancedInventory\Model\ResourceModel\Journal;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Wyomind\AdvancedInventory\Model\Journal', 'Wyomind\AdvancedInventory\Model\ResourceModel\Journal');
    }
    
    public function getColumn($column)
    {
        $this->getSelect()->distinct(true)->group($column);
        return $this;
    }
}
