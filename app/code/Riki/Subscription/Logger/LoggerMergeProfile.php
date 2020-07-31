<?php

namespace Riki\Subscription\Logger;

/**
 * Class LoggerStateProfile
 * @package Riki\Subscription\Logger
 */
class LoggerMergeProfile extends \Monolog\Logger
{
    const LOGGER_SUBSCRIPTION_MERGE_PROFILE = 'loggersetting/subscriptionlogger/logger_merge_profile_active';


    /**
     * @return bool
     */
    public function isActive(){

        $om = \Magento\Framework\App\ObjectManager::getInstance();

        if($om->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::LOGGER_SUBSCRIPTION_MERGE_PROFILE)){
            return true;
        }

        return false;
    }

}