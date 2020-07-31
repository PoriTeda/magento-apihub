<?php
namespace Riki\Sales\Logger;
#class Logger
use Monolog\Logger;

class HandlerSales extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/riki_sales.log';

}