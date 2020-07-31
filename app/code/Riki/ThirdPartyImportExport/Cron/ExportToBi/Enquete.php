<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class Enquete
{
    const DEFAULT_LOCAL_SAVE = 'var/bi_export_enquete';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_enquete/csvexport_order_folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_enquete/csvexport_enquete_folder_report_ftp';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_enquete/csvexport_order_folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_enquete/bi_last_run_to_cron';
    const LOGFILE = 'bi_export_enquete';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\EnqueteHelper
     */
    protected $_enqueteHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\Enquete\LoggerCSV
     */
    protected $_logger;

    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\EnqueteHelper $enqueteHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\Enquete\LoggerCSV $logger
    ) {
        $this->_enqueteHelper = $enqueteHelper;
        $this->_logger = $logger;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        /*provide logger for enquete helper*/
        $this->_enqueteHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_enqueteHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_enqueteHelper->initExport(
            self::DEFAULT_LOCAL_SAVE,
            self::CONFIG_PATH_LOCAL,
            self::CONFIG_PATH_FTP,
            self::CONFIG_PATH_REPORT,
            self::CONFIG_CRON_RUN_LAST_TIME
        );

        /*prepare data and folder for export*/
        if (!$initConfig) {
            return false;
        }

        /*export process*/
        $this->_enqueteHelper->exportProcess();
    }
}