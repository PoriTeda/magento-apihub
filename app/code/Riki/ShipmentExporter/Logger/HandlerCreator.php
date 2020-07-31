<?php
namespace Riki\ShipmentExporter\Logger;
#class Logger
use Monolog\Logger;

class HandlerCreator extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/shipment_creator.log';

}