<?php

namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class ValidateShippingAddressBeforeCreateOrder implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        $orderAddresses = $order->getAddresses();

        $availableAddresses = $quote->getCustomer()->getAddresses();

        foreach ($orderAddresses as $orderAddress) {
            $customerAddressId = $orderAddress->getCustomerAddressId();

            if (!$customerAddressId) {
                continue;
            }

            foreach ($availableAddresses as $address) {
                if ($customerAddressId == $address->getId()) {
                    continue 2;
                }
            }

            throw new LocalizedException(__('Cannot place order.'));
        }
    }
}