<?php
namespace Riki\Fraud\Model\ResourceModel\RmaCedynaThreshold;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\Fraud\Model\RmaCedynaThreshold','Riki\Fraud\Model\ResourceModel\RmaCedynaThreshold');
    }
}
