<?php

namespace Riki\ShipLeadTime\Model\ResourceModel\Leadtime;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Riki\ShipLeadTime\Model\Leadtime', 'Riki\ShipLeadTime\Model\ResourceModel\Leadtime');
    }

    /**
     * @param $warehouseId
     * @return $this
     */
    public function addWarehouseIdToFilter($warehouseId)
    {
        $this->getSelect()->join(
            ['pos' => $this->getTable('pointofsale')],
            'pos.store_code = main_table.warehouse_id',
            ['place_id']
        )->where('pos.place_id=?', $warehouseId);

        return $this;
    }

    /**
     * @return $this
     */
    public function addActiveToFilter()
    {
        return $this->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('priority', ['notnull'   =>  true])
            ->addFieldToFilter('priority', ['neq'   =>  0]);
    }
}