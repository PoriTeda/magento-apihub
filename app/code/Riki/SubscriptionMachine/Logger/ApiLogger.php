<?php

namespace Riki\SubscriptionMachine\Logger;

class ApiLogger extends \Monolog\Logger
{
    const CONFIG_PATH_LOGGER_SETTING_SUBSCRIPTION_MONTHLY_FEE = 'loggersetting/subscriptionlogger/monthly_fee_api';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * ApiLogger constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $name,
        $handlers = [],
        $processors = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = [])
    {
        if (!$this->scopeConfig->getValue(
            self::CONFIG_PATH_LOGGER_SETTING_SUBSCRIPTION_MONTHLY_FEE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        )) {
            return true;
        }

        return $this->addRecord(static::INFO, $message);
    }
}
