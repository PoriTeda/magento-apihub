<?php
namespace Riki\AdvancedInventory\Logger;
#class Logger
use Monolog\Logger;

class HandlerInv1 extends \Magento\Framework\Logger\Handler\Base
{
    const RIKI_IMPORT_STOCK_LOG_FILE_NAME = '/var/log/importstockinv1.log';
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = self::RIKI_IMPORT_STOCK_LOG_FILE_NAME;

}