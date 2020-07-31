<?php

namespace Riki\Fraud\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $fileName = '/var/log/cedyna_threshold.log';
    protected $loggerType = \Monolog\Logger::INFO;
}