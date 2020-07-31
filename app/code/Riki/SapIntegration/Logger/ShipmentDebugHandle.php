<?php
namespace Riki\SapIntegration\Logger;

use Monolog\Logger;

class ShipmentDebugHandle extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/sap_api_debug.log';
}