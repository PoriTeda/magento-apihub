<?php

namespace Riki\Subscription\Model\Emulator;

class OrderManagement
    extends \Magento\Sales\Model\Service\OrderService
{
   public function __construct(
       \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
       \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $historyRepository,
       \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
       \Magento\Framework\Api\FilterBuilder $filterBuilder,
       \Magento\Sales\Model\OrderNotifier $notifier,
       \Magento\Framework\Event\ManagerInterface $eventManager,
       \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender,
       \Riki\Subscription\Model\Emulator\OrderRepository $emulatorOrderRepository
   )
   {
       parent::__construct($orderRepository, $historyRepository, $criteriaBuilder, $filterBuilder, $notifier, $eventManager, $orderCommentSender);
       $this->orderRepository = $emulatorOrderRepository;
   }
}