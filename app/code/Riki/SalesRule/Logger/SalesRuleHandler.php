<?php
namespace Riki\SalesRule\Logger;

use Monolog\Logger;

class SalesRuleHandler extends \Magento\Framework\Logger\Handler\Base
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
    protected $fileName = '/var/log/sales_rule.log';
}