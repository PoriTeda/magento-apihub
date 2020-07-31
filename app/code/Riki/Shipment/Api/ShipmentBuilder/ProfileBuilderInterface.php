<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Shipment\Api\ShipmentBuilder;

/**
 * Interface ProfileBuilderInterface
 * @package Riki\Subscription\Api\GenerateOrder
 */
interface ProfileBuilderInterface
{
    /**
     * @param \Riki\Shipment\Api\ShipmentBuilder\ProfileOrderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);

    /**
     * @return \Riki\Shipment\Api\ShipmentBuilder\ProfileOrderInterface[]
     */
    public function getItems();
}
