<?php

namespace Riki\Loyalty\Observer\Sales\Order;

use Magento\Framework\Event\ObserverInterface;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;

class SalesOrderLoadAfter implements ObserverInterface
{
    /**
     * @var \Riki\Loyalty\Model\Reward
     */
    protected $rewardModel;

    public function __construct(\Riki\Loyalty\Model\Reward $reward)
    {
        $this->rewardModel = $reward;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if ($order->getStatus() == OrderStatus::STATUS_ORDER_PENDING_CRD_REVIEW) {
            $pendingPoint = $this->rewardModel->getResource()->pointOrderByStatus(
                $order->getIncrementId(),
                \Riki\Loyalty\Model\Reward::STATUS_PENDING_APPROVAL
            );
            if ($pendingPoint) {
                $order->setPendingPoint($pendingPoint);
                $order->setCanShowPointApproval(true);
            }
        }
    }
}
