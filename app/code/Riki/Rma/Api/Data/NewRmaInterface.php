<?php
namespace Riki\Rma\Api\Data;

interface NewRmaInterface
{

    /**
     * Get date_requested
     *
     * @return string
     */
    public function getDateRequested();

    /**
     * Set date_requested
     *
     * @param string $dateRequested
     * @return \Magento\Framework\DataObject
     */
    public function setDateRequested($dateRequested);

    /**
     * Get items
     *
     * @return \Riki\Rma\Api\Data\ItemInterface[]
     */
    public function getItems();

    /**
     * Set items
     *
     * @param \Riki\Rma\Api\Data\ItemInterface[] $items
     * @return \Magento\Framework\DataObject
     */
    public function setItems(array $items = null);

    /**
     * Get reason ID
     *
     * @return int
     */
    public function getReasonId();

    /**
     * Set reason ID
     *
     * @param int $reasonId
     * @return \Magento\Framework\DataObject
     */
    public function setReasonId($reasonId);

    /**
     * Get shipment number
     *
     * @return string
     */
    public function getRmaShipmentNumber();

    /**
     * Set shipment number
     *
     * @param string $shipmentIncrementId
     * @return \Magento\Framework\DataObject
     */
    public function setRmaShipmentNumber($shipmentIncrementId);

    /**
     * Get substitution order number
     *
     * @return string
     */
    public function getSubstitutionOrder();

    /**
     * Set substitution order number
     *
     * @param string $orderNumber
     * @return \Magento\Framework\DataObject
     */
    public function setSubstitutionOrder($orderNumber);

    /**
     * Get returned warehouse
     *
     * @return string
     */
    public function getReturnedWarehouse();

    /**
     * Set returned warehouse
     *
     * @param string $warehouse
     * @return \Magento\Framework\DataObject
     */
    public function setReturnedWarehouse($warehouse);

    /**
     * Get returned warehouse
     *
     * @return string
     */
    public function getFullPartial();

    /**
     * Set returned warehouse
     *
     * @param string $fullPartial
     * @return \Magento\Framework\DataObject
     */
    public function setFullPartial($fullPartial);

    /**
     * Get comments list
     *
     * @return string
     */
    public function getComments();

    /**
     * Set comments list
     *
     * @param string $comments
     * @return \Magento\Framework\DataObject
     */
    public function setComments($comments);
}