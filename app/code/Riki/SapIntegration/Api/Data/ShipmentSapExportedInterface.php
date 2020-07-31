<?php

namespace Riki\SapIntegration\Api\Data;

interface ShipmentSapExportedInterface
{
    const SHIPMENT_ENTITY_ID = 'shipment_entity_id';
    const SHIPMENT_INCREMENT_ID = 'shipment_increment_id';
    const ORDER_ID = 'order_id';
    const IS_EXPORTED_SAP = 'is_exported_sap';
    const EXPORT_SAP_DATE = 'export_sap_date';
    const BACKUP_FILE = 'backup_file';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * set shipment entity id
     *
     * @param int $shipmentEntityId
     * @return $this
     */
    public function setShipmentEntityId($shipmentEntityId);

    /**
     * get shipment entity id
     *
     * @return int|null
     */
    public function getShipmentEntityId();

    /**
     * set shipment increment id
     *
     * @param string $shipmentIncrementId
     * @return $this
     */
    public function setShipmentIncrementId($shipmentIncrementId);

    /**
     * get shipment increment id
     *
     * @return string|null
     */
    public function getShipmentIncrementId();

    /**
     * set shipment order id
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * get shipment order id
     *
     * @return int|null
     */
    public function getOrderId();

    /**
     * set is exported sap
     *
     * @param int $isExportedSap
     * @return $this
     */
    public function setIsExportedSap($isExportedSap);

    /**
     * get is exported sap
     *
     * @return int
     */
    public function getIsExportedSap();

    /**
     * set export sap date
     *
     * @param string $exportSapDate
     * @return $this
     */
    public function setExportSapDate($exportSapDate);

    /**
     * get export sap date
     *
     * @return string|null
     */
    public function getExportSapDate();

    /**
     * set backup file
     *
     * @param $fileName
     * @return $this
     */
    public function setBackupFile($fileName);

    /**
     * get backup file
     *
     * @return string|null
     */
    public function getBackupFile();

    /**
     * set created at
     *
     * @param $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * get created at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * set updated at
     *
     * @param $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt();
}
