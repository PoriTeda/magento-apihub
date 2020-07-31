<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper;

class SftpExportHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    protected $_sftp;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper
     */
    protected $_configHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper
     */
    protected $_fileHelper;

    /*sftp root folder*/
    protected $pathRoot;

    /*sftp logger, provided by setLogger function*/
    protected $_logger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper
    ) {
        parent::__construct($context);
        $this->_sftp = $sftp;
        $this->_encryptor = $encryptor;
        $this->_configHelper = $configHelper;
        $this->_fileHelper = $fileHelper;
    }

    /**
     * Move file to sFtp (after create local file success)
     *
     * @param $localPathTmp
     * @param $localPath
     * @param $sFtpPath
     * @param string $reportPath
     */
    public function moveFileToFtp($localPathTmp, $localPath, $sFtpPath, $reportPath = '')
    {
        $rootDir = $this->_fileHelper->getRootDirectory();

        /*open sFtp connect*/
        $this->openFtp();

        /*get sftp exported directory*/
        $exportedDir = $this->getFtpDirectory($sFtpPath);

        /*get sftp reported directory*/
        if (!empty($reportPath)) {
            $reportedDir = $this->getFtpDirectory($reportPath);
        } else {
            $reportedDir = false;
        }

        /*get list file from tmp folder*/
        $listFile = $this->_fileHelper->getListFileFromDirectory($localPathTmp);

        if ($listFile) {
            try {

                foreach ($listFile as $path) {

                    /*flag to check create file to export folder success*/
                    $exportBi = false;
                    /*flag to check create file to report folder success*/
                    $reportBi = false;

                    /*get file name from path*/
                    $fileName = $this->_fileHelper->getFileName($path);

                    /*check create export file process*/
                    if ($exportedDir) {
                        if (!$this->createFileToFtp($exportedDir.DS.$fileName, $rootDir.DS.$path)) {
                            $this->addLogInfo('Upload '.$fileName.' to FTP folder failed');
                        } else {
                            $exportBi = true;
                            $this->addLogInfo('Upload '.$fileName.' to FTP folder successfully');
                        }
                    }

                    /*check create report file process*/
                    if ($reportedDir) {
                        if (!$this->createFileToFtp($reportedDir.DS.$fileName, $rootDir.DS.$path)) {
                            $this->addLogInfo('Upload '.$fileName.' to FTP - Report folder failed');
                        } else {
                            $reportBi = true;
                            $this->addLogInfo('Upload '.$fileName.' to FTP - Report folder successfully');
                        }
                    }

                    /*success create file for export folder and report folder*/
                    if ($exportBi || $reportBi) {

                        /*file from tmp folder*/
                        $oldFile = $rootDir.DS.$localPathTmp.DS.$fileName;
                        /*file from local folder*/
                        $moveFile = $rootDir.DS.$localPath.DS.$fileName;

                        /*move file from tmp folder to local folder*/
                        $this->_fileHelper->move($oldFile, $moveFile);
                    }
                }

                $this->addLogInfo('Summary of '.sizeof($listFile).' files');

            } catch (\Exception $e) {
                $this->addLogInfo($e->getMessage());
            }
        }
        $this->closeFtp();
    }

    /**
     * Create new file for sftp
     *
     * @param $sFtpFile
     * @param $localFile
     * @return bool
     */
    public function createFileToFtp($sFtpFile, $localFile)
    {
        try {
            return $this->_sftp->write($sFtpFile, $localFile);
        } catch (\Exception $e) {
            $this->addLogInfo($e->getMessage());
        }

        return false;
    }

    /**
     * connect to sFtp
     */
    public function openFtp()
    {
        /*get sFtp config*/
        $host = $this->_configHelper->getSftpHost();
        $port = $this->_configHelper->getSftpPort();
        $username = $this->_configHelper->getSftpUser();
        $password = $this->_encryptor->decrypt($this->_configHelper->getSftpPass());

        /*connect to sftp*/
        try {
            $this->_sftp->open([
                'host' => $host.':'.$port,
                'username' => $username,
                'password' => $password,
                'timeout' => 300
            ]);

            /*get sftp root working directory*/
            $this->pathRoot = $this->_sftp->pwd();
        } catch (\Exception $e) {
            $this->addLogInfo($e->getMessage());
            return;
        }
    }

    /**
     * Get working directory from sFtp
     *
     * @param $location
     * @return mixed
     */
    public function getFtpDirectory($location)
    {
        /*move current working directory to sftp root*/
        $this->_sftp->cd($this->pathRoot);

        /*create folder on ftp*/
        $dirList = explode('/', $location);

        foreach ($dirList as $dir) {
            if ($dir != '') {

                /*create folder if not exists*/
                if (!$this->_sftp->cd($dir)) {
                    try {
                        $this->_sftp->mkdir('/' . $dir);
                    } catch (\Exception $e) {
                        $this->addLogInfo($e->getMessage());
                    }
                }

                /*Change current working directory to this folder*/
                try {
                    $this->_sftp->cd($dir);
                } catch(\Exception $e) {
                    $this->addLogInfo($e->getMessage());
                }
            }
        }

        /*return current working directory*/
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
     * Provide logger object for this helper
     *
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Add message to logger
     *
     * @param $msg
     */
    public function addLogInfo($msg)
    {
        if (!empty($this->_logger)) {
            $this->_logger->info($msg);
        }
    }
}