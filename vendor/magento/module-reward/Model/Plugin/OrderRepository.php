<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Plugin;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderInterface;

class OrderRepository
{
    /**
     * Check if credit memo can be created for order with reward points
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @param int $orderId
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        OrderInterface $order,
        $orderId
    ) {
        if ($order->canUnhold() || $order->isCanceled() || $order->getState() === Order::STATE_CLOSED) {
            return $order;
        }

        if ($order->getBaseRwrdCrrncyAmtInvoiced() > $order->getBaseRwrdCrrncyAmntRefnded()) {
            $order->setForcedCanCreditmemo(true);
        }

        return $order;
    }
}
