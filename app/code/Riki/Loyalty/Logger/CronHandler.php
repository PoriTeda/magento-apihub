<?php

namespace Riki\Loyalty\Logger;

use Monolog\Logger;

class CronHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/cron_resend_shopping_point.log';
}
