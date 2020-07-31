<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class Rma
{
    const DEFAULT_LOCAL_SAVE = 'var/bi_rma';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_rma/folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_rma/folder_ftp_report';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_rma/folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_rma/cron_last_time_run';
    const LOGFILE = 'bi_export_rma';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\RmaHelper
     */
    protected $_rmaHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\Rma\LoggerCSV
     */
    protected $_logger;

    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\RmaHelper $rmaHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\Rma\LoggerCSV $logger
    ) {
        $this->_rmaHelper = $rmaHelper;
        $this->_logger = $logger;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        /*provide logger for rma helper*/
        $this->_rmaHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_rmaHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_rmaHelper->initExport(
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
        $this->_rmaHelper->exportProcess();
    }
}