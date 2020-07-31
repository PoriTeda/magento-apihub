<?php

namespace Riki\Sales\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Riki\Sales\Api\Data\OrderStatusInterface;

class OrderStatus extends AbstractExtensibleObject implements OrderStatusInterface
{
    /**
     * Set order status
     *
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get order status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    /**
     * Set Ship out date
     *
     * @param string $date
     *
     * @return $this
     */
    public function setShipOutDate($date)
    {
        return $this->setData(self::SHIP_OUT_DATE, $date);
    }

    /**
     * Get Ship out date
     *
     * @return string
     */
    public function getShipOutDate()
    {
        return $this->_get(self::SHIP_OUT_DATE);
    }

    /**
     * Set delivery completion date
     *
     * @param string $date
     *
     * @return $this
     */
    public function setDeliveryCompletionDate($date)
    {
        return $this->setData(self::DELIVERY_COMPLETION_DATE, $date);
    }

    /**
     * Get delivery completion date
     *
     * @return string
     */
    public function getDeliveryCompletionDate()
    {
        return $this->_get(self::DELIVERY_COMPLETION_DATE);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @api
     * @return \Riki\Sales\Api\Data\OrderStatusExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     *
     * @api
     * @param \Riki\Sales\Api\Data\OrderStatusExtensionInterface $extensionAttributes
     *
     * @return $this
     */
    public function setExtensionAttributes(\Riki\Sales\Api\Data\OrderStatusExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
