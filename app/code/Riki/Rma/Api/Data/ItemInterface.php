<?php
namespace Riki\Rma\Api\Data;

interface ItemInterface
{
    /**
     * Get sku
     *
     * @return string
     */
    public function getSku();

    /**
     * Set sku
     *
     * @param string $sku
     * @return \Riki\Rma\Api\Data\ItemInterface
     */
    public function setSku($sku);

    /**
     * Get order_item_id
     *
     * @return int
     */
    public function getOrderItemId();

    /**
     * Set order_item_id
     *
     * @param int $id
     * @return \Riki\Rma\Api\Data\ItemInterface
     */
    public function setOrderItemId($id);

    /**
     * Get qty_requested
     *
     * @return int
     */
    public function getQtyRequested();

    /**
     * Set qty_requested
     *
     * @param int $qtyRequested
     * @return \Riki\Rma\Api\Data\ItemInterface
     */
    public function setQtyRequested($qtyRequested);
}