<?php
namespace Riki\Rma\Api\Data;

interface RmaInterface
{
    /**
     * Get entity_id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set entity_id
     *
     * @param int $entityId
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setEntityId($entityId);

    /**
     * Get increment_id
     *
     * @return string
     */
    public function getIncrementId();

    /**
     * Set increment_id
     *
     * @param string $incrementId
     *
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setIncrementId($incrementId);

    /**
     * Get order_id
     *
     * @return int
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param int $orderId
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setOrderId($orderId);

    /**
     * Get store_id
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Set store_id
     *
     * @param int $storeId
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setStoreId($storeId);

    /**
     * Get customer_id
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set customer_id
     *
     * @param int $customerId
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setCustomerId($customerId);


    /**
     * Get refund_method
     *
     * @return string
     */
    public function getRefundMethod();

    /**
     * Set refund_method
     *
     * @param string $method
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setRefundMethod($method);

    /**
     * Get order_increment_id
     *
     * @return string
     */
    public function getOrderIncrementId();

    /**
     * Set order_increment_id
     *
     * @param string $incrementId
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setOrderIncrementId($incrementId);

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
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setDateRequested($dateRequested);

    /**
     * Get returned_date
     *
     * @return string
     */
    public function getReturnedDate();

    /**
     * Set returned_date
     *
     * @param string $dateRequested
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setReturnedDate($dateRequested);

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
     * @return \Riki\Rma\Api\Data\RmaInterface
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
     * @return \Riki\Rma\Api\Data\RmaInterface
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
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setRmaShipmentNumber($shipmentIncrementId);

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
     * @return \Riki\Rma\Api\Data\RmaInterface
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
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setFullPartial($fullPartial);

    /**
     * Get comments list
     *
     * @return \Magento\Rma\Api\Data\CommentInterface[]
     */
    public function getComments();

    /**
     * Set comments list
     *
     * @param \Magento\Rma\Api\Data\CommentInterface[] $comments
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setComments(array $comments = null);

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
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setSubstitutionOrder($orderNumber);

    /**
     * Get refund allowed
     *
     * @return int
     */
    public function getRefundAllowed();

    /**
     * Set refund allowed
     *
     * @param int $refundAllowed
     * @return \Riki\Rma\Api\Data\RmaInterface
     */
    public function setRefundAllowed($refundAllowed);
}
