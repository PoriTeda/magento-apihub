<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Shipment\Model\Order\ShipmentBuilder;

use Riki\Shipment\Api\ShipmentBuilder\ProfileBuilderInterface;

/**
 * @codeCoverageIgnore
 */
class ProfileBuilder implements ProfileBuilderInterface
{
    /**
     * @var []
     */
    private $items;

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setItems(array $items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getItems()
    {
        return $this->items;
    }
}
