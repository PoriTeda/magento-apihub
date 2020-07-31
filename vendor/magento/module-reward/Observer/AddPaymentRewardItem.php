<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddPaymentRewardItem implements ObserverInterface
{
    /**
     * Add reward amount to payment discount total
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Payment\Model\Cart $cart */
        $cart = $observer->getEvent()->getCart();
        $salesEntity = $cart->getSalesModel();
        $discount = abs($salesEntity->getDataUsingMethod('base_reward_currency_amount'));
        if ($discount > 0.0001) {
            $cart->addDiscount((double)$discount);
        }
    }
}
