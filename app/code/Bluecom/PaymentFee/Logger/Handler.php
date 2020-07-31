<?php

namespace Bluecom\PaymentFee\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Init
     *
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * Set file name
     *
     * @var string
     */
    protected $fileName = '/var/log/paymentfee.log';

}