<?php
namespace Riki\ThirdPartyImportExport\Logger;

use Monolog\Logger;

class HandlerShippingCSV extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/shipping_delivery_complete_export.log';
}