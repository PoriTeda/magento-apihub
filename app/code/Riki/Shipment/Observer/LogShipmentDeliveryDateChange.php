<?php

namespace Riki\Shipment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use Riki\Shipment\Logger\Update as Logger;

class LogShipmentDeliveryDateChange implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * LogShipmentDeliveryDateChange constructor.
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if ($shipment->getId() && !$shipment->getData('delivery_date')) {
            /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
            $shipmentItems = $shipment->getItemsCollection();
            if (sizeof($shipmentItems) > 0) {
                $shipmentItem = reset($shipmentItems);
            }
            $orderItem = $shipmentItem->getOrderItem();

            if ($orderItem && $orderItem->getData('delivery_date')) {
                $this->logger->critical(new LocalizedException(__(
                    'The shipment #%1 delivery date has been changed to empty',
                    $shipment->getIncrementId()
                )));
            }
        }
    }
}
