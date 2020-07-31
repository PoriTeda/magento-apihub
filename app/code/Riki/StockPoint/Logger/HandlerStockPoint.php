<?php

namespace Riki\StockPoint\Logger;

class HandlerStockPoint extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName ='/var/log/stock_point.log';
}
