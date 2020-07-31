<?php


namespace Riki\Subscription\Logger;

class LoggerPublishMessageQueue extends \Monolog\Logger
{
    const LOGGER_ORDER_QUEUE_ENABLE = 'loggersetting/subscriptionlogger/logger_publish_queue_active';

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        if(!$om->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::LOGGER_ORDER_QUEUE_ENABLE)){
            return true;
        }

        return $this->addRecord(static::INFO, $message, $context);
    }

}