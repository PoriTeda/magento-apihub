<?php


namespace Riki\Subscription\Logger;

class LoggerOrder extends \Monolog\Logger
{
    const LOGGER_SUBSCRIPTION_ORDER_ENABLE = 'loggersetting/subscriptionlogger/logger_order_active';

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        if(!$om->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::LOGGER_SUBSCRIPTION_ORDER_ENABLE)){
            return true;
        }

        return $this->addRecord(static::INFO, $message, $context);
    }
}