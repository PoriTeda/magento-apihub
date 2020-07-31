<?php

namespace Riki\Subscription\Model\Emulator\Order\Shipment;

class Item
    extends \Magento\Sales\Model\Order\Shipment\Item
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Sales\Model\Order\ItemFactory $orderItemFactory,
        \Riki\Subscription\Model\Emulator\Order\ItemFactory $emulatorOrderItemFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $orderItemFactory, $resource, $resourceCollection, $data);
        $this->_orderItemFactory = $emulatorOrderItemFactory;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\ResourceModel\Order\Shipment\Item');
    }
}