<?php
namespace Riki\Fraud\Model;
class SuspectedFraud extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Riki\Fraud\Model\ResourceModel\SuspectedFraud');
    }
}
