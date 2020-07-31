<?php
namespace Riki\ArReconciliation\Model\ResourceModel\ReturnLog;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\ArReconciliation\Model\ReturnLog','Riki\ArReconciliation\Model\ResourceModel\ReturnLog');
    }
}
