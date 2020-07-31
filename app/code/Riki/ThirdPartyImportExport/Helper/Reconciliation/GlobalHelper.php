<?php
namespace Riki\ThirdPartyImportExport\Helper\Reconciliation;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Encryption\EncryptorInterface;
class GlobalHelper extends AbstractHelper
{
    const DS = '/';
    /**
     * Bi Data exporter configuration : enable/disable
     */
    const CONFIG_SE_ENABLE = 'reconciliation/common/reconciliation_export_enable';
    /**
     * ftp ip
     */
    const CONFIG_SE_FTP_IP = 'setting_sftp/setup_ftp/ftp_id';
    /**
     * ftp port
     */
    const CONFIG_SE_FTP_PORT = 'setting_sftp/setup_ftp/ftp_port';
    /**
     *  ftp user
     */
    const CONFIG_SE_FTP_USER = 'setting_sftp/setup_ftp/ftp_user';
    /**
     * ftp pass
     */
    const CONFIG_SE_FTP_PASS = 'setting_sftp/setup_ftp/ftp_pass';
    /**
     * enable and disable sentmail
     */
    const CONFIG_ENABLE_MAILCONFIG_ENABLE_MAIL = 'reconciliation/seemail/reconciliation_email_enable';

    /**
     * template mail
     */
    const CONFIG_SE_EMAIL_TEMPLATE = 'reconciliation/seemail/reconciliation_email_template';
    /**
     * email to sent
     */
    const CONFIG_SE_EMAIL_ALERT = 'reconciliation/seemail/reconciliation_email_alert';
    /**
     * Shipping exporter configuration : sftp export
     */
    const XML_PATH_FTP_CSV = 'reconciliation/data_cron_reconciliation/csvexport_folder_ftp';
    const XML_PATH_FTP_REPORT_CSV = 'reconciliation/data_cron_reconciliation/csvexport_folder_ftp_report';
    /**
     * Shipping exporter configuration :  export local
     */
    const XML_PATH_LOCAL_CSV = 'reconciliation/data_cron_reconciliation/csvexport_folder_local';

    const XML_CONFIG_LAST_CRON = 'reconciliation/data_cron_reconciliation/reconciliation_last_run_to_cron';
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;
    
    /**
     * @var DateTime
     */
    protected $_datetime;
    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    protected $_sftp;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var File
     */
    protected $_file;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_resourceConfig;
    /**
     * @var string
     */
    protected $pathRoot;
    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;
    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        DateTime $dateTime,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        File $file,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        EncryptorInterface $encryptor
    ) {
        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
        $this->_scopeConfig = $context;
        $this->_storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_datetime =$dateTime;
        $this->_sftp = $sftp;
        $this->_filesystem = $filesystem;
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_resourceConfig = $resourceConfig;
        $this->_encryptor = $encryptor;
        parent::__construct($context);
    }

    /**
     * Return store
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }
    /**
     * Check whether or not the module output is enabled in Configuration
     *
     * @return bool
     */
    public function isEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $isEnabled = $this->scopeConfig->getValue(self::CONFIG_SE_ENABLE, $storeScope);
        return $isEnabled;
    }
    /**
     * Get order paging value config
     *
     * @return mixed
     */
    public function getSftpHost()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $fptId  = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_IP, $storeScope);
        return $fptId;
    }

    /**
     * @return mixed
     */
    public function getSftpPort()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $ftpPort = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_PORT,$storeScope);
        return $ftpPort;
    }

    /**
     * @return mixed
     */
    public function getSftpUser()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $ftpUser = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_USER,$storeScope);
        return $ftpUser;
    }
    /**
     * @return mixed
     */
    public function getSftpPass()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $ftpPass = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_PASS,$storeScope);
        return $this->_encryptor->decrypt($ftpPass);

    }
    /**
     * @return mixed
     */
    public function getEmailAlert()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $emailAlert = $this->scopeConfig->getValue(self::CONFIG_SE_EMAIL_ALERT,$storeScope);
        return @explode(';',$emailAlert);
    }
    /**
     * @return mixed
     */
    public function getEmailTemplate()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $template = $this->scopeConfig->getValue(self::CONFIG_SE_EMAIL_TEMPLATE, $storeScope);
        return $template;

    }
    /**
     * @return mixed
     */
    public function getSenderEmail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/email',$storeScope);
    }

    /**
     * @return mixed
     */
    public function getSenderName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/name',$storeScope);
    }
    /**
     * @param $emailTemplateVariables
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables)
    {
        $senderInfo = [
            'name' => $this->getSenderName() , 'email' => $this->getSenderEmail()
        ];
        $template =  $this->_transportBuilder->setTemplateIdentifier($this->getEmailTemplate())
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($this->getEmailAlert());
        return $this;
    }
    /**
     * @param $emailTemplateVariables
     */
    public function sendMailExporting($emailTemplateVariables)
    {
        $this->inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables);
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    /**
     * @param $log
     */
    public function openFtp($log){
        $host = $this->getSftpHost();
        $port = $this->getSftpPort();
        $username = $this->getSftpUser();
        $password = $this->getSftpPass();
        //connect ftp
        try {
            $this->_sftp->open(
                array (
                    'host' => $host.':'.$port,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 300
                )
            );
            $this->pathRoot = $this->_sftp->pwd();
        }catch (\Exception $e) {
            $log->info($e->getMessage());
            return;
        }
    }
    /**
     * @param $location
     * @param $log
     */
    public function cdFolderFtp($location,$log){
        //create file on ftp
        $dirList = explode('/', $location);
        foreach ($dirList as $dir) {
            if ($dir != '') {
                if (!$this->_sftp->cd($dir)) {
                    try {
                        $this->_sftp->mkdir('/' . $dir);
                    } catch (\Exception $e) {
                        $log->info($e->getMessage());
                    }
                }
                try {
                    $this->_sftp->cd($dir);
                } catch(\Exception $e) {
                    $log->info($e->getMessage());
                }
            }
        }
        return $this->_sftp->pwd();
    }

    public function closeFtp(){
        $this->_sftp->close();
    }
    /**
     * @param string $objet
     * @param $pathLocalTemp
     * @param $pathSave
     * @param $pathFtp
     * @param $log
     */
    public function MoveFileToFtp($objet = '',$pathLocalTemp,$pathSave,$pathFtp,$log,$pwdReport = ''){
        $bool1 = $bool2 = True;
        $rootDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->openFtp($log);
        $pwd = $this->cdFolderFtp($pathFtp,$log);
        //list file in folder tmp
        $readfile = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $listfile = $readfile->read(self::DS.$pathLocalTemp.self::DS);
        $totalFile = count($listfile);
        if($totalFile){
            try {
                if($pwdReport) {
                    $this->_sftp->cd($this->pathRoot);
                    $pwdReport = $this->cdFolderFtp($pwdReport,$log);
                }

                foreach($listfile as $path){
                    // Move file
                    $nameArray = explode(self::DS,$path);
                    $fileName = $nameArray[count($nameArray) - 1];
                    if(!$this->_sftp->write($pwd.self::DS.$fileName, $rootDir.self::DS.$path)){
                        $bool1 = false;
                    }
                    if($pwdReport){
                        if(!$this->_sftp->write($pwdReport.self::DS.$fileName, $rootDir.self::DS.$path)){
                            $bool2 = false;
                        }
                    }
                    if ($bool1 && $bool2) {
                        $log->info("Upload ".$fileName." to FTP successfully");
                        $oldFile = $rootDir.self::DS.$pathLocalTemp.self::DS.$fileName;
                        $moveFile = $rootDir.self::DS.$pathSave.self::DS.$fileName;
                        if ($this->_file->isExists($oldFile)) {
                            $this->_file->rename($oldFile,$moveFile);
                        }
                    } else {
                        $log->info("Upload ".$fileName." to FTP fail");
                    }
                }
                $log->info("summary of  ".$totalFile." ".$objet." export successfully");
            } catch (\Exception $e) {
                $log->info($e->getMessage());
            }
        }
        $this->closeFtp();
    }
    /**
     * @param $needDate
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function backupLog($name,$log)
    {
        $varDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $fileSystem = new File();
        $backupFolder = DS.'log'.DS.'BiExportData';
        $localPath = $varDir.$backupFolder;

        if(!$fileSystem->isDirectory($localPath)){
            if(!$fileSystem->createDirectory($localPath)){
                $log->info(__('Can not create dir file').$localPath);
                return;
            }
        }
        $fileLog = $varDir.DS.'log'.DS.$name.'.log';
        $newLog = $varDir. DS .$backupFolder. DS . $name . '_'.$this->_datetime->date().'.log';
        if($fileSystem->isWritable($localPath) && $fileSystem->isExists($fileLog))
        {
            $fileSystem->rename($fileLog,$newLog);
        }
    }

    /**
     * @param $namelog
     * @param $log
     */
    public function sentMail($namelog,$log){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $isEnable = $this->scopeConfig->getValue(self::CONFIG_ENABLE_MAILCONFIG_ENABLE_MAIL,$storeScope);
        if(!$isEnable){
            return false;
        }
        //sent mail file log
        $log->info("Sending notification emails ....");
        $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $contentLog=  $reader->openFile(DS.'var'.DS.'log' . DS.$namelog.'.log','r')->readAll();
        $emailVariable = ['logContent'=> $contentLog];
        $this->sendMailExporting($emailVariable);
    }

    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }
    /**
     * @return mixed
     */
    public function getLocalPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::XML_PATH_LOCAL_CSV,$storeScope);
        return $LocalPath;
    }
    /**
     * @return mixed
     */
    public function getSFTPPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::XML_PATH_FTP_CSV,$storeScope);
        return $LocalPath;
    }
    /**
     * @return mixed
     */
    public function getSFTPPathReportExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::XML_PATH_FTP_REPORT_CSV,$storeScope);
        return $LocalPath;
    }

    /**
     * @param $time
     */
    public function setLastRunToCron($time)
    {
        $this->_resourceConfig->saveConfig(self::XML_CONFIG_LAST_CRON, $time, 'default', 0);
    }
    /**
     * @return mixed
     */
    public function getLastRunToCron(){
        $localPath = $this->scopeConfig->getValue(self::XML_CONFIG_LAST_CRON,'default');
        return $localPath;
    }
}