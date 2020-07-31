<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Shipment\Api\ShipmentBuilder;

/**
 * Interface ProfileOrderInterface
 * @package Riki\Subscription\Api\GenerateOrder
 */
interface ProfileOrderInterface
{
    /**
     * @param $orderId
     * @return mixed
     */
    public function setOrderId($orderId);

    /**
     * @return mixed
     */
    public function getOrderId();
}
