<?php

namespace Riki\Subscription\Api\Simulator;


interface DeliveryDateSimulatorInterface
{
    /**
     *
     * @param \Riki\Subscription\Model\Simulator\OrderSimulator $orderSimulator
     * @return mixed
     */
    public function processDeliveryDateSimulator($orderSimulator);

}