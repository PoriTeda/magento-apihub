<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment;

class Relation
    extends \Magento\Sales\Model\ResourceModel\Order\Shipment\Relation
{
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Item $shipmentItemResource,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track $shipmentTrackResource,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Comment $shipmentCommentResource ,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\Item $emulatorShipmentItemResource ,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\Track $emulatorTrackResource
    )
    {
        parent::__construct($shipmentItemResource, $shipmentTrackResource, $shipmentCommentResource);
        $this->shipmentItemResource = $emulatorShipmentItemResource;
        $this->shipmentTrackResource = $emulatorTrackResource;
    }
}