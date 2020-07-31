<?php
namespace Riki\ThirdPartyImportExport\Logger\ExportToBi\Enquete;

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
    protected $fileName = '/var/log/bi_export_enquete.log';
}