<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class FairSeasonalGift
{
    const DEFAULT_LOCAL_SAVE = 'var/bi_export_fair_seasonal_gift';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_fair_seasonal_gift/folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_fair_seasonal_gift/folder_ftp_report';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_fair_seasonal_gift/folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_fair_seasonal_gift/cron_last_time_run';
    const LOGFILE = 'bi_export_fair_seasonal_gift';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\FairAndSeasonalHelper
     */
    protected $_fairHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\FairSeasonalGift\LoggerCSV
     */
    protected $_logger;

    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\FairAndSeasonalHelper $fairHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\FairSeasonalGift\LoggerCSV $loggerCSV
    ){
        $this->_fairHelper = $fairHelper;
        $this->_logger = $loggerCSV;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        /*provide logger for fair helper*/
        $this->_fairHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_fairHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_fairHelper->initExport(
            self::DEFAULT_LOCAL_SAVE,
            self::CONFIG_PATH_LOCAL,
            self::CONFIG_PATH_FTP,
            self::CONFIG_PATH_REPORT,
            self::CONFIG_CRON_RUN_LAST_TIME
        );

        if (!$initConfig) {
            return false;
        }

        /*export process*/
        $this->_fairHelper->exportProcess();
    }


}