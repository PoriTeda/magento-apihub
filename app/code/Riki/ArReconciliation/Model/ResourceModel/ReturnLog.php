<?php
namespace Riki\ArReconciliation\Model\ResourceModel;
class ReturnLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_rma_refund_log','id');
    }
}
