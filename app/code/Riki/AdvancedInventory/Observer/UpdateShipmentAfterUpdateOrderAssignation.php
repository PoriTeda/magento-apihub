<?php
namespace Riki\AdvancedInventory\Observer;

use Magento\Framework\Exception\LocalizedException;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\AdvancedInventory\Cron\ReAssignation;

class UpdateShipmentAfterUpdateOrderAssignation implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface
     */
    private $pointOfSaleRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * UpdateShipmentAfterUpdateOrderAssignation constructor.
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface $pointOfSaleRepository
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface $pointOfSaleRepository,
        \Magento\Framework\Registry $registry
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->pointOfSaleRepository = $pointOfSaleRepository;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $shipmentIds = $order->getShipmentsCollection()->getAllIds();

        foreach ($shipmentIds as $id) {
            $shipment = $this->shipmentRepository->get($id);

            $shipmentStatus = $shipment->getShipmentStatus();

            // Get current ware house of shipment and order
            $shipmentWarehouse = $shipment->getWarehouse();
            $orderWarehouse = $this->getReAssignedWarehouseCode($order);

            // If they are the same warehouse, nothing to change on shipment.
            if ($shipmentWarehouse == $orderWarehouse) {
                continue;
            }

            switch ($shipmentStatus) {
                case ShipmentStatus::SHIPMENT_STATUS_CREATED:
                    $this->processReassignation($shipment, $order);
                    $this->shipmentRepository->save($shipment);
                    break;
                case ShipmentStatus::SHIPMENT_STATUS_REJECTED:
                case ShipmentStatus::SHIPMENT_STATUS_CANCEL:
                    break;
                case ShipmentStatus::SHIPMENT_STATUS_EXPORTED_PARTIAL:
                case ShipmentStatus::SHIPMENT_STATUS_EXPORTED:
                    $this->processReassignation($shipment, $order);
                    if ($shipment->dataHasChangedFor('warehouse')) {
                        $this->resetShipmentStatus($shipment);
                        $this->shipmentRepository->save($shipment);
                    }
                    break;
                default:
                    throw new LocalizedException(__(
                        'Order cannot change warehouse as the shipment already sent to customer'
                    ));
            }
        }
    }

    /**
     * @param \Magento\Sales\Api\Data\ShipmentInterface $shipment
     * @return $this
     */
    protected function resetShipmentStatus(\Magento\Sales\Api\Data\ShipmentInterface $shipment)
    {
        $shipment->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_CREATED)
            ->setIsExportedSap(null)
            ->setIsExported(0);

        return $this;
    }

    /**
     * @param \Magento\Sales\Api\Data\ShipmentInterface $shipment
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     */
    protected function processReassignation(
        \Magento\Sales\Api\Data\ShipmentInterface $shipment,
        \Magento\Sales\Model\Order $order
    ) {
        if ($this->registry->registry(ReAssignation::IS_REASSIGNATION_CRON_NAME)
            && $warehouseCode = $this->getReAssignedWarehouseCode($order)
        ) {
            $shipment->setWarehouse($warehouseCode);
        }

        return $shipment;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return null
     */
    protected function getReAssignedWarehouseCode(\Magento\Sales\Model\Order $order)
    {
        $assignedToId = $order->getAssignedTo();

        if ($assignedToId) {
            $pos = $this->pointOfSaleRepository->get($assignedToId);
            return $pos->getStoreCode();
        }

        return null;
    }
}
