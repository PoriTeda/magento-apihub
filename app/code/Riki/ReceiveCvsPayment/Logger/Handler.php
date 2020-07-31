<?php
namespace Riki\ReceiveCvsPayment\Logger;
#class Logger
use Monolog\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/receive_cvs_payment_order.log';

}