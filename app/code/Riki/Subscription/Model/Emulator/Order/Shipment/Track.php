<?php

namespace Riki\Subscription\Model\Emulator\Order\Shipment;

class Track
    extends \Magento\Sales\Model\Order\Shipment\Track
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Riki\Subscription\Model\Emulator\Order\ShipmentRepository $emulatorShipmentRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $storeManager, $shipmentRepository, $resource, $resourceCollection, $data);
        $this->shipmentRepository = $emulatorShipmentRepository;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\Track');
    }
}

