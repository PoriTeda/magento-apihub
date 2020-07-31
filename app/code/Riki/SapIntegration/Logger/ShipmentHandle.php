<?php
namespace Riki\SapIntegration\Logger;

use Monolog\Logger;

class ShipmentHandle extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/sap_cron_shipment.log';
}