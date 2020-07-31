<?php
namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class GiftWrapping
{
    const DEFAULT_LOCAL_SAVE = 'var/bi_export_giftwrapping';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_gift_wrapping/csvexport_order_folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_gift_wrapping/csvexport_giftwrapping_folder_ftp_report';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_gift_wrapping/csvexport_order_folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_gift_wrapping/bi_last_run_to_cron';
    const LOGFILE = 'bi_export_gift_wrapping';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GiftWrappingHelper
     */
    protected $_giftWrappingHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\GiftWrapping\LoggerCSV
     */
    protected $_logger;

    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GiftWrappingHelper $giftWrappingHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\GiftWrapping\LoggerCSV $logger
    ) {
        $this->_giftWrappingHelper = $giftWrappingHelper;
        $this->_logger = $logger;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        /*provide logger for gift wrapping helper*/
        $this->_giftWrappingHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_giftWrappingHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_giftWrappingHelper->initExport(
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
        $this->_giftWrappingHelper->exportProcess();
    }
}