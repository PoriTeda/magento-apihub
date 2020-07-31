<?php


namespace Riki\Subscription\Logger;

class LoggerAddProductToProfile extends \Monolog\Logger
{
    const LOGGER_ADD_PRODUCT_TO_PROFILE_ENABLE = 'loggersetting/subscriptionlogger/logger_add_product_to_profile';

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        if(!$om->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::LOGGER_ADD_PRODUCT_TO_PROFILE_ENABLE)){
            return true;
        }

        return $this->addRecord(static::INFO, $message, $context);
    }

}