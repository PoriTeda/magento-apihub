<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper;

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
    const CONFIG_SE_ENABLE = 'di_data_export_setup/secommon/di_data_export_enable';

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
     * enable and disable send mail
     */
    const CONFIG_ENABLE_MAILCONFIG_ENABLE_MAIL = 'di_data_export_setup/seemail/shipmentexport_email_enable';

    /**
     * template mail
     */
    const CONFIG_SE_EMAIL_TEMPLATE = 'di_data_export_setup/seemail/shipmentexport_email_template';

    /**
     * email to sent
     */
    const CONFIG_SE_EMAIL_ALERT = 'di_data_export_setup/seemail/shipmentexport_email_alert';

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
     * @var string
     */
    protected $pathRoot;
    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;
    /**
     * GlobalHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param DateTime $dateTime
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\Io\Sftp $sftp
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param File $file
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
        \Magento\Framework\File\Csv $csv,
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
        $this->_csv  = $csv;
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
        $ftpPort = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_PORT, $storeScope);
        return $ftpPort;
    }

    /**
     * @return mixed
     */
    public function getSftpUser()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $ftpUser = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_USER, $storeScope);
        return $ftpUser;
    }

    /**
     * @return mixed
     */
    public function getSftpPass()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $ftpPass = $this->scopeConfig->getValue(self::CONFIG_SE_FTP_PASS, $storeScope);
        return $ftpPass;
    }

    /**
     * @return mixed
     */
    public function getEmailAlert()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $emailAlert = $this->scopeConfig->getValue(self::CONFIG_SE_EMAIL_ALERT, $storeScope);
        return @explode(';', $emailAlert);
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
        return $this->scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
    }

    /**
     * @return mixed
     */
    public function getSenderName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
    }

    /**
     * @param $emailTemplateVariables
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables)
    {
        $senderInfo = [
            'name' => $this->getSenderName(),
            'email' => $this->getSenderEmail()
        ];

        $this->_transportBuilder->setTemplateIdentifier($this->getEmailTemplate())
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* Here you can defile area and store of template for which you prepare it */
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
        $password = $this->_encryptor->decrypt($password);
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
        } catch (\Exception $e) {
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

    /**
     * Close FTP
     */
    public function closeFtp()
    {
        $this->_sftp->close();
    }

    /**
     * @param string $object
     * @param $pathLocalTemp
     * @param $pathSave
     * @param $pathFtp
     * @param $log
     */
    public function MoveFileToFtp($object = '', $pathLocalTemp, $pathSave, $pathFtp, $log, $pwdReport = '')
    {

        $bool1 = $bool2 = True;
        $rootDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->openFtp($log);
        $pwd = $this->cdFolderFtp($pathFtp,$log);

        // List file in folder tmp
        $readFile = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $listFile = $readFile->read(self::DS.$pathLocalTemp.self::DS);
        $totalFile = count($listFile);
        if ($totalFile) {
            try {
                if($pwdReport) {
                    $this->_sftp->cd($this->pathRoot);
                    $pwdReport = $this->cdFolderFtp($pwdReport,$log);
                }

                $totalValidFile = 0;

                foreach ($listFile as $path) {

                    /*check file is valid to update sftp*/
                    $isValidFile = $this->isValidFileToUploadSftp(self::DS.$path);

                    if (!$isValidFile) {
                        continue;
                    } else {
                        $totalValidFile++;
                    }

                    /* Move file */
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

                if ($totalValidFile) {
                    $log->info("summary of ".$totalValidFile." ".$object." export successfully");
                }

            } catch (\Exception $e) {
                $log->info($e->getMessage());
            }
        }
        $this->closeFtp();
    }

    /**
     * @param $nameFile
     * @param $aData
     */
    public function writeFileLocal($nameFile,$aData){

        if(count($aData) > 1){

            if(!$this->_file->isExists($nameFile)) {
                $this->_csv->saveData($nameFile, $aData);
            }
            else{
                $aDataOrigin = $this->_csv->getData($nameFile);
                if (count($aDataOrigin) > 1) {
                    array_shift($aData);

                    //remove duplicated profile origin
                    $aData = array_map('array_values', $aData);
                    $aData = array_merge($aDataOrigin, $aData);
                    $this->_csv->saveData($nameFile, $aData);
                }
            }
        }
    }

    /**
     * @param $dataOrigin
     * @param $dataUpdate
     * @param $configQueue
     * @return mixed
     */
    public function removeSubDuplicatedInfo($dataOrigin,$dataUpdate){

        $dataOriginHandle = $dataOrigin;
        $aColumns = array_shift($dataOriginHandle);

        if(in_array('subscription_profile.profile_id',$aColumns)){
            $searchProfileId = 'subscription_profile.profile_id';
        }
        else
        if(in_array('product.subscription_profile_profile_id',$aColumns)){
            $searchProfileId = 'product.subscription_profile_profile_id';
        }
        else
        if(in_array('shipment.profile_id',$aColumns)){
            $searchProfileId = 'shipment.profile_id';
        }
        else
        if(in_array('shipment_item.entity_id',$aColumns)){
            return [$aColumns];
        }

        if(!isset($searchProfileId)){
            return [$aColumns];
        }

        $dataOriginMapping = [];
        foreach($dataOriginHandle as $key =>  $itemDataOrigin){
            $aLine = [];
            foreach($aColumns as $keyColumn => $sColumn){
                $aLine[$sColumn] = $itemDataOrigin[$keyColumn];
            }
            $dataOriginMapping[$key] = $aLine;
        }

        $aProfileOriginId = array_column($dataOriginMapping,$searchProfileId);

        $dataUpdateMapping = [];
        foreach($dataUpdate as $key => $itemDataUpdate){
            $aLine = [];
            foreach($aColumns as $keyColumn => $sColumn){
                $aLine[$sColumn] = (isset($itemDataUpdate[$keyColumn])) ? $itemDataUpdate[$keyColumn] : "";
            }
            $dataUpdateMapping[$key] = $aLine;
        }

        $aProfileUpdateId = array_column($dataUpdateMapping,$searchProfileId);

        $aDuplicateProfileId = array_intersect($aProfileOriginId,$aProfileUpdateId);

        if(!empty($aDuplicateProfileId)){
            foreach($aDuplicateProfileId as $iProfileId){
                $listKeyProfileId =  array_keys($aProfileOriginId, $iProfileId);
                foreach($listKeyProfileId as $keyProfileId){
                    if(isset($dataOriginHandle[$keyProfileId])){
                        unset($dataOriginHandle[$keyProfileId]);
                    }
                }
            }
        }

        if(!empty($dataOriginHandle)){
            return array_merge([$aColumns],$dataOriginHandle);
        }
        else{
            return [$aColumns];
        }
    }
    /**
     * @param $pathLocalTemp
     * @param $pathSave
     * @param $filename
     * @param $pathFtp
     * @param $log
     */
    public function MoveOneFileToFtp($pathLocalTemp, $pathSave, $filename, $pathFtp, $log,$pwdReport = '')
    {
        $bool1 = $bool2 = True;
        $rootDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->openFtp($log);
        $pwd = $this->cdFolderFtp($pathFtp,$log);

        try {
            if($pwdReport) {
                $this->_sftp->cd($this->pathRoot);
                $pwdReport = $this->cdFolderFtp($pwdReport,$log);
            }

            if(!$this->_sftp->write($pwd.self::DS.$filename, $rootDir.self::DS.$pathLocalTemp.self::DS.$filename)){
                $bool1 = false;
            }

            if($pwdReport){
                if(!$this->_sftp->write($pwdReport.self::DS.$filename, $rootDir.self::DS.$pathLocalTemp.self::DS.$filename)){
                    $bool2 = false;
                }
            }
            if ($bool1 && $bool2) {
                $log->info("Upload ".$filename." to FTP successfully");
                $oldFile = $rootDir.self::DS.$pathLocalTemp.self::DS.$filename;
                $moveFile = $rootDir.self::DS.$pathSave.self::DS.$filename;
                if ($this->_file->isExists($oldFile)) {
                    $this->_file->rename($oldFile, $moveFile);
                }
            }else{
                $log->info("Upload ".$filename." to FTP fail");
            }
        } catch (\Exception $e) {
            $log->info($e->getMessage());
        }
        $this->closeFtp();
    }

    /**
     * @param $filename
     * @param $pathFtp
     * @param $log
     * @return bool
     */
    public function removeFileFromFtp($filename, $pathFtp, $log)
    {
        $this->openFtp($log);
        $pwd = $this->cdFolderFtp($pathFtp,$log);
        return $this->_sftp->rm($pwd.self::DS.$filename);
    }

    /**
     * DownloadFileFromFtp
     *
     * @param $pathLocalTemp
     * @param $filename
     * @param $pathFtp
     * @param $log
     * @return mixed
     */
    public function downloadFileFromFtp($pathLocalTemp, $filename, $pathFtp, $log)
    {
        $this->openFtp($log);
        $pwd = $this->cdFolderFtp($pathFtp, $log);
        $rootDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        return $this->_sftp->read($pwd.self::DS.$filename, $rootDir.self::DS.$pathLocalTemp.self::DS.$filename);
    }

    /**
     * @param $name
     * @param $log
     */
    public function backupLog($name, $log)
    {
        $varDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $fileSystem = new File();
        $backupFolder = DS.'log'.DS.'BiExportData';
        $localPath = $varDir.$backupFolder;

        if (!$fileSystem->isDirectory($localPath)) {
            if (!$fileSystem->createDirectory($localPath)) {
                $log->info(__('Can not create dir file').$localPath);
                return;
            }
        }
        $fileLog = $varDir.DS.'log'.DS.$name.'.log';
        $newLog = $varDir.DS.$backupFolder.DS.$name.'_'.$this->_datetime->date('Y-m-d_His').'.log';
        if ($fileSystem->isWritable($localPath) && $fileSystem->isExists($fileLog)) {
            $fileSystem->rename($fileLog,$newLog);
        }
    }

    /**
     * @param $namelog
     * @param $log
     * @return bool
     */
    public function sentMail($namelog, $log)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $isEnable = $this->scopeConfig->getValue(self::CONFIG_ENABLE_MAILCONFIG_ENABLE_MAIL,$storeScope);
        if (!$isEnable) {
            return false;
        }
        // send mail file log
        $log->info("Sending notification emails ....");
        $reader = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $contentLog=  $reader->openFile(DS.'var'.DS.'log'.DS.$namelog.'.log', 'r')->readAll();
        $emailVariable = ['logContent'=> $contentLog];
        $this->sendMailExporting($emailVariable);
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }

    /**
     * Get time by Utc
     *
     * @return string
     */
    public function getTimeByUtc()
    {
        $dateTime = new \DateTime('', new \DateTimeZone('UTC'));
        return $dateTime->format("Y-m-d H:i:s");
    }

    /**
     * Check file before move to sftp
     *      Reject file which size is 0
     *
     * @param $file
     * @return bool
     */
    public function isValidFileToUploadSftp($file)
    {
        $readInterface = $this->_filesystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::ROOT
        );

        /*get file info*/
        $fileInfo = $readInterface->stat($file);

        if ($fileInfo && $fileInfo['size'] && $fileInfo['size'] > 0 ) {
            return true;
        }

        return false;
    }
}