<?php

namespace Riki\Subscription\Logger;


class Logger extends \Monolog\Logger
{
    const LOGGER_SUBSCRIPTION_ENABLE = 'loggersetting/subscriptionlogger/logger_edit_profile_active';

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        if(!$om->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::LOGGER_SUBSCRIPTION_ENABLE)){
            return true;
        }

        return $this->addRecord(static::INFO, $message, $context);
    }
}