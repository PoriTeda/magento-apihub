<?php
namespace Riki\SapIntegration\Logger;

use Monolog\Logger;

class RmaHandle extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/sap_cron_rma.log';
}