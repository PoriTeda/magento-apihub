<?php
namespace Riki\ArReconciliation\Model\ResourceModel\OrderReturnLog;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\ArReconciliation\Model\OrderReturnLog','Riki\ArReconciliation\Model\ResourceModel\OrderReturnLog');
    }
}
