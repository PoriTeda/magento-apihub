<?php


namespace Riki\Subscription\Logger;


class HandlerFreeMachine extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/subscription_add_free_machine.log';

}