<?php

namespace Riki\ThirdPartyImportExport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Filesystem\DirectoryList;

class CvsOrderPayment extends AbstractHelper
{
    const DS = '/';

    /**
     * Config is enable export cvs order payment
     */
    const CONFIG_IS_ENABLE = 'csv_order_payment_setup/se_common/is_enable';

    /**
     * Config setup ftp id
     */
    const CONFIG_SETUP_FTP_ID = 'setting_sftp/setup_ftp/ftp_id';

    /**
     * Config setup ftp port
     */
    const CONFIG_SETUP_FTP_PORT = 'setting_sftp/setup_ftp/ftp_port';

    /**
     * Config setup ftp user
     */
    const CONFIG_SETUP_FTP_USER = 'setting_sftp/setup_ftp/ftp_user';

    /**
     * Config setup ftp password
     */
    const CONFIG_SETUP_FTP_PASS = 'setting_sftp/setup_ftp/ftp_pass';

    /**
     * Config export file local
     */
    const CONFIG_CRON_FOLDER_LOCAL = 'csv_order_payment_setup/cvs_order_cron_location/folder_local';

    /**
     * Config export file ftp
     */
    const CONFIG_CRON_FOLDER_FTP = 'csv_order_payment_setup/cvs_order_cron_location/folder_ftp';

    /**
     * Config export file ftp - report
     */
    const CONFIG_CRON_FOLDER_REPORT = 'csv_order_payment_setup/cvs_order_cron_location/folder_ftp_report';

    /**
     * Get last time to cron run
     */
    const CONFIG_LAST_TIME_CRON = 'csv_order_payment_setup/cvs_order_cron_location/last_time_run_to_cron';

    /**
     * Config email enabled
     */
    const CONFIG_EMAIL_ENABLE = 'csv_order_payment_setup/cvs_order_send_email/email_enable';

    /**
     * Config send email alert
     */
    const CONFIG_EMAIL_ALERT = 'csv_order_payment_setup/cvs_order_send_email/email_alert';

    /**
     * Config send email template
     */
    const CONFIG_EMAIL_TEMPLATE = 'csv_order_payment_setup/cvs_order_send_email/email_template';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var string
     */
    protected $storeScope;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    protected $sftp;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var DateTime
     */
    protected $dateTime;
    protected $encryptor;
    /**
     * @var string
     */
    protected $pathRoot;

    /**
     * CvsOrderPayment constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Filesystem\Io\Sftp $sftp
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param File $file
     * @param DateTime $dateTime
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem $fileSystem,
        File $file,
        DateTime $dateTime,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->resourceConfig = $resourceConfig;
        $this->transportBuilder = $transportBuilder;
        $this->sftp = $sftp;
        $this->directoryList = $directoryList;
        $this->fileSystem = $fileSystem;
        $this->file = $file;
        $this->dateTime = $dateTime;
        $this->storeScope = ScopeInterface::SCOPE_WEBSITE;
        $this->encryptor = $encryptor;
    }

    /**
     * Get store
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Check enabled configuration
     *
     * @return mixed
     */
    public function isEnable()
    {
        $isEnabled = $this->scopeConfig->getValue(self::CONFIG_IS_ENABLE, $this->storeScope);
        return $isEnabled;
    }

    /**
     * Get ftp host
     *
     * @return mixed
     */
    public function getSftpHost()
    {
        $ftpId = $this->scopeConfig->getValue(self::CONFIG_SETUP_FTP_ID, $this->storeScope);
        return $ftpId;
    }

    /**
     * Get ftp port
     *
     * @return mixed
     */
    public function getSftpPort()
    {
        $ftpPort = $this->scopeConfig->getValue(self::CONFIG_SETUP_FTP_PORT, $this->storeScope);
        return $ftpPort;
    }

    /**
     * Get ftp user
     *
     * @return mixed
     */
    public function getSftpUser()
    {
        $ftpUser = $this->scopeConfig->getValue(self::CONFIG_SETUP_FTP_USER, $this->storeScope);
        return $ftpUser;
    }

    /**
     * Get ftp pass
     *
     * @return mixed
     */
    public function getSftpPass()
    {
        $ftpPass = $this->scopeConfig->getValue(self::CONFIG_SETUP_FTP_PASS, $this->storeScope);
        return $ftpPass;
    }

    /**
     * Get local export
     *
     * @return mixed
     */
    public function getLocalPathExport()
    {
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_CRON_FOLDER_LOCAL, $this->storeScope);
        return $LocalPath;
    }

    /**
     * Get sFtp export
     *
     * @return mixed
     */
    public function getSFTPPathExport()
    {
        $sFtpPath = $this->scopeConfig->getValue(self::CONFIG_CRON_FOLDER_FTP, $this->storeScope);
        return $sFtpPath;
    }

    /**
     * Get Report sFtp export
     *
     * @return mixed
     */
    public function getReportPathExport()
    {
        $sFtpPath = $this->scopeConfig->getValue(self::CONFIG_CRON_FOLDER_REPORT, $this->storeScope);
        return $sFtpPath;
    }

    /**
     * Get last time cron run
     *
     * @return mixed
     */
    public function getLastRunToCron()
    {
        $LocalPath = $this->scopeConfig->getValue(self::CONFIG_LAST_TIME_CRON, 'default');
        return $LocalPath;
    }

    /**
     * Set last time cron run
     *
     * @param $time
     */
    public function setLastRunToCron($time)
    {
        $this->resourceConfig->saveConfig(self::CONFIG_LAST_TIME_CRON, $time, 'default', 0);
    }

    /**
     * Get email alert
     *
     * @return array|bool
     */
    public function getEmailAlert()
    {
        $emailAlert = $this->scopeConfig->getValue(self::CONFIG_EMAIL_ALERT, $this->storeScope);
        if ($emailAlert) {
            return explode(';', $emailAlert);
        }
        return false;
    }

    /**
     * Get email template
     *
     * @return mixed
     */
    public function getEmailTemplate()
    {
        $emailTemplate = $this->scopeConfig->getValue(self::CONFIG_EMAIL_TEMPLATE, $this->storeScope);
        return $emailTemplate;
    }

    /**
     * Get sender email
     *
     * @return mixed
     */
    public function getSenderEmail()
    {
        $senderEmail = $this->scopeConfig->getValue('trans_email/ident_support/email', $this->storeScope);
        return $senderEmail;
    }

    /**
     * Get sender name
     *
     * @return mixed
     */
    public function getSenderName()
    {
        $senderName = $this->scopeConfig->getValue('trans_email/ident_support/name', $this->storeScope);
        return $senderName;
    }

    /**
     * Generate template
     *
     * @param $emailTemplateVariables
     * @return $this
     * @throws \Magento\Framework\Exception\MailException
     */
    public function generateTemplate($emailTemplateVariables)
    {
        $senderInfo = [
            'name' => $this->getSenderName(), 'email' => $this->getSenderEmail()
        ];

        $emailTo = $this->getEmailAlert();

        if (!$emailTo) {
            throw new \Magento\Framework\Exception\MailException(
                __('Recipient is empty.')
            );
        }

        $this->transportBuilder->setTemplateIdentifier($this->getEmailTemplate())->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId()
            ]
        )
        ->setTemplateVars($emailTemplateVariables)
        ->setFrom($senderInfo)
        ->addTo($emailTo);
        return $this;
    }

    /**
     * Send email export
     *
     * @param $emailTemplateVariables
     */
    public function sendMailShipmentExporting($emailTemplateVariables)
    {
        $this->inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables);
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    /**
     * Get config
     *
     * @param $path
     * @return mixed
     */
    public function getConfig($path)
    {
        $config = $this->scopeConfig->getValue($path, $this->storeScope);
        return $config;
    }

    /**
     * Open Ftp
     *
     * @param $log
     */
    public function openFtp($log)
    {
        $host = $this->getSftpHost();
        $port = $this->getSftpPort();
        $username = $this->getSftpUser();
        $password = $this->encryptor->decrypt($this->getSftpPass());

        try {
            $this->sftp->open(
                array (
                    'host' => $host . ':' . $port,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 300
                )
            );
            $this->pathRoot = $this->sftp->pwd();
        } catch (\Exception $e) {
            $log->info($e->getMessage());
            return;
        }
    }

    /**
     * CD folder ftp
     *
     * @param $location
     * @param $log
     * @return string
     */
    public function cdFolderFtp($location, $log)
    {
        // Create file on ftp
        $dirList = explode('/', $location);
        foreach ($dirList as $dir) {
            if ($dir != '') {
                if (!$this->sftp->cd($dir)) {
                    try {
                        $this->sftp->mkdir('/' . $dir);
                    } catch (\Exception $e) {
                        $log->info($e->getMessage());
                    }
                }
                try {
                    $this->sftp->cd($dir);
                } catch(\Exception $e) {
                    $log->info($e->getMessage());
                }
            }
        }

        return $this->sftp->pwd();
    }

    /**
     * Closed sftp
     */
    public function closeFtp()
    {
        $this->sftp->close();
    }

    /**
     * Move file to Ftp
     *
     * @param string $object
     * @param $pathLocalTemp
     * @param $pathSave
     * @param $pathFtp
     * @param $log
     */
    public function MoveFileToFtp($object = '', $pathLocalTemp, $pathSave, $pathFtp, $log, $pwdReport = '')
    {
        $bool1 = $bool2 = True;
        $rootDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->openFtp($log);
        $pwd = $this->cdFolderFtp($pathFtp,$log);

        // List file in folder tmp
        $readFile = $this->fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $listFile = $readFile->read(self::DS.$pathLocalTemp.self::DS);
        $totalFile = count($listFile);
        if ($totalFile) {
            try {
                if($pwdReport) {
                    $this->sftp->cd($this->pathRoot);
                    $pwdReport = $this->cdFolderFtp($pwdReport,$log);
                }

                foreach ($listFile as $path) {
                    // Move file
                    $nameArray = explode(self::DS,$path);
                    $fileName = $nameArray[count($nameArray) - 1];
                    if(!$this->sftp->write($pwd.self::DS.$fileName, $rootDir.self::DS.$path)){
                        $bool1 = false;
                    }
                    if($pwdReport){
                        if(!$this->sftp->write($pwdReport.self::DS.$fileName, $rootDir.self::DS.$path)){
                            $bool2 = false;
                        }
                    }
                    if ($bool1 && $bool2) {
                        $log->info('Upload '.$fileName.' to FTP successfully');
                        $oldFile = $rootDir.self::DS.$pathLocalTemp.self::DS.$fileName;
                        $moveFile = $rootDir.self::DS.$pathSave.self::DS.$fileName;
                        if ($this->file->isExists($oldFile)) {
                            $this->file->rename($oldFile,$moveFile);
                        }
                    } else {
                        $log->info('Upload '.$fileName.' to FTP fail');
                    }
                }
                $log->info('Summary of '.$totalFile.' '.$object.' export successfully');
            } catch (\Exception $e) {
                $log->info($e->getMessage());
            }
        }
        $this->closeFtp();
    }

    /**
     * Backup log
     *
     * @param $name
     * @param $log
     */
    public function backupLog($name, $log)
    {
        $varDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $fileSystem = new File();
        $backupFolder = DS . 'log' . DS . 'CvsOrderPayment';
        $localPath = $varDir . $backupFolder;

        if (!$fileSystem->isDirectory($localPath)) {
            if (!$fileSystem->createDirectory($localPath)) {
                $log->info(__('Can not create dir file') . $localPath);
                return;
            }
        }
        $fileLog = $varDir . DS . 'log' . DS . $name . '.log';
        $newLog = $varDir . DS . $backupFolder . DS . $name . '_' . $this->dateTime->date().'.log';
        if ($fileSystem->isWritable($localPath) && $fileSystem->isExists($fileLog)) {
            $fileSystem->rename($fileLog,$newLog);
        }
    }

    /**
     * Sent email
     *
     * @param $nameLog
     * @param $log
     * @return bool
     */
    public function sentMail($nameLog, $log)
    {
        $isEnable = $this->scopeConfig->getValue(self::CONFIG_EMAIL_ENABLE, $this->storeScope);
        if (!$isEnable) {
            return false;
        }

        // Sent mail file log
        $log->info('Sending notification emails ....');
        $reader = $this->fileSystem->getDirectoryRead(DirectoryList::ROOT);
        $contentLog = $reader->openFile(DS . 'var' . DS . 'log' . DS . $nameLog.'.log','r')->readAll();
        $emailVariable = ['logContent'=> $contentLog];
        try {
            $this->sendMailShipmentExporting($emailVariable);
            $log->info(__('Send notification email success.'));
        } catch (\Exception $e) {
            $log->info(__('Send notification email failed: %1', $e->getMessage()));
        }

    }

    /**
     * Get time by Utc
     *
     * @return string
     */
    public function getTimeByUtc(){
        $dateTime = new \DateTime('', new \DateTimeZone('UTC'));
        return $dateTime->format("Y-m-d H:i:s");
    }
}