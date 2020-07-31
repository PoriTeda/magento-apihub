<?php
namespace Riki\ArReconciliation\Model\ResourceModel\ShipmentLog;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            'Riki\ArReconciliation\Model\ShipmentLog',
            'Riki\ArReconciliation\Model\ResourceModel\ShipmentLog'
        );
        $this->_map['fields']['id'] = 'main_table.id';
    }
}
