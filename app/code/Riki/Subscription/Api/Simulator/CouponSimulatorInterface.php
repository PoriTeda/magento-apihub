<?php

namespace Riki\Subscription\Api\Simulator;


interface CouponSimulatorInterface
{

    /**
     * @param integer $profileId
     * @param string $couponCode
     * @param string $action
     * @return mixed
     */
    public function couponApplied($profileId,$couponCode,$action);


}