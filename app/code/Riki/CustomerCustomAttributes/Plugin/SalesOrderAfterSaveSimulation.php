<?php

namespace Riki\CustomerCustomAttributes\Plugin;

class SalesOrderAfterSaveSimulation
{
    public function aroundExecute($subject, $proceed, $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $proceed($observer);
        }

        return $observer;
    }
}
