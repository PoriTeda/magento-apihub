<?php
namespace Riki\Rma\Model\ResourceModel\Reason;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'Riki\Rma\Model\Reason',
            'Riki\Rma\Model\ResourceModel\Reason'
        );
    }

    /**
     * filter reason by deleted field
     *
     * @return $this
     */
    public function addActiveFilter()
    {
        $this->addFieldToFilter('deleted', 0);
        return $this;
    }



    public function toOptionArray($valueField = null, $labelField = 'name', $additional = [])
    {
       return $this->_toOptionArray($valueField, $labelField, $additional);
    }

    /**
     * get reason by code
     *
     * @param $reasonCode
     * @return \Magento\Framework\DataObject
     */
    public function getReasonByCode($reasonCode){
        return $this->addFieldToFilter('code', $reasonCode)
            ->setPageSize(1)
            ->getFirstItem();
    }
}
