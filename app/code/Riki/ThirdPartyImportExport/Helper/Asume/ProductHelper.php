<?php
namespace Riki\ThirdPartyImportExport\Helper\Asume;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Encryption\EncryptorInterface;
class ProductHelper extends AbstractHelper
{
    const DS = '/';

    const XML_PATH_LOCAL_XML = 'amuse_data_export_setup/product_amuse_export/folder_local';

    const XML_PATH_FTP_XML = 'amuse_data_export_setup/product_amuse_export/folder_ftp';

    const CONFIG_SE_ENABLE = 'amuse_data_export_setup/secommon/asume_data_export_enable';

    const CONFIG_SE_FTP_IP = 'amuse_data_export_setup/seftp/data_ftp_id';

    const CONFIG_SE_FTP_PORT = 'amuse_data_export_setup/seftp/data_ftp_port';

    const CONFIG_SE_FTP_USER = 'amuse_data_export_setup/seftp/data_ftp_user';

    const CONFIG_SE_FTP_PASS = 'amuse_data_export_setup/seftp/data_ftp_pass';

    const CONFIG_ENABLE_MAILCONFIG_ENABLE_MAIL = 'amuse_data_export_setup/seemail/asume_data_mail_enable';

    const CONFIG_SE_EMAIL_TEMPLATE = 'amuse_data_export_setup/seemail/export_email_template';

    const CONFIG_SE_EMAIL_ALERT = 'amuse_data_export_setup/seemail/export_email_alert';

    /**
     * @var
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
     * @var
     */
    protected $_filesystem;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var
     */
    protected $_file;
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
        EncryptorInterface $encryptor
    ) {
        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
        $this->_scopeConfig = $context;
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_datetime =$dateTime;
        $this->_sftp = $sftp;
        $this->_filesystem = $filesystem;
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_encryptor = $encryptor;
        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
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
        $ftpPass = $this->_encryptor->decrypt($ftpPass);
        return $ftpPass;
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
    public function generateTemplate()
    {
        $senderInfo = [
            'name' => $this->getSenderName() , 'email' => $this->getSenderEmail()
        ];
        $this->_transportBuilder->setTemplateIdentifier($this->getEmailTemplate())
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars([])
            ->setFrom($senderInfo)
            ->addTo($this->getEmailAlert());
        return $this;
    }
    /**
     * @param $emailTemplateVariables
     */
    public function sendMailExporting()
    {
        $this->inlineTranslation->suspend();
        $this->generateTemplate();
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
             return true;
        }catch (\Exception $e) {
            $log->info($e->getMessage());
            // sent mail
            return false;
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
            if($dir != '') {
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
    public function MoveFileToFtp($objet = '',$pathLocalTemp,$pathSave,$pathFtp,$log){
        $rootDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $connected = $this->openFtp($log);
        if($connected){
            $this->cdFolderFtp($pathFtp,$log);
            //list file in folder tmp
            $readfile = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
            $listfile = $readfile->read(self::DS.$pathLocalTemp.self::DS);
            $totalFile = 0;
            $totalFile = count($listfile);
            if($totalFile){
                try {
                    foreach($listfile as $path){
                        $filename = '';
                        // Move file
                        $nameArray = explode(self::DS,$path);
                        $filename =   $nameArray[count($nameArray) - 1];
                        if($this->_sftp->write($this->_sftp->pwd() .self::DS . $filename , $rootDir.self::DS . $path)){
                            $log->info("Upload " . $filename . " to FTP successfully");
                            $oldFile = $rootDir . self::DS . $pathLocalTemp .self::DS. $filename;
                            $moveFile = $rootDir . self::DS . $pathSave . self::DS . $filename;
                            if($this->_file->isExists($oldFile)){
                                $this->_file->rename($oldFile,$moveFile);
                            }
                        }else{
                            $log->info("Upload " . $filename . " to FTP fail");
                            //Sent mail
                            $this->sentMail();
                            return false;
                        }
                    }
                    $log->info("summary of " . $totalFile . " " . $objet . " export successfully");
                } catch (\Exception $e) {
                    $log->info($e->getMessage());
                }
            }
            $this->closeFtp();
        }
    }
    /**
     * @param $needDate
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function backupLog($name,$log)
    {
        $varDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $fileSystem = new File();
        $backupFolder = DS.'log'.DS.'asume_bk';
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
    public function sentMail(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $isEnable = $this->scopeConfig->getValue(self::CONFIG_ENABLE_MAILCONFIG_ENABLE_MAIL,$storeScope);
        if(!$isEnable){
            return false;
        }
        $this->sendMailExporting();
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
        $localPath = $this->scopeConfig->getValue(self::XML_PATH_LOCAL_XML,$storeScope);
        return $localPath;
    }
    /**
     * @return mixed
     */
    public function getSFTPPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->scopeConfig->getValue(self::XML_PATH_FTP_XML,$storeScope);
        return $LocalPath;
    }
}