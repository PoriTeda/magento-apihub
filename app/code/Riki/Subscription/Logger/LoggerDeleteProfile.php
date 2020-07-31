<?php

namespace Riki\Subscription\Logger;

/**
 * Class LoggerStateProfile
 * @package Riki\Subscription\Logger
 */
class LoggerDeleteProfile extends \Monolog\Logger
{
    const LOGGER_SUBSCRIPTION_STATE_PROFILE = 'loggersetting/subscriptionlogger/logger_delete_subscription_profile';


    /**
     * @return bool
     */
    public function isActive(){

        $om = \Magento\Framework\App\ObjectManager::getInstance();

        if($om->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::LOGGER_SUBSCRIPTION_STATE_PROFILE)){
            return true;
        }

        return false;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profileModelTemp
     * @param array $context
     * @return bool
     */
    public function infoProfileTempDeleted(\Riki\Subscription\Model\Profile\Profile $profileModelTemp, array $context = array())
    {
        if(!$this->isActive()){
          return false;
        }

        if($profileModelTemp){
            $aInfoProfileTemp=  [
                'temp_profile_id' => $profileModelTemp->getLinkedProfileId(),
                'type' => $profileModelTemp->getChangeType()
            ];
            $message = 'profile_tmp_deleted : '.\Zend_Json_Encoder::encode($aInfoProfileTemp);
            return $this->addRecord(static::INFO, $message, $context);
        }
    }

}