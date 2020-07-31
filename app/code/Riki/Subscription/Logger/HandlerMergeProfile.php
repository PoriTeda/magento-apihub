<?php


namespace Riki\Subscription\Logger;


class HandlerMergeProfile extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/subscription_merge_profile.log';

}