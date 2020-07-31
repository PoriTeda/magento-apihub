<?php

namespace Riki\Subscription\Logger;

class HandlerDelayPayment extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/delay_payment_order.log';

}