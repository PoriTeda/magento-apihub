<?php
namespace Riki\SubscriptionCourse\Logger;
#class Logger
use Monolog\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = LoggerUpdateSubStatus::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/subscription_update_status.log';

}