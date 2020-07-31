<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class Product
{
    const DEFAULT_LOCAL_SAVE = 'var/BI_EXPORT_PRODUCT';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_product/csvexport_order_folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_product/csvexport_order_folder_report_ftp';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_product/csvexport_order_folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_order/csvexport_order_last_time_cron_run';
    const LOGFILE = 'bi_export_product';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\ProductHelper
     */
    protected $_productHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\Product\LoggerCSV
     */
    protected $_logger;

    /**
     * Product constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\ProductHelper $productHelper
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\Product\LoggerCSV $logger
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\ProductHelper $productHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\Product\LoggerCSV $logger
    ) {
        $this->_productHelper = $productHelper;
        $this->_logger = $logger;
    }

    /**
     * execute the main function
     */
    public function execute()
    {
        /*provide logger for order helper*/
        $this->_productHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_productHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_productHelper->initExport(
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
        $this->_productHelper->exportProcess();
    }
}