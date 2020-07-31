<?php
namespace Riki\ThirdPartyImportExport\Logger\ExportToBi\FairSeasonalGift;

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
    protected $fileName = '/var/log/bi_export_fair_seasonal_gift.log';
}