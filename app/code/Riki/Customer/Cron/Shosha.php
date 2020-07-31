<?php

namespace Riki\Customer\Cron;

class Shosha
{
    const DEFAULT_LOCAL_SAVE = 'var/cedyna_shosha';
    const CONFIG_PATH_FTP = 'export_shosha/folder_setting/folder_ftp';
    const CONFIG_PATH_REPORT = '';
    const CONFIG_PATH_LOCAL = 'export_shosha/folder_setting/folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'export_shosha/folder_setting/cron_last_time_run';
    const LOGFILE = 'cedyna_export_shosha';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\ShoshaHelper
     */
    protected $_shoshaHelper;
    /**
     * @var \Riki\Customer\Logger\Shosha\LoggerCSV
     */
    protected $_logger;

    /**
     * Shosha constructor.
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\ShoshaHelper $shoshaHelper
     * @param \Riki\Customer\Logger\Shosha\LoggerCSV $loggerCSV
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\ShoshaHelper $shoshaHelper,
        \Riki\Customer\Logger\Shosha\LoggerCSV $loggerCSV
    ){
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
        $this->_shoshaHelper->exportProcess(true);
    }
}