<?php

namespace Riki\StockPoint\Logger;

class StockPointLogger extends \Monolog\Logger
{
    /**
     * @var string
     */
    protected $nameConfig;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    const LOG_TYPE_DEBUG_SHOW_BUTTON = 'is_active_debug_show_button';
    const LOG_TYPE_REMOVE_FROM_BUCKET = 'is_active_remove_from_bucket';
    const LOG_TYPE_NOTIFY_DATA_SHOW_MAP = 'is_active_notify_data_show_map';
    const LOG_TYPE_DISCOUNT_RATE = 'is_active_discount_rate';
    const LOG_TYPE_REGISTER_DELIVERY = 'is_active_register_delivery';
    const LOG_TYPE_UPDATE_DELIVERY = 'is_active_update_delivery';
    const LOG_TYPE_CONFIRM_BUCKET_ORDER = 'is_active_confirm_bucket_order';
    const LOG_TYPE_STOCK_POINT_DELIVERY_STATUS = 'is_active_stock_point_delivery_status';
    const LOG_TYPE_DEACTIVATE_STOCK_POINT = 'is_active_deactivate_stockpoint';
    const LOG_TYPE_CRON_SEND_BUCKET_ORDER = 'is_active_cron_send_bucket_order';

    /**
     * StockPointLogger constructor.
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
        if (empty($context) || !isset($context["type"])) {
            return true;
        }
        $this->nameConfig = 'subscriptioncourse/log_stockpoint/'.$context["type"];

        if (!$this->scopeConfig->getValue($this->nameConfig)) {
            return true;
        }
        return $this->addRecord(static::INFO, $message, $context);
    }
}
