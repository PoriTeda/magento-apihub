<?php

namespace Riki\SubscriptionMachine\Logger;

class ApiHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/subscription_machine_api.log';
}
