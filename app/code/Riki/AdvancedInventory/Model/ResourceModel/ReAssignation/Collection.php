<?php
namespace Riki\AdvancedInventory\Model\ResourceModel\ReAssignation;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'Riki\AdvancedInventory\Model\ReAssignation',
            'Riki\AdvancedInventory\Model\ResourceModel\ReAssignation'
        );
    }
}
