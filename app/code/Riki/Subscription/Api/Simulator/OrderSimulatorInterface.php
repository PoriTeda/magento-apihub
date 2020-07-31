<?php

namespace Riki\Subscription\Api\Simulator;


interface OrderSimulatorInterface
{
    /**
     *
     * @param string $profileId
     * @return mixed
     */
    public function processOrderSimulator($profileId);

}