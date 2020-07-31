<?php
namespace Riki\Fraud\Model\ResourceModel\SuspectedFraud;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riki\Fraud\Model\SuspectedFraud','Riki\Fraud\Model\ResourceModel\SuspectedFraud');
    }
}
