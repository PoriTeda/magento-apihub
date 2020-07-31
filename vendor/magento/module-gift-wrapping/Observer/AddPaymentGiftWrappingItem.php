<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddPaymentGiftWrappingItem implements ObserverInterface
{
    /**
     * Add gift wrapping items into payment checkout
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Payment\Model\Cart $cart */
        $cart = $observer->getEvent()->getCart();
        $totalWrapping = 0;
        $totalCard = 0;
        $salesEntity = $cart->getSalesModel();
        foreach ($salesEntity->getAllItems() as $item) {
            $originalItem = $item->getOriginalItem();
            if (!$originalItem->getParentItem() && $originalItem->getGwId() && $originalItem->getGwBasePrice()) {
                $totalWrapping += $originalItem->getGwBasePrice();
            }
        }
        if ($salesEntity->getDataUsingMethod('gw_id') && $salesEntity->getDataUsingMethod('gw_base_price')) {
            $totalWrapping += $salesEntity->getDataUsingMethod('gw_base_price');
        }
        if ($salesEntity->getDataUsingMethod('gw_add_card') && $salesEntity->getDataUsingMethod('gw_card_base_price')
        ) {
            $totalCard += $salesEntity->getDataUsingMethod('gw_card_base_price');
        }
        if ($totalWrapping) {
            $cart->addCustomItem(__('Gift Wrapping'), 1, $totalWrapping);
        }
        if ($totalCard) {
            $cart->addCustomItem(__('Printed Card'), 1, $totalCard);
        }
    }
}
