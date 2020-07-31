<?php

namespace Riki\Subscription\Logger;

class DelayPayment extends \Monolog\Logger
{
    const LOGGER_ENABLE_CONFIG_PATH = 'loggersetting/subscriptionlogger/delay_payment';

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        if (!$om->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue(
            self::LOGGER_ENABLE_CONFIG_PATH
        )) {
            return true;
        }

        return $this->addRecord(static::INFO, $message, $context);
    }
}