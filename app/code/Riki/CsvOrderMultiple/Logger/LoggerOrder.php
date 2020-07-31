<?php

namespace Riki\CsvOrderMultiple\Logger;

class LoggerOrder extends \Monolog\Logger
{
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        return $this->addRecord(static::INFO, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     */
    public function logSuccess($message, array $context = array())
    {
        return $this->addRecord(static::INFO, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     */
    public function logError($message, array $context = array())
    {
        return $this->addRecord(static::ERROR, $message, $context);
    }

}