<?php

namespace Bluecom\Paygent\Logger;

class IvrHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/paygent-ivr.log';

}