<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Shipment\Model\Order\ShipmentBuilder;
use Riki\Shipment\Api\ShipmentBuilder\ProfileOrderInterface;

/**
 * @codeCoverageIgnore
 */
class ProfileOrder implements ProfileOrderInterface
{
    /**
     * @var int
     */
    private $orderId;


    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getOrderId()
    {
        return $this->orderId;
    }
}
