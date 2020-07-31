<?php

namespace Riki\ThirdPartyImportExport\Logger\ExportToBi\InvoiceSaleShipment;

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
    protected $fileName = '/var/log/bi_export_invoice_sale_shipment.log';
}