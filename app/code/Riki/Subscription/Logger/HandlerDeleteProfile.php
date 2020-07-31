<?php

namespace Riki\Subscription\Logger;

class HandlerDeleteProfile extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/subscription_delete_profile.log';

}