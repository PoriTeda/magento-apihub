<?php
namespace Riki\ThirdPartyImportExport\Logger\ExportToBi\Rma;

class HandlerCSV extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/bi_export_rma.log';
}