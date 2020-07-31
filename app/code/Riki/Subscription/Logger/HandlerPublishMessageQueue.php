<?php


namespace Riki\Subscription\Logger;


class HandlerPublishMessageQueue extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/subscription_profile_publish_queue.log';

}