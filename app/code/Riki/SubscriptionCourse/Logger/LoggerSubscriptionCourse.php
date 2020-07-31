<?php

namespace Riki\SubscriptionCourse\Logger;

class LoggerSubscriptionCourse extends \Monolog\Logger
{
    const LOGGER_SUBSCRIPTION_COURSE_IMPORT_ENABLE = 'subscriptioncourse/course_import/log_active';

    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $name,
        $handlers = [],
        $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = [])
    {
        return $this->addRecord(static::INFO, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     */
    public function logSuccess($message, array $context = [])
    {
        return $this->addRecord(static::INFO, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     */
    public function logError($message, array $context = [])
    {
        return $this->addRecord(static::ERROR, $message, $context);
    }

    public function addRecord($level, $message, array $context = [])
    {
        if ($this->scopeConfig->getValue(self::LOGGER_SUBSCRIPTION_COURSE_IMPORT_ENABLE)) {
            return parent::addRecord($level, $message, $context);
        }
    }
}
