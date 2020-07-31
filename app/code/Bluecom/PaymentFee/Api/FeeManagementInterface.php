<?php
namespace Bluecom\PaymentFee\Api;

interface FeeManagementInterface
{
    /**
     * Get payment fee by method
     *
     * @param $method
     *
     * @return int
     */
    public function getFeeByMethod($method);
}