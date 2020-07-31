<?php
namespace Riki\StockPoint\Logger;

class AutoAssignStockPointLogger extends \Monolog\Logger
{
    const CONFIG_PATH_LOG_TYPE_CRON_AUTO_ASSIGN_STOCK_POINT = 'subscriptioncourse/log_stockpoint/is_active_cron_auto_assign_stock_point';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * AutoAssignStockPointLogger constructor.
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
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = [])
    {
        if (!$this->scopeConfig->getValue(
            self::CONFIG_PATH_LOG_TYPE_CRON_AUTO_ASSIGN_STOCK_POINT,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        )) {
            return true;
        }

        return $this->addRecord(static::INFO, $message);
    }
}
