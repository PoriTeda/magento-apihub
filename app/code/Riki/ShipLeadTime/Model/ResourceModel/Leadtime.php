<?php

namespace Riki\ShipLeadTime\Model\ResourceModel;

class Leadtime extends  \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected function _construct()
    {
        $this->_init('riki_shipleadtime', 'id');
    }

    /**
     * @param array ids
     *
     * @return self
     */
    public function enableByIds(array $ids){
        $this->getConnection()->update(
            $this->getMainTable(),
            ['is_active'    =>  1],
            [
                'id IN (?)' => $ids
            ]
        );

        return $this;
    }

    /**
     * @param array ids
     *
     * @return self
     */
    public function disableByIds(array $ids){
        $this->getConnection()->update(
            $this->getMainTable(),
            ['is_active'    =>  0],
            [
                'id IN (?)' => $ids
            ]
        );

        return $this;
    }
}