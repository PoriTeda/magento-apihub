<?php

namespace Riki\Preorder\Logger;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/pre_order.log';

    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
}
