<?php
namespace Riki\ArReconciliation\Model\ResourceModel\OrderLog;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\ArReconciliation\Model\OrderLog','Riki\ArReconciliation\Model\ResourceModel\OrderLog');
    }
}
