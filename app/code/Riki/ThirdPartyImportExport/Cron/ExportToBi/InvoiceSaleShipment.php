<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class InvoiceSaleShipment
{
    const LOCK_INTERVAL = 7200;
    const LOCK_PATH = 'var/BIEXPORTINVOICESALESHIPMENT';
    const DEFAULT_LOCAL_SAVE = 'var/bi_invoice_shipment';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_invoice_sale_shipment/folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_invoice_sale_shipment/folder_ftp_report';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_invoice_sale_shipment/folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_invoice_sale_shipment/bi_last_run_to_cron';
    const LOGFILE = 'bi_export_invoice_sale_shipment';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\InvoiceSaleShipmentHelper
     */
    protected $_invoiceShipmentHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper
     */
    protected $_fileHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\InvoiceSaleShipment\LoggerCSV
     */
    protected $_logger;

    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\InvoiceSaleShipmentHelper $invoiceShipmentHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileExportHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\InvoiceSaleShipment\LoggerCSV $logger
    ) {
        $this->_invoiceShipmentHelper = $invoiceShipmentHelper;
        $this->_logger = $logger;
        $this->_fileHelper = $fileExportHelper;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        $this->initExport();
        /*provide logger for shipment helper*/
        $this->_invoiceShipmentHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_invoiceShipmentHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_invoiceShipmentHelper->initExport(
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
        $this->_invoiceShipmentHelper->exportProcess();

        /*delete lock file after export success*/
        $this->deleteLockFile();

    }

    protected function initExport()
    {
        $baseDir = $this->_fileHelper->getRootDirectory();

        /*flag to check tmp folder can create new dir or is writable*/
        $validateLockFolder = $this->validateLockFolder($baseDir. DS .self::LOCK_PATH);

        if ($validateLockFolder) {
            /*tmp file to ensure that system do not run same mulit process at the same time*/
            $lockFile = $this->getLockFile();
            if ($this->_fileHelper->isExists($lockFile)) {
                $lockCreateTime = filemtime($this->_fileHelper->getAbsolutePath($lockFile));
                $currentTime = time();
                if ($currentTime - $lockCreateTime < self::LOCK_INTERVAL) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please wait, system have a same process is running and haven’t finish yet.')
                    );
                } else {
                    $this->deleteLockFile();
                    $this->_fileHelper->createFile($lockFile);
                }
            } else {
                $this->_fileHelper->createFile($lockFile);
            }
        }
    }

    /**
     * Get lock file
     *      this lock file is used to tracking that system have same process is running
     *
     * @return string
     */
    public function getLockFile()
    {
        return self::LOCK_PATH . DS . '.lock';
    }

    /**
     * validate lock folder
     *
     * @param $path
     * @return bool
     */
    public function validateLockFolder($path)
    {
        $this->_fileHelper->validateExportFolder($path);
        return true;
    }

    /**
     * Delete lock file
     */
    public function deleteLockFile()
    {
        $this->_fileHelper->deleteFile($this->getLockFile());
    }
}
