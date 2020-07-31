<?php

namespace Riki\Subscription\Model\Emulator\Order;

class ShipmentFactory
    extends \Magento\Sales\Model\Order\ShipmentFactory
{
    public function __construct(
        \Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory ,
        \Riki\Subscription\Model\Emulator\Convert\OrderFactory $emulatorConvertOrderFactory ,
        \Riki\Subscription\Model\Emulator\Order\Shipment\TrackFactory $emulatorTrackFactory
    )
    {
        parent::__construct($convertOrderFactory, $trackFactory);
        $this->converter = $emulatorConvertOrderFactory->create();
        $this->trackFactory = $emulatorTrackFactory ;
    }
}