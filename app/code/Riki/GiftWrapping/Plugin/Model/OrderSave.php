<?php

namespace Riki\GiftWrapping\Plugin\Model;

class OrderSave extends \Magento\GiftMessage\Model\Plugin\OrderSave
{
    protected function saveOrderGiftMessage(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        if (!$order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return parent::saveOrderGiftMessage($order);
        }

        return $order;
    }

    protected function saveOrderItemGiftMessage(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        if (!$order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return parent::saveOrderItemGiftMessage($order);
        }

        return $order;
    }
}
