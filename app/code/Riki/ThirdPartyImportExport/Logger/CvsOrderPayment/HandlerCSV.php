<?php

namespace Riki\ThirdPartyImportExport\Logger\CvsOrderPayment;

use Monolog\Logger;

class HandlerCSV extends \Magento\Framework\Logger\Handler\Base
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
    protected $fileName = '/var/log/cvs_order_payment.log';
}