<?php
namespace Riki\Fraud\Model\ResourceModel;
class OrderCedynaThreshold extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_order_cedyna_threshold','id');
    }
}
