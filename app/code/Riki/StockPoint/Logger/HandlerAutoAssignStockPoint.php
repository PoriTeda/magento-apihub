<?php
namespace Riki\StockPoint\Logger;

class HandlerAutoAssignStockPoint extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/auto_assign_stock_point.log';
}
