<?php

namespace Riki\SapIntegration\Model\ResourceModel;

class ShipmentSapExported extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $_isPkAutoIncrement = false;

    /**
     * Prefix for resources that will be used in this resource model
     *
     * @var string
     */
    protected $connectionName = 'sales';


    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('riki_shipment_sap_exported', 'shipment_entity_id');
    }

    /**
     * Check if object is new
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return bool
     */
    public function isObjectNotNew(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->isObjectNew()) {
            return false;
        }

        return parent::isObjectNotNew($object);
    }
}
