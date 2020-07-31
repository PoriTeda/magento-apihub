<?php

namespace Riki\Rma\Logger\ReviewCc\Handler;

use Magento\Framework\Logger\Handler\Base;

class Error extends Base
{
    protected $loggerType = \Monolog\Logger::CRITICAL;
}