<?php
namespace Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile;

class LoggerCSV extends \Monolog\Logger
{
    const LOGGER_BI_EXPORT_SUBSCRIPTION_ENABLE = 'loggersetting/subscriptionlogger/logger_bi_export_subscription_active';

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        if(!$om->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue(self::LOGGER_BI_EXPORT_SUBSCRIPTION_ENABLE)){
            return true;
        }
        return $this->addRecord(static::INFO, $message, $context);
    }
}