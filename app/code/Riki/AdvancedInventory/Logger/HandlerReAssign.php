<?php
namespace Riki\AdvancedInventory\Logger;
use Monolog\Logger;

class HandlerReAssign extends \Magento\Framework\Logger\Handler\Base
{
    const RIKI_RE_ASSIGN_STOCK_LOG_FILE_NAME = '/var/log/advanced_inventory_re_assign.log';
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = self::RIKI_RE_ASSIGN_STOCK_LOG_FILE_NAME;

}