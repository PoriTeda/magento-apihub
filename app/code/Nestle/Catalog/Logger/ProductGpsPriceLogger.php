<?php
namespace Nestle\Catalog\Logger;

class ProductGpsPriceLogger extends \Monolog\Logger
{
    const CONFIG_PATH_LOGGER_PRODUCT_GPS_PRICE_ENABLE_STATUS = 'loggersetting/catalog_logger/logger_product_gps_price_enable_status';

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
            self::CONFIG_PATH_LOGGER_PRODUCT_GPS_PRICE_ENABLE_STATUS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        )) {
            return true;
        }

        return parent::addRecord($level, $message, $context);
    }
}
