<?php

namespace Riki\CustomerCustomAttributes\Plugin;

class SalesOrderAddressAfterSaveSimulation
{
    public function aroundExecute($subject, $proceed, $observer)
    {
        $orderAddress = $observer->getEvent()->getAddress();
        if (!$orderAddress instanceof \Riki\Subscription\Model\Emulator\Order\Address) {
            return $proceed($observer);
        }

        return $observer;
    }
}
