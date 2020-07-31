<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Observer;

use Magento\Framework\Event\ObserverInterface;

class BeforeSubmitObserver
    implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var  \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if($order instanceof \Riki\Subscription\Model\Emulator\Order){
            $order->setCanSendNewEmailFlag(false);
        }

    }
}
