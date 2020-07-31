<?php
namespace Riki\SubscriptionCutOffEmail\Logger;

class HandlerSendCutOffEmail extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/send_cut_off_email.log';
}
