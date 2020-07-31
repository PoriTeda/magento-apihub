<?php
namespace Riki\Subscription\Logger;

class LoggerReplaceProduct extends \Monolog\Logger
{
    CONST LOGGER_SUBSCRIPTION_REPLACE_PRODUCT = 'loggersetting/subscriptionlogger/logger_replace_product_active';

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        if(!$om->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::LOGGER_SUBSCRIPTION_REPLACE_PRODUCT)){
            return true;
        }

        return $this->addRecord(static::INFO, $message, $context);
    }

}