<?php


namespace Nestle\Debugging\Model\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class NestleDebugHandler extends Base
{
    protected $fileName   = '/var/log/debugging/debug.log';
    protected $loggerType = Logger::DEBUG;
}
