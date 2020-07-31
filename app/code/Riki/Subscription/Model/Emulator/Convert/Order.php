<?php

namespace Riki\Subscription\Model\Emulator\Convert;

class Order
    extends \Magento\Sales\Model\Convert\Order
{
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Model\Order\Invoice\ItemFactory $invoiceItemFactory,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Sales\Model\Order\Shipment\ItemFactory $shipmentItemFactory,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Model\Order\Creditmemo\ItemFactory $creditmemoItemFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Riki\Subscription\Model\Emulator\Order\InvoiceRepository $emulatorInvoiceRepository,
        \Riki\Subscription\Model\Emulator\Order\ShipmentRepository $emulatorShipmentRepository,
        \Riki\Subscription\Model\Emulator\Order\Shipment\ItemFactory $emulatorShipmentItemFactory,
        array $data = []
    )
    {
        parent::__construct($eventManager, $invoiceRepository, $invoiceItemFactory, $shipmentRepository, $shipmentItemFactory, $creditmemoRepository, $creditmemoItemFactory, $objectCopyService, $data);
        $this->invoiceRepository = $emulatorInvoiceRepository;
        $this->shipmentRepository = $emulatorShipmentRepository;
        $this->_shipmentItemFactory = $emulatorShipmentItemFactory;
    }
}