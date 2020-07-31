<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper;

class ExportHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_resourceConfig;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper
     */
    protected $_sftpHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper
     */
    protected $_fileHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper
     */
    protected $_configHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper
     */
    protected $_emailHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper
     */
    protected $_dateTimeColumnsHelper;
    /**
     * @var \Riki\Sales\Helper\ConnectionHelper
     */
    protected $_connectionHelper;

    /*config local path*/
    protected $_configLocalPath;

    /*config sftp path*/
    protected $_configSftpPath;

    /*config report path*/
    protected $_configReportPath;

    /*last time that cron run*/
    protected $_configLastTimeRun;

    /*csv processor*/
    protected $_csv;

    /*root directory*/
    protected $_baseDir;

    /*local path*/
    protected $_path;

    /*tmp path*/
    protected $_pathTmp;

    /*log object*/
    protected $_logger;

    /*current log file*/
    protected $_logFile;

    protected $configTimeZone;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        parent::__construct($context);
        $this->_dateTime = $dateTime;
        $this->_timezone = $timezone;
        $this->_resourceConfig = $resourceConfig;
        $this->_sftpHelper = $sftpHelper;
        $this->_fileHelper = $fileHelper;
        $this->_configHelper = $configHelper;
        $this->_emailHelper = $emailHelper;
        $this->_dateTimeColumnsHelper = $dateTimeColumnsHelper;
        $this->_connectionHelper = $connectionHelper;
    }

    /**
     * provide export config
     *
     * @param $defaultLocalPath
     * @param $configLocalPath
     * @param $configSftpPath
     * @param $configReportPath
     * @param $configLastTimeRun
     * @return bool
     */
    public function initExport(
        $defaultLocalPath,
        $configLocalPath,
        $configSftpPath,
        $configReportPath,
        $configLastTimeRun
    ) {
        /*check module is enabled*/
        if (!$this->_configHelper->isEnable()) {
            return false;
        }

        /*provide export config*/
        $this->initConfig($configLocalPath, $configSftpPath, $configLastTimeRun, $configReportPath);

        /*get root path dir*/
        $this->_baseDir = $this->_fileHelper->getRootDirectory();

        /*get local folder from config*/
        $this->_path = $this->getLocalFolder($defaultLocalPath);

        /*get local tmp folder*/
        $this->_pathTmp = $this->_path . '_tmp';

        /*flag to check tmp folder can create new dir or is writable*/
        $validateTmpFolder = $this->validateExportFolder($this->_baseDir . DS . $this->_pathTmp);

        /*flag to check tmp folder can create new dir or is writable*/
        $validateLocalFolder = $this->validateExportFolder($this->_baseDir . DS . $this->_path);

        if ($validateTmpFolder && $validateLocalFolder) {
            /*generate csv processor*/
            $this->_csv = new \Magento\Framework\File\Csv(
                new \Magento\Framework\Filesystem\Driver\File()
            );
            return true;
        }

        return false;
    }

    /**
     * @param $configLocalPath
     * @param $configSftpPath
     * @param $configLastTimeRun
     * @param string $reportPath
     * @return bool
     */
    public function initConfig($configLocalPath, $configSftpPath, $configLastTimeRun, $reportPath = '')
    {
        /*provide local folder config*/
        $this->_configLocalPath = $configLocalPath;

        /*provide sftp folder config*/
        $this->_configSftpPath = $configSftpPath;

        /*provide report folder config*/
        $this->_configReportPath = $reportPath;

        /*provide last time that cron run config*/
        $this->_configLastTimeRun = $configLastTimeRun;

        return true;
    }

    /**
     * get local export folder
     *
     * @param $defaultLocalPath
     * @return string
     */
    public function getLocalFolder($defaultLocalPath)
    {
        /*get local folder from config*/
        $localCsv = $this->getLocalPathExport();

        if (!$localCsv) {
            $localCsv = $defaultLocalPath;
        } else {
            if (trim($localCsv, -1) == DS) {
                $localCsv = str_replace(DS, '', $localCsv);
            }
        }

        return $localCsv;
    }

    /**
     * @return mixed
     */
    public function getLocalPathExport()
    {
        return $this->_configHelper->getConfig($this->_configLocalPath);
    }

    /**
     * @return mixed
     */
    public function getSFTPPathExport()
    {
        return $this->_configHelper->getConfig($this->_configSftpPath);
    }

    /**
     * @return mixed
     */
    public function getReportPathExport()
    {
        if (!empty($this->_configReportPath)) {
            return $this->_configHelper->getConfig($this->_configReportPath);
        } else {
            return $this->_configReportPath;
        }
    }

    /**
     * @return mixed
     */
    public function getLastRunToCron()
    {
        return $this->_configHelper->getConfig($this->_configLastTimeRun);
    }

    /**
     * @return mixed
     */
    public function getLastRunToCronDB()
    {
        $lastTimeCronRun = '';

        $connectionDefault = $this->_connectionHelper->getDefaultConnection();

        try {
            /*config table name*/
            $configTable = $connectionDefault->getTableName('core_config_data');

            $getLastTimeCronRun = $connectionDefault->select()->from(
                $configTable, 'value'
            )->where(
                'path = ?', $this->_configLastTimeRun
            )->limitPage(1, 1)->limit(1);

            $timeCronRun = $connectionDefault->fetchCol($getLastTimeCronRun);

            if (!empty($timeCronRun) && !empty($timeCronRun[0])) {
                $lastTimeCronRun = $timeCronRun[0];
            }

        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }

        if (empty($lastTimeCronRun)) {
            $lastTimeCronRun = $this->getLastRunToCron();
        }

        return $lastTimeCronRun;
    }

    /**
     * set last time that cron is run
     */
    public function setLastRunToCron()
    {
        $this->_resourceConfig->saveConfig(
            $this->_configLastTimeRun,
            $this->getTimeByUtc(),
            'default', 0
        );
    }

    /**
     * Provide logger object
     *
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
        $this->_logger->setTimezone(new \DateTimeZone($this->_timezone->getConfigTimezone()));
        $this->_sftpHelper->setLogger($this->_logger);
        $this->_emailHelper->setLogger($this->_logger);
    }

    /**
     * Set log file
     *
     * @param $logFile
     */
    public function setLogFile($logFile)
    {
        $this->_logFile = $logFile;
    }

    /**
     * Backup old log to new file and create new log file
     *
     * @param $logFile
     */
    public function backupLog($logFile)
    {
        $this->setLogFile($logFile);

        try {
            $this->_fileHelper->backupLog($logFile, $this->_timezone->date()->format('YmdHis'));
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    /**
     * validate export folder
     *
     * @param $path
     * @return bool
     */
    public function validateExportFolder($path)
    {
        try {
            $this->_fileHelper->validateExportFolder($path);
            return true;
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
        return false;
    }

    /**
     * Get connection helper
     *
     * @return \Riki\Sales\Helper\ConnectionHelper
     */
    public function getConnectionHelper()
    {
        return $this->_connectionHelper;
    }

    /**
     * Get datetime columns helper
     *
     * @return DateTimeColumnsHelper
     */
    public function getDateTimeColumnsHelper()
    {
        return $this->_dateTimeColumnsHelper;
    }

    /**
     * Get time by Utc
     *
     * @return string
     */
    public function getTimeByUtc()
    {
        $dateTime = new \DateTime('', new \DateTimeZone('UTC'));

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * send notification email
     */
    public function sentNotificationEmail()
    {
        /*get log content and send to email notification*/
        $logContent = $this->_fileHelper->getLogContent($this->_logFile);
        /*send email process*/
        $this->_emailHelper->sentNotificationEmail($logContent);
    }

    /**
     * Create local file for tmp folder
     * @param $exportData
     */
    public function createLocalFile($exportData)
    {
        foreach ($exportData as $key => $value) {

            $newFile = $this->_baseDir . DS . $this->_pathTmp . DS . $key;

            try {
                /*create csv file for tmp folder*/
                $this->_csv->saveData($newFile, $value);

                //remove bom header file
                $this->_fileHelper->removeBom($newFile);
                //NED-1507 log file creation
                $this->_logger->info("Save local file successfully: " . $newFile);
            } catch (\Exception $e) {
                $this->_logger->info("Could not save local file: " . $newFile . " error: " . $e->getMessage());
            }

        }
    }

    /**
     * Convert date time from UTC to config timezone
     *
     * @param $dateTime
     * @return string
     */
    public function convertToConfigTimezone($dateTime)
    {
        /*generate date time object with timezone is utc and time is dateTime param*/
        $columnTime = $this->_timezone->date(strtotime($dateTime), null, false);

        /*convert to config timezone*/
        $columnTime->setTimezone($this->getConfigTimeZone());

        /*return date time with config timezone*/
        return $columnTime->format('Y-m-d H:i:s');
    }

    /**
     * Get config timezone, avoid recalling in loop.
     * @return \DateTimeZone
     */
    public function getConfigTimeZone()
    {
        if (!$this->configTimeZone) {
            $this->configTimeZone = new \DateTimeZone($this->_timezone->getConfigTimezone());
        }

        return $this->configTimeZone;
    }
}