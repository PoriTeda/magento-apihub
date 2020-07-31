<?php

namespace Riki\Sales\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface OrderStatusInterface extends ExtensibleDataInterface
{
    const STATUS = 'status';

    const SHIP_OUT_DATE = 'ship_out_date';

    const DELIVERY_COMPLETION_DATE = 'delivery_completion_date';

    /**
     * Set order status
     *
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get order status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set Ship out date
     *
     * @param string $date
     *
     * @return $this
     */
    public function setShipOutDate($date);

    /**
     * Get Ship out date
     *
     * @return string
     */
    public function getShipOutDate();

    /**
     * Set delivery completion date
     *
     * @param string $date
     *
     * @return $this
     */
    public function setDeliveryCompletionDate($date);

    /**
     * Get delivery completion date
     *
     * @return string
     */
    public function getDeliveryCompletionDate();

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @api
     * @return \Riki\Sales\Api\Data\OrderStatusExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @api
     * @param \Riki\Sales\Api\Data\OrderStatusExtensionInterface $extensionAttributes
     *
     * @return $this
     */
    public function setExtensionAttributes(\Riki\Sales\Api\Data\OrderStatusExtensionInterface $extensionAttributes);
}
