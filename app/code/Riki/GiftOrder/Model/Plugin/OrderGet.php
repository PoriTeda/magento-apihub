<?php

namespace Riki\GiftOrder\Model\Plugin;

use Magento\Framework\Exception\NoSuchEntityException;

class OrderGet extends \Magento\GiftMessage\Model\Plugin\OrderGet
{
    /**
     * Get gift message for order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    protected function getOrderGiftMessage(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes && $extensionAttributes->getGiftMessage()) {
            return $order;
        }
        if ($order->getGiftMessageId()) {
            try {
                /** @var \Magento\GiftMessage\Api\Data\MessageInterface $giftMessage */
                $giftMessage = $this->giftMessageOrderRepository->get($order->getEntityId());
            } catch (NoSuchEntityException $e) {
                return $order;
            }

            /** @var \Magento\Sales\Api\Data\OrderExtension $orderExtension */
            $orderExtension = $extensionAttributes ? $extensionAttributes : $this->orderExtensionFactory->create();
            $orderExtension->setGiftMessage($giftMessage);
            $order->setExtensionAttributes($orderExtension);
        }
        return $order;
    }
    /**
     * Get gift message for items of order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    protected function getOrderItemGiftMessage(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $orderItems = $order->getItems();
        if (null !== $orderItems) {
            /** @var \Magento\Sales\Api\Data\OrderItemInterface $orderItem */
            foreach ($orderItems as $orderItem) {
                if ($orderItem->getGiftMessageId()) {
                    $extensionAttributes = $orderItem->getExtensionAttributes();
                    if ($extensionAttributes && $extensionAttributes->getGiftMessage()) {
                        continue;
                    }

                    try {
                        /* @var \Magento\GiftMessage\Api\Data\MessageInterface $giftMessage */
                        $giftMessage = $this->giftMessageOrderItemRepository->get(
                            $order->getEntityId(),
                            $orderItem->getItemId()
                        );
                    } catch (NoSuchEntityException $e) {
                        continue;
                    }

                    /** @var \Magento\Sales\Api\Data\OrderItemExtension $orderItemExtension */
                    $orderItemExtension = $extensionAttributes
                        ? $extensionAttributes
                        : $this->orderItemExtensionFactory->create();
                    $orderItemExtension->setGiftMessage($giftMessage);
                    $orderItem->setExtensionAttributes($orderItemExtension);
                }
            }
        }
        return $order;
    }
}
