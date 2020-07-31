<?php

namespace Riki\Sales\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ShipmentUnserializeFields implements ObserverInterface
{
    /**
     * Un-serialize fields
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shipment = $observer->getShipment();
        $shipment->getResource()->unserializeFields($shipment);
    }
}
