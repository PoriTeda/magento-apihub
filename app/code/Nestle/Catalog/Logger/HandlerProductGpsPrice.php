<?php
namespace Nestle\Catalog\Logger;

class HandlerProductGpsPrice extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/product_gps_price_logger.log';
}
