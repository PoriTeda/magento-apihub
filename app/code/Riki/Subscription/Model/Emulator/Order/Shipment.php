<?php

namespace Riki\Subscription\Model\Emulator\Order;

class Shipment
    extends \Magento\Sales\Model\Order\Shipment
{

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory $shipmentItemCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory $trackCollectionFactory,
        \Magento\Sales\Model\Order\Shipment\CommentFactory $commentFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Comment\CollectionFactory $commentCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\Item\CollectionFactory $emulatorShipmentItemCollectionFactory ,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\Track\CollectionFactory $emulatorShipmentTrackCollectionFactory ,
        \Riki\Subscription\Model\Emulator\OrderRepository $emulatorOrderRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $shipmentItemCollectionFactory, $trackCollectionFactory, $commentFactory, $commentCollectionFactory, $orderRepository, $resource, $resourceCollection, $data);
        $this->_shipmentItemCollectionFactory = $emulatorShipmentItemCollectionFactory;
        $this->_trackCollectionFactory = $emulatorShipmentTrackCollectionFactory;
        $this->orderRepository = $emulatorOrderRepository;
    }

    /**
     * Initialize shipment resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment');
    }
}