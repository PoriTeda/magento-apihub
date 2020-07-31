<?php
namespace Riki\Subscription\Model\Emulator\Order;


class ToOrderItemConverter
    extends \Magento\Quote\Model\Quote\Item\ToOrderItem
{
    public function __construct(
        \Magento\Sales\Api\Data\OrderItemInterfaceFactory $orderItemFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper ,
        \Riki\Subscription\Model\Emulator\Order\ItemFactory $emulatorOrderItemFactory)
    {
        parent::__construct($orderItemFactory, $objectCopyService, $dataObjectHelper);
        $this->orderItemFactory = $emulatorOrderItemFactory;
    }

}