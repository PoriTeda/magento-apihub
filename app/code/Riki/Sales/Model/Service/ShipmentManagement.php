<?php
namespace Riki\Sales\Model\Service;

class ShipmentManagement implements \Riki\Sales\Api\ShipmentManagementInterface
{

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $shipmentCollectionFactory;

    /**
     * ShipmentManagement constructor.
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
    )
    {
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
    }

    /**
     * @param $incrementId
     * @return \Magento\Framework\DataObject
     */
    public function getByIncrementId($incrementId)
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $shipmentCollection */
        $shipmentCollection = $this->shipmentCollectionFactory->create();

        return $shipmentCollection->addFieldToFilter('increment_id', $incrementId)
            ->setPageSize(1)
            ->getFirstItem();
    }
}
