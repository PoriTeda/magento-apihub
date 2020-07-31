<?php
namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class Inquiry
{
    const DEFAULT_LOCAL_SAVE = 'var/bi_inquiry';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_enquiry/csvexport_order_folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_enquiry/csvexport_order_folder_report_ftp';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_enquiry/csvexport_order_folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_enquiry/bi_last_run_to_cron';
    const LOGFILE = 'bi_export_enquiry';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\InquiryHelper
     */
    protected $_inqueryHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\Enquiry\LoggerCSV
     */
    protected $_logger;

    /**
     * Enquiry constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\InquiryHelper $inquiryHelper
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\Enquiry\LoggerCSV $logger
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\InquiryHelper $inquiryHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\Enquiry\LoggerCSV $logger
    ) {
        $this->_inqueryHelper = $inquiryHelper;
        $this->_logger = $logger;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        /*provide logger for rma helper*/
        $this->_inqueryHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_inqueryHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_inqueryHelper->initExport(
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
        $this->_inqueryHelper->exportProcess();
    }
}