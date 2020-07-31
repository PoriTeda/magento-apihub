<?php
namespace Riki\SubscriptionCutOffEmail\Logger;

class SendCutOffEmailLogger extends \Monolog\Logger
{
    const CONFIG_PATH_LOG_TYPE_CRON_SEND_CUT_OFF_EMAIL = 'subscriptioncourse/cutoffdate/is_active_cron_send_cut_off_email';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * SendCutOffEmailLogger constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        string $name,
        $handlers = [],
        $processors = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Adds a log record.
     *
     * @param integer $level
     * @param string $message
     * @param array $context
     * @return boolean
     */
    public function addRecord($level, $message, array $context = [])
    {
        if (!$this->scopeConfig->getValue(
            self::CONFIG_PATH_LOG_TYPE_CRON_SEND_CUT_OFF_EMAIL,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        )) {
            return true;
        }

        return parent::addRecord($level, $message, $context);
    }
}
