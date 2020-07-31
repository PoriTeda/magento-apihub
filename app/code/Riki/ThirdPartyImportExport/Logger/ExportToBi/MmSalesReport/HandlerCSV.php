<?php
namespace Riki\ThirdPartyImportExport\Logger\ExportToBi\MmSalesReport;

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
    protected $fileName = '/var/log/bi_export_mm_report_sales.log';
}