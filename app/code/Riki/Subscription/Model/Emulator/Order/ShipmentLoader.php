<?php

namespace Riki\Subscription\Model\Emulator\Order;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\ShipmentDocumentFactory;

class ShipmentLoader extends \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader
{
    public function __construct(
        ManagerInterface $messageManager,
        Registry $registry,
        ShipmentRepositoryInterface $shipmentRepository,
        OrderRepositoryInterface $orderRepository,
        ShipmentDocumentFactory $documentFactory,
        ShipmentTrackCreationInterfaceFactory $trackFactory,
        ShipmentItemCreationInterfaceFactory $itemFactory,
        \Riki\Subscription\Model\Emulator\OrderRepository $emulatorOrderRepository ,
        \Riki\Subscription\Model\Emulator\Order\Shipment\TrackFactory $emulatorTrackFactory ,
        \Riki\Subscription\Model\Emulator\Order\ShipmentRepository $emulatorShipmentRepository,
        array $data = []
    )
    {
        parent::__construct($messageManager, $registry, $shipmentRepository, $orderRepository, $documentFactory, $trackFactory, $itemFactory, $data);

        $this->orderRepository = $emulatorOrderRepository;
        $this->trackFactory = $emulatorTrackFactory;
        $this->shipmentRepository = $emulatorShipmentRepository;
    }
}