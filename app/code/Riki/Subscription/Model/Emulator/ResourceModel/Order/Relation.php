<?php
namespace Riki\Subscription\Model\Emulator\ResourceModel\Order;

class Relation
    extends \Magento\Sales\Model\ResourceModel\Order\Relation
{
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Handler\Address $addressHandler,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Sales\Model\ResourceModel\Order\Payment $orderPaymentResource,
        \Magento\Sales\Model\ResourceModel\Order\Status\History $orderStatusHistoryResource ,
        \Riki\Subscription\Model\Emulator\Order\ItemRepository $emulatorOrderItemRepository ,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Payment $emulatorPaymentResource,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Status\History $emulatorOrderStatusHistory,
        \Riki\Subscription\Model\Emulator\ResourceModel\Order\Handler\Address $emulatorAddressHandler
    )
    {
        parent::__construct($addressHandler, $orderItemRepository, $orderPaymentResource, $orderStatusHistoryResource);
        $this->orderItemRepository = $emulatorOrderItemRepository;
        $this->orderPaymentResource = $emulatorPaymentResource;
        $this->orderStatusHistoryResource = $emulatorOrderStatusHistory;
        $this->addressHandler = $emulatorAddressHandler;
    }
}