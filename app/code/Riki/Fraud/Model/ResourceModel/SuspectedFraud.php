<?php
namespace Riki\Fraud\Model\ResourceModel;
class SuspectedFraud extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_suspected_fraud_order','id');
    }
}
