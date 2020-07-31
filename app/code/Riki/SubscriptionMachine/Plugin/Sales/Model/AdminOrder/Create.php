<?php

namespace Riki\SubscriptionMachine\Plugin\Sales\Model\AdminOrder;

class Create
{
    /**
     * remove free machine item for reorder action
     *
     * @param \Magento\Sales\Model\AdminOrder\Create $subject
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param null $qty
     * @return array
     */
    public function beforeInitFromOrderItem(
        \Magento\Sales\Model\AdminOrder\Create $subject,
        \Magento\Sales\Model\Order\Item $orderItem,
        $qty = null
    ) {
        $order = $orderItem->getOrder();

        if ($order->getReordered()) { // avoid edit order case
            $buyRequest = $orderItem->getBuyRequest();
            if (isset($buyRequest['options']['free_machine_item'])) {
                $orderItem->setId(null);
            }
        }

        return [$orderItem, $qty];
    }
}
