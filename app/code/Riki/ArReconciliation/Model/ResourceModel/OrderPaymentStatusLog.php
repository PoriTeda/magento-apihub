<?php
namespace Riki\ArReconciliation\Model\ResourceModel;
class OrderPaymentStatusLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $connectionName = 'sales';

    protected function _construct()
    {
        $this->_init('riki_order_payment_status_log','id');
    }
}
