<?php

namespace Riki\Rma\Logger\Refund;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    protected $fileName = '/var/log/refund.log';
    protected $loggerType = \Monolog\Logger::ERROR;
}