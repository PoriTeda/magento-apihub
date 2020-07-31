<?php

namespace Riki\SapIntegration\Model;

use Riki\SapIntegration\Api\Data\ShipmentSapExportedInterface;

class ShipmentSapExported extends \Magento\Framework\Model\AbstractModel implements ShipmentSapExportedInterface
{
    protected $_idFieldName = 'shipment_entity_id';

    /**
     * Model construct that should be used for object initialization
     */
    protected function _construct()
    {
        $this->_init(\Riki\SapIntegration\Model\ResourceModel\ShipmentSapExported::class);
    }

    /**
     * set shipment entity id
     *
     * @param int $shipmentEntityId
     * @return $this
     */
    public function setShipmentEntityId($shipmentEntityId)
    {
        return $this->setData(self::SHIPMENT_ENTITY_ID, $shipmentEntityId);
    }

    /**
     * get shipment entity id
     *
     * @return int|null
     */
    public function getShipmentEntityId()
    {
        return $this->getData(self::SHIPMENT_ENTITY_ID);
    }

    /**
     * set shipment increment id
     *
     * @param string $shipmentIncrementId
     * @return $this
     */
    public function setShipmentIncrementId($shipmentIncrementId)
    {
        return $this->setData(self::SHIPMENT_INCREMENT_ID, $shipmentIncrementId);
    }

    /**
     * get shipment increment id
     *
     * @return string|null
     */
    public function getShipmentIncrementId()
    {
        return $this->getData(self::SHIPMENT_INCREMENT_ID);
    }

    /**
     * set shipment order id
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * get shipment order id
     *
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * set is exported sap
     *
     * @param int $isExportedSap
     * @return $this
     */
    public function setIsExportedSap($isExportedSap)
    {
        return $this->setData(self::IS_EXPORTED_SAP, $isExportedSap);
    }

    /**
     * get is exported sap
     *
     * @return int
     */
    public function getIsExportedSap()
    {
        return $this->getData(self::IS_EXPORTED_SAP);
    }

    /**
     * set export sap date
     *
     * @param string $exportSapDate
     * @return $this
     */
    public function setExportSapDate($exportSapDate)
    {
        return $this->setData(self::EXPORT_SAP_DATE, $exportSapDate);
    }

    /**
     * get export sap date
     *
     * @return string|null
     */
    public function getExportSapDate()
    {
        return $this->getData(self::EXPORT_SAP_DATE);
    }

    /**
     * set backup file
     *
     * @param $fileName
     * @return $this
     */
    public function setBackupFile($fileName)
    {
        return $this->setData(self::BACKUP_FILE, $fileName);
    }

    /**
     * get backup file
     *
     * @return string|null
     */
    public function getBackupFile()
    {
        return $this->getData(self::BACKUP_FILE);
    }

    /**
     * set created at
     *
     * @param $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * get created at
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * set updated at
     *
     * @param $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }
    public function beforeSave()
    {
        if (is_null($this->getIsExportedSap()) and !is_null($this->getOrigData('is_exported_sap'))) {
            $this->_logger->debug("SapShipmentFlag:: the shipment " . $this->getShipmentIncrementId() ."::Before ".serialize($this->getOrigData()));
            $this->_logger->debug("SapShipmentFlag:: the shipment " . $this->getShipmentIncrementId() ."::After ".serialize($this->getData()));
            $exception = new \Exception("SapShipmentFlag:: the shipment " . $this->getShipmentIncrementId() . " is going to update wrong flag.");
            $this->_logger->debug($exception->getMessage() . "\n" . $exception->getTraceAsString());
        }
        return parent::beforeSave();
    }
}
