<?php
namespace Riki\ArReconciliation\Model\ResourceModel;
class ShipmentLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_shipment_log','id');
    }
}
