<?php
namespace Riki\Rma\Model;

class NewRma extends \Magento\Framework\DataObject implements \Riki\Rma\Api\Data\NewRmaInterface
{
    const REASON_CODE = 'reason_code';
    const SHIPMENT_NUMBER = 'rma_shipment_number';
    const RETURNED_DATE = 'returned_date';
    const RETURNED_WAREHOUSE = 'returned_warehouse_code';
    const FULL_PARTIAL = 'full_partial_text';
    const COMMENTS = 'new_comment';
    const SUBSTITUTION_ORDER = 'substitution_order';
    const ITEMS = 'items';

    /**
     * Get shipment number
     *
     * @return string
     */
    public function getRmaShipmentNumber()
    {
        return $this->getData(self::SHIPMENT_NUMBER);
    }

    /**
     * Set shipment number
     *
     * @param string $shipmentIncrementId
     * @return \Magento\Framework\DataObject
     */
    public function setRmaShipmentNumber($shipmentIncrementId)
    {
        return $this->setData(self::SHIPMENT_NUMBER, $shipmentIncrementId);
    }

    /**
     * Get returned_date
     *
     * @return string
     */
    public function getDateRequested()
    {
        return $this->getData(self::RETURNED_DATE);
    }

    /**
     * Set returned_date
     *
     * @param string $dateRequested
     * @return \Magento\Framework\DataObject
     */
    public function setDateRequested($dateRequested)
    {
        return $this->setData(self::RETURNED_DATE, $dateRequested);
    }

    /**
     * Get reason Code
     *
     * @return int
     */
    public function getReasonId()
    {
        return $this->getData(self::REASON_CODE);
    }

    /**
     * Set reason Code
     *
     * @param int $reasonId
     * @return \Magento\Framework\DataObject
     */
    public function setReasonId($reasonId)
    {
        return $this->setData(self::REASON_CODE, $reasonId);
    }



    /**
     * Get returned warehouse code
     *
     * @return string
     */
    public function getReturnedWarehouse()
    {
        return $this->getData(self::RETURNED_WAREHOUSE);
    }

    /**
     * Set returned warehouse code
     *
     * @param string $warehouse
     * @return \Magento\Framework\DataObject
     */
    public function setReturnedWarehouse($warehouse)
    {
        $this->setData(self::RETURNED_WAREHOUSE, $warehouse);
    }

    /**
     * Get full partial text
     *
     * @return string
     */
    public function getFullPartial()
    {
        return $this->getData(self::FULL_PARTIAL);
    }

    /**
     * Set full partial text
     *
     * @param string $fullPartial
     * @return \Magento\Framework\DataObject
     */
    public function setFullPartial($fullPartial)
    {
        return $this->setData(self::FULL_PARTIAL, $fullPartial);
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComments()
    {
        return $this->getData(self::COMMENTS);
    }

    /**
     * Set new comment
     *
     * @param string $comment
     * @return \Magento\Framework\DataObject
     */
    public function setComments($comment)
    {
        return $this->setData(self::COMMENTS, $comment);
    }

    /**
     * Get items
     *
     * @return \Riki\Rma\Api\Data\ItemInterface[]
     */
    public function getItems()
    {
        return $this->getData(self::ITEMS);
    }

    /**
     * Set items
     *
     * @param \Riki\Rma\Api\Data\ItemInterface[] $items
     * @return \Magento\Framework\DataObject
     */
    public function setItems(array $items = null)
    {
        return $this->setData(self::ITEMS, $items);
    }

    /**
     * Get substitution order number
     *
     * @return string
     */
    public function getSubstitutionOrder()
    {
        return $this->getData(self::SUBSTITUTION_ORDER);
    }

    /**
     * Set substitution order number
     *
     * @param string $orderNumber
     * @return \Magento\Framework\DataObject
     */
    public function setSubstitutionOrder($orderNumber)
    {
        return $this->setData(self::SUBSTITUTION_ORDER, $orderNumber);
    }
}
