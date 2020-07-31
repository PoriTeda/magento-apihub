<?php
namespace Riki\ArReconciliation\Model\ResourceModel\OrderPaymentStatusLog;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\ArReconciliation\Model\OrderPaymentStatusLog','Riki\ArReconciliation\Model\ResourceModel\OrderPaymentStatusLog');
    }
}
