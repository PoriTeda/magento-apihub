<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class Shipment
{
    const DEFAULT_LOCAL_SAVE = 'var/BI_EXPORT_SHIPMENT';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_shipment/csvexport_order_folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_shipment/csvexport_order_folder_report_ftp';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_shipment/csvexport_order_folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_shipment/shipment_last_time_cron_run';
    const LOGFILE = 'bi_export_shipment';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\ShipmentHelper
     */
    protected $_shipmentHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\Shipment\LoggerCSV
     */
    protected $_logger;

    /**
     * Shipment constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\ShipmentHelper $shipmentHelper
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\Shipment\LoggerCSV $logger
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\ShipmentHelper $shipmentHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\Shipment\LoggerCSV $logger
    ) {
        $this->_shipmentHelper = $shipmentHelper;
        $this->_logger = $logger;
    }

    /**
     * @return bool
     *
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /*provide logger for shipment helper*/
        $this->_shipmentHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_shipmentHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_shipmentHelper->initExport(
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
        try {
            $this->_shipmentHelper->exportProcess();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw $e;
        }
    }
}
