<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Shipment\Controller\Adminhtml\Shipment;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\ShipmentExporter\Cron\ShipmentExporter;
use Riki\ShipmentExporter\Helper\Data as ShipmentExporterHelper;
use Riki\ShipmentImporter\Helper\Data as ShipmentImporterHelper;
use Magento\Framework\Filesystem\Io\Sftp;
class ImportPost extends \Magento\Backend\App\Action
{
    CONST UPLOAD_TARGET = 'uploadShipments';

    CONST UPLOAD_REMOTE = '/remote/';
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;
    /**
     * @var UploaderFactory
     */
    protected $_uploaderFactory;
    /**
     * @var array
     */
    protected $_allowedExtensions = ['csv'];
    /**
     * @var string
     */
    protected $_fileId = 'csv_file';
    /**
     * @var TimezoneInterface
     */
    protected $_dateTime;
    /**
     * @var DirectoryList
     */
    protected $_directoryList;
    /**
     * @var
     */
    protected $_readerCSV;
    /**
     * @var \Riki\ReceiveCvsPayment\Model\ImportingFactory
     */
    protected $_importingFactory;
    /**
     * @var \Riki\ReceiveCvsPayment\Model\CsvorderFactory
     */
    protected $_cvsOrderFactory;
    /**
     * @var \Riki\ReceiveCvsPayment\Model\ResourceModel\Csvorder\CollectionFactory
     */
    protected $_cvsOrderCollectionFactory;
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_backendSession;
    /**
     * @var \Riki\ReceiveCvsPayment\Helper\Data
     */
    protected $_shipmentExporterHelper;
    /**
     * @var ShipmentImporterHelper
     */
    protected $_shipmentImporterHelper;
    /**
     * @var Sftp
     */
    protected $_sftp;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * ImportPost constructor.
     * @param Action\Context $context
     * @param UploaderFactory $uploaderFactory
     * @param TimezoneInterface $dateTime
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\File\Csv $reader
     * @param \Magento\Framework\Filesystem $filesystem
     * @param ShipmentExporterHelper $exporterHelper
     * @param ShipmentImporterHelper $importerHelper
     * @param Sftp $sftp
     */
    public function __construct(
        Action\Context $context,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\File\Csv $reader,
        \Magento\Framework\Filesystem $filesystem,
        \Psr\Log\LoggerInterface $logger,
        ShipmentExporterHelper $exporterHelper,
        ShipmentImporterHelper $importerHelper,
        Sftp $sftp
    )
    {
        $this->_uploaderFactory = $uploaderFactory;
        $this->_dateTime = $dateTime;
        $this->_directoryList = $directoryList;
        $this->_readerCSV = $reader;
        $this->_backendSession = $context->getSession();
        $this->_shipmentExporterHelper = $exporterHelper;
        $this->_shipmentImporterHelper = $importerHelper;
        $this->_sftp = $sftp;
        $this->_logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $destinationPath = $this->getDestinationPath();
        $checker = $this->_shipmentExporterHelper->checkSftpConnection($this->_sftp);
        $resultRedirect = $this->resultRedirectFactory->create();
        if(!$checker[0])
        {
            $errMsg = __('Could not connect to SFTP.');
            $this->messageManager->addError($errMsg);
            return $resultRedirect->setPath('rikiship/shipment/importcsv');
        }
        $fieldFiles= [
            '1501'=>'import_1501',
            '1601'=>'import_1601',
            '1701'=>'import_1701',
            '1801'=>'import_1801',
            '1901'=>'import_1901',
            '1502'=>'import_1502',
            '1602'=>'import_1602',
            '1702'=>'import_1702',
            '1802'=>'import_1802',
            '1902'=>'import_1902',
            '1504'=>'import_1504',
            '1604'=>'import_1604',
            '1704'=>'import_1704',
            '1804'=>'import_1804',
            '1904'=>'import_1904',
            '1507'=>'import_1507'
        ];

        $uploadFiles  = array();
        $fileIncrement = 0;
        foreach($fieldFiles as $key=>$fileField)
        {
            try {
                $uploader = $this->_uploaderFactory->create(['fileId' => $fileField])
                    ->setAllowCreateFolders(true)
                    ->setAllowedExtensions($this->_allowedExtensions)
                    ->setAllowRenameFiles(true)
                    ->addValidateCallback('validate', $this, 'validateFile');
                //success
                if (!$uploader->save($destinationPath)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('File cannot be saved to path: $1', $destinationPath)
                    );
                }
                $filename = $uploader->getUploadedFileName();
                $pattern = $this->getPatternFileByIndex($key);
                //validate file pattern
                $result = preg_match($pattern, $filename);
                if($result)
                {
                    //try to upload to sftp
                    $uploadFiles[$key] = $filename;
                }
                else
                {
                    $this->messageManager->addError("$filename is not valid to upload");
                }
            } catch (\Exception $e) {
                $fileIncrement++;
            }
        }
        if(!$uploadFiles){
            $this->messageManager->addError(__('No any files have been uploaded.'));
        }else{
            $this->exportSftpBatch($uploadFiles);
        }
        return $resultRedirect->setPath('rikiship/shipment/importcsv');
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getDestinationPath()
    {
        $varDirectory = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $path = $varDirectory.'/'.self::UPLOAD_TARGET;
        $fileObject = new File();

        if(!$fileObject->isDirectory($path))
        {
            $fileObject->createDirectory($path,0777);
        }
        return $path;
    }
    /**
     * @param $index
     * @return mixed
     */
    public function getPatternFileByIndex($index)
    {
        $pattern =  $this->_shipmentImporterHelper->getPatternRegex($index);
        return "/^$pattern/";
    }
    /**
     * @param $files
     */
    public function exportSftpBatch($files)
    {
        $host = $this->_shipmentImporterHelper->getSftpHost();
        $port = $this->_shipmentImporterHelper->getSftpPort();
        $username = $this->_shipmentImporterHelper->getSftpUser();
        $password = $this->_shipmentImporterHelper->getSftpPass();
        // try to connect sftp
        try {
            $this->_sftp->open(
                array (
                    'host' => $host.':'.$port,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 300
                )
            );
        } catch (\Exception $e) {
            $messages[] = $e->getMessage();
            return;
        }

        $rootDir = $this->_sftp->pwd();
        foreach($files as $pathType=>$_filename)
        {
            $locationTarget = $this->getTargetLocationByNumber($pathType);
            $locationTarget = preg_replace('#/+#','/',$locationTarget);
            $pathLocal  = $this->getDestinationPath();
            $dirList = explode('/', $locationTarget);
            $this->_sftp->cd($rootDir); // back to first dir
            foreach ($dirList as $dir) {
                if($dir != '') {
                    if (!$this->_sftp->cd($dir)) {
                        try {
                            $this->_sftp->mkdir('/'. $dir);
                        } catch (\Exception $e) {
                            $this->messageManager->addError($e->getMessage());
                        }
                    }
                    try {
                        $this->_sftp->cd($dir);
                    } catch(\Exception $e) {
                        $this->messageManager->addError($e->getMessage());
                    }
                }
            }
            try {
                $sftpPath = $rootDir.'/'.$locationTarget.'/';
                $targetFile  = $sftpPath. $_filename;
                $sourceFile =  $pathLocal.'/'.$_filename;
                if($this->_sftp->write($targetFile, $sourceFile))
                {
                    $this->messageManager->addSuccess( "Upload $_filename to sftp successfully");
                }
                else
                {
                    $this->messageManager->addError("Upload ".$_filename." fail");
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }//end foreach
    }
    /**
     * @param $number
     * @return mixed
     */
    public function getTargetLocationByNumber($number)
    {
        return $this->_shipmentImporterHelper->getLocationSftp($number);
    }
    /**
     * Is Allow.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        if ($this->_authorization->isAllowed('Riki_Shipment::rikiship_importcsv'))
        {
            return true;
        }
        return false;
    }
}
