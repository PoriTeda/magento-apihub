<?php

namespace Riki\Rma\Logger\Point;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    protected $fileName = '/var/log/return_reward_point.log';
    protected $loggerType = \Monolog\Logger::ERROR;
}