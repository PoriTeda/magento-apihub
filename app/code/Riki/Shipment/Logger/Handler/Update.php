<?php
namespace Riki\Shipment\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Update extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/shipment_update.log';

}
