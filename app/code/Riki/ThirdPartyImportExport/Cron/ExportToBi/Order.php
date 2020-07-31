<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class Order
{
    const DEFAULT_LOCAL_SAVE = 'var/BI_EXPORT_ORDER';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_order/csvexport_order_folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_order/csvexport_order_folder_report_ftp';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_order/csvexport_order_folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_order/csvexport_order_last_time_cron_run';
    const LOGFILE = 'bi_export_order';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\OrderHelper
     */
    protected $_orderHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\Order\LoggerCSV
     */
    protected $_logger;

    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\OrderHelper $orderHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\Order\LoggerCSV $logger
    ) {
        $this->_orderHelper = $orderHelper;
        $this->_logger = $logger;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        /*provide logger for order helper*/
        $this->_orderHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_orderHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_orderHelper->initExport(
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
        $this->_orderHelper->exportProcess();
    }
}