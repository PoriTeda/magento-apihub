<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class Shosha
{
    const DEFAULT_LOCAL_SAVE = 'var/bi_shosha';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_shosha/folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_shosha/folder_ftp_report';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_shosha/folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_shosha/cron_last_time_run';
    const LOGFILE = 'bi_export_shosha';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\ShoshaHelper
     */
    protected $_shoshaHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\Shosha\LoggerCSV
     */
    protected $_logger;

    /**
     * Shosha constructor.
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\ShoshaHelper $shoshaHelper
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\Shosha\LoggerCSV $loggerCSV
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\ShoshaHelper $shoshaHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\Shosha\LoggerCSV $loggerCSV
    ) {
        $this->_shoshaHelper = $shoshaHelper;
        $this->_logger = $loggerCSV;
    }


    /**
     * @return $this
     */
    public function execute()
    {
        /*provide logger for shosha helper*/
        $this->_shoshaHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_shoshaHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_shoshaHelper->initExport(
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
        $this->_shoshaHelper->exportProcess();
    }
}