<?php
namespace Riki\DelayPayment\Logger;

/**
 * Class Handler
 * @package Riki\DelayPayment\Logger
 */
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/delay_payment_cancel_authorize.log';
}
