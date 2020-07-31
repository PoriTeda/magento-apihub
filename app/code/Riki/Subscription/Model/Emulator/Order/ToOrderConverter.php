<?php


namespace Riki\Subscription\Model\Emulator\Order;


class ToOrderConverter
    extends \Magento\Quote\Model\Quote\Address\ToOrder

{
    public function __construct(
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper ,
        \Riki\Subscription\Model\Emulator\OrderFactory $emulatorOrderFactory
    )
    {
        parent::__construct($orderFactory, $objectCopyService, $eventManager, $dataObjectHelper);
        $this->orderFactory = $emulatorOrderFactory;
    }

}