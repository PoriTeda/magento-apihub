<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class RmaInvoice
{
    const DEFAULT_LOCAL_SAVE = 'var/bi_rma_invoice';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_rma_invoice/folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_rma_invoice/folder_ftp_report';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_rma_invoice/folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_rma_invoice/cron_last_time_run';
    const LOGFILE = 'bi_export_rma_invoice';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\RmaInvoiceHelper
     */
    protected $_rmaInvoiceHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\RmaInvoice\LoggerCSV
     */
    protected $_logger;

    /**
     * RmaInvoice constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\RmaInvoiceHelper $rmaInvoiceHelper
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\RmaInvoice\LoggerCSV $logger
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\RmaInvoiceHelper $rmaInvoiceHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\RmaInvoice\LoggerCSV $logger
    ) {
        $this->_rmaInvoiceHelper = $rmaInvoiceHelper;
        $this->_logger = $logger;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        /*provide logger for rma helper*/
        $this->_rmaInvoiceHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_rmaInvoiceHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_rmaInvoiceHelper->initExport(
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
        $this->_rmaInvoiceHelper->exportProcess();
    }
}
