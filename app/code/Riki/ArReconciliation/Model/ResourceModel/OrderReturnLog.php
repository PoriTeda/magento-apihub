<?php
namespace Riki\ArReconciliation\Model\ResourceModel;
class OrderReturnLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $connectionName = 'sales';

    protected function _construct()
    {
        $this->_init('riki_order_refund_log','id');
    }
}
