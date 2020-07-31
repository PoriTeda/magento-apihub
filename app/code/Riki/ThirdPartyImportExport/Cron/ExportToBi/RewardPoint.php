<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class RewardPoint
{
    const DEFAULT_LOCAL_SAVE = 'var/bi_reward_poin';
    const CONFIG_PATH_FTP = 'di_data_export_setup/data_cron_reward_point/folder_ftp';
    const CONFIG_PATH_REPORT = 'di_data_export_setup/data_cron_reward_point/folder_ftp_report';
    const CONFIG_PATH_LOCAL = 'di_data_export_setup/data_cron_reward_point/folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'di_data_export_setup/data_cron_reward_point/cron_last_time_run';
    const LOGFILE = 'bi_export_points';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\RewardPointHelper
     */
    protected $_rewardPointHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\RewardPoint\LoggerCSV
     */
    protected $_logger;

    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\RewardPointHelper $rewardPointHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\RewardPoint\LoggerCSV $loggerCSV
    ) {
       $this->_rewardPointHelper = $rewardPointHelper;
        $this->_logger = $loggerCSV;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        /*provide logger for reward point helper*/
        $this->_rewardPointHelper->setLogger($this->_logger);

        /*backup file log before export*/
        $this->_rewardPointHelper->backupLog(self::LOGFILE);

        /*generate export config*/
        $initConfig = $this->_rewardPointHelper->initExport(
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
        $this->_rewardPointHelper->exportProcess();
    }
}