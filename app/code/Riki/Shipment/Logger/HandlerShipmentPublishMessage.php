<?php
namespace Riki\Shipment\Logger;
#class Logger
use Monolog\Logger;

class HandlerShipmentPublishMessage extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/riki_sales_shipment_publish_message.log';

}