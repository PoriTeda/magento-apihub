<?php
namespace Riki\AdvancedInventory\Logger;


class LoggerReAssign extends \Monolog\Logger
{
    const LOGGER_RE_ASSIGN_ENABLE = 'loggersetting/advancedinventorylogger/logger_re_assign_active';

    /**
     * Adds a log record.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addRecord($level, $message, array $context = array())
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        if(!$om->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::LOGGER_RE_ASSIGN_ENABLE)){
            return true;
        }

        return parent::addRecord($level, $message, $context);
    }
}