<?php
namespace Riki\Fraud\Model\ResourceModel\OrderCedynaThreshold;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\Fraud\Model\OrderCedynaThreshold','Riki\Fraud\Model\ResourceModel\OrderCedynaThreshold');
    }
}
