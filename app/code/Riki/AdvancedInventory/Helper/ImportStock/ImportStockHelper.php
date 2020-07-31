<?php

namespace Riki\AdvancedInventory\Helper\ImportStock;

use Magento\Framework\Filesystem\Driver\File;

class ImportStockHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * @var \Magento\Framework\App\AreaList
     */
    protected $areaList;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $file;
    /**
     * @var File
     */
    protected $fileSystem;
    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    protected $sftp;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $posFactory;
    /**
     * @var \Riki\AdvancedInventory\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \Riki\AdvancedInventory\Helper\ImportStock\ConfigHelper
     */
    protected $configHelper;

    /*logger object*/
    protected $logger;

    /*name of log file*/
    protected $logFileName;

    /*warehouse*/
    protected $wh;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\AreaList $areaList,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Filesystem $file,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Riki\AdvancedInventory\Helper\Data $dataHelper,
        \Riki\AdvancedInventory\Helper\ImportStock\ConfigHelper $configHelper
    ) {
        parent::__construct($context);
        $this->areaList = $areaList;
        $this->state = $state;
        $this->directoryList = $directoryList;
        $this->encryptor = $encryptor;
        $this->file = $file;
        $this->fileSystem = $fileSystem;
        $this->sftp = $sftp;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->posFactory = $pointOfSaleFactory;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Provide logger object
     *
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->logFileName = $logger->getLogFileName();

        $this->logger = $logger;
        $this->logger->setTimezone(
            new \DateTimeZone($this->timezone->getConfigTimezone())
        );

        $this->dataHelper->setLogger($this->logger);

    }

    /**
     * Set warehouse config
     *
     * @param $wh
     */
    public function setWarehouseConfig($wh)
    {
        $this->wh = $wh;
        $this->configHelper->setWarehouse($wh);
    }

    /**
     * import stock - main process
     */
    public function importProcess()
    {
        $area = $this->areaList->getArea($this->state->getAreaCode());
        $area->load(\Magento\Framework\App\Area::PART_TRANSLATE);

        $taskName = __($this->wh. ' Warehouse - Import Stock');

        $date = $this->timezone->date();
        $needDate = $date->format('Y-m-d H:i:s');
        $needDateFile = $date->format('YmdHis');

        $this->dataHelper->backupLog($needDateFile, $this->logFileName);

        $this->logger->info($taskName. ' run at: '. $needDate);

        if (!$this->configHelper->isEnableImportStock()) {
            $this->logger->info(sprintf(__('%s has been disabled'), $taskName));
            return;
        }

        $warehouseId = $this->configHelper->getWarehouseId();

        $warehouse = $this->getWarehouseById($warehouseId);

        if (!$warehouse) {
            $this->logger->info(sprintf(__('Warehouse %s does not exist'), $this->wh));
            return;
        }

        /*get import file*/
        $data = $this->getSftpFiles($needDateFile);

        if (empty($data)) {
            $this->sendMailResult();
            return;
        }

        if ($data == 2) {
            $this->logger->info('CSV files not found.');
            $this->sendMailResult();
            return;
        }

        $whData = $this->dataHelper->getWarehouseIdByCode();

        $warehouseCode = $warehouse->getData('store_code');

        /*folder where to get import file*/
        $location = $this->configHelper->getWarehouseLocation();

        // Process Data
        $productList = array();
        foreach ($data as $filename => $content) {
            if ($content == 1) {
                $this->logger->info(sprintf(__("Content of file %s is null"), $filename));
            } elseif ($content == 2) {
                $this->logger->info(sprintf(__("The file %s does not exist"), $location.self::DS.$filename));
            } else {
                $this->logger->info((sprintf(__(" ********* Import data from file %s ***********"), $filename)));
                foreach ($content as $key => $item) {
                    if ($key > 0) {
                        $result = $this->dataHelper->updateAdvancedInventory($item,$whData, $warehouseCode);
                        if ($result == 1) {
                            $this->logger->info(sprintf(__('Update success: %s %s %s %s %s'),
                                $item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_SKU],
                                $warehouseCode,
                                $item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_QTY],
                                $item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_IS_IN_STOCK],
                                $item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_MANAGE_STOCK]
                            ));
                        } else {

                            $productSku = '';

                            if (!empty($item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_SKU])) {
                                $productSku = $item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_SKU];
                            }

                            $productQty = 0;

                            if (!empty($item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_QTY])) {
                                $productQty = $item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_QTY];
                            }

                            $productIsInStock = 0;

                            if (!empty($item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_IS_IN_STOCK])) {
                                $productIsInStock = $item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_IS_IN_STOCK];
                            }

                            $productManageStock = 0;

                            if (!empty($item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_MANAGE_STOCK])) {
                                $productManageStock = $item[\Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_CSV_KEY_MANAGE_STOCK];
                            }

                            //error importing
                            $productList[] = implode("\r\n",[
                                sprintf(__("Import product sku: %s"),$productSku),
                                sprintf(__("Import product qty: %s"),$productQty),
                            ]);
                            if($result == \Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_ERROR_INVALID_PRODUCT_SKU) {
                                $errorDetail = "Invalid product sku";
                            } elseif ($result == \Riki\AdvancedInventory\Helper\Data::RIKI_IMPORT_ERROR_INVALID_WAREHOUSE_CODE) {
                                $errorDetail = "Invalid warehouse code";
                            } else {
                                $errorDetail = "unknown error";
                            }

                            $this->logger->info(sprintf(__('Update failed: %s %s %s %s %s (%s)'),
                                $productSku,
                                $warehouseCode,
                                $productQty,
                                $productIsInStock,
                                $productManageStock,
                                $errorDetail
                            ));
                        }
                    }
                }
            }

        }

        $this->logger->info('****************** '. $taskName. ' end ****************************** ');

        /* controlled by Email Marketing */
        /* Email: Stock import email */
        if (count($productList)) {
            $emailVariable['year'] = $this->timezone->date()->format('Y');
            $emailVariable['month'] = $this->timezone->date()->format('m');
            $emailVariable['day'] = $this->timezone->date()->format('d');
            $emailVariable['hour'] = $this->timezone->date()->format('H');
            $emailVariable['productList'] = implode("\r\n", $productList);
            $this->dataHelper->sendMailStockResult($emailVariable);
        }
        //send log email
        $this->sendMailResult();

    }

    /**
     * Read import file
     *
     * @param $needDateFile
     * @return int|bool|array
     */
    public function getSftpFiles($needDateFile)
    {
        $host = $this->configHelper->getSftpHost();
        $port = $this->configHelper->getSftpPort();
        $username = $this->configHelper->getSftpUser();
        $pass = $this->encryptor->decrypt(
            $this->configHelper->getSftpPass()
        );

        try {
            $this->sftp->open(
                array(
                    'host' => $host .':'. $port,
                    'username' => $username,
                    'password' => $pass,
                    'timeout' => 300
                )
            );
        } catch(\Exception $e) {
            $this->sftp->close();
            $this->logger->info(__('Could not connect to SFTP server'));
            return false;
        }

        $getRootPath = $this->sftp->pwd();

        /*warehouse location where to get import file*/
        $location = $this->configHelper->getWarehouseLocation();

        $dirList = explode('/', $location);

        foreach ($dirList as $dir) {
            if ($dir != '') {
                try {
                    if (!$this->sftp->cd($dir)) {
                        return false;
                    }
                } catch (\Exception $e) {
                    $this->logger->info(sprintf(__('Location :%s in sFTP does not exist'), $location));
                    return false;
                }
            }
        }

        /*file name pattern*/
        $patternRoot = $this->configHelper->getWarehousePattern();

        $pattern = "/^$patternRoot/";

        $baseDir = $this->directoryList->getPath(
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );

        $remoteFolder = $this->dataHelper->getRemoteFolderName($location);

        $doneFolder = "imported";

        $pathToDoneFolder = str_replace($remoteFolder, $doneFolder, $location);

        /*create done folder*/
        $pathToDoneFolder = $getRootPath.'/'.$pathToDoneFolder;

        if (!$this->sftp->cd($pathToDoneFolder)) {
            $this->sftp->cd($location);
            $this->sftp->cd('..');
            $this->sftp->mkdir('/'.$doneFolder, 0777);
        }

        $uploadPath = $getRootPath.'/'.$location;
        $this->sftp->cd($uploadPath);
        $files = $this->sftp->rawls();
        $filesALl =  array();
        $localPath = $baseDir . '/importstock';
        $localPathShort = 'importstock';

        $fileObject = new File();
        if(!$fileObject->isExists($localPath)) {
            $fileObject->createDirectory($localPath, 0777);
        }

        foreach ($files as $key => $file) {
            $pre = preg_match($pattern, $key);
            if($pre) {
                $extension = substr($key, strpos($key, '.'));
                if ($extension == '.csv') {
                    $filesALl[] = $key;
                    $this->sftp->read($uploadPath . '/'. $key, $localPath . '/' . $key);
                }
            }
        }

        $data = [];
        if (sizeof($filesALl) > 0) {
            foreach ($filesALl as $filename) {
                if ($this->fileSystem->isExists($localPath . self::DS . $filename)) {
                    $newFileName = str_replace('.csv', '_' . $needDateFile . '.csv', $filename);
                    $done = str_replace($remoteFolder, $doneFolder, $location . '/' . $newFileName);
                    $done = $getRootPath.'/'.$done;
                    //remove if already exist in done folder
                    $this->sftp->rm($done);
                    $this->sftp->mv($uploadPath . '/' . $filename, $done);

                    $contentFile = $this->dataHelper->getCsvData($baseDir . self::DS . $localPathShort . self::DS . $filename);

                    if ($contentFile == null || $contentFile == '') {
                        $data[$filename] = 1;
                    } else {
                        $data[$filename] = $contentFile;
                    }

                    try {
                        if ($fileObject->isExists($localPath . self::DS . $filename)) {
                            $fileObject->deleteFile($localPath . self::DS . $filename);
                        }
                    } catch (\Exception $e) {
                        $this->logger->info($e->getMessage());
                    }
                }
            }
        }

        $this->sftp->close();

        if (sizeof($data) > 0) {
            return $data;
        } else {
            //import file does not exists
            return 2;
        }
    }

    /**
     * send email result
     */
    public function sendMailResult()
    {
        $reader = $this->file->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );

        $contentLog = $reader->openFile(
            '/log/'.$this->logFileName, 'r'
        )->readAll();

        $emailVariable = [
            'log_content' => $contentLog,
            'warehouse' => $this->wh
        ];

        $this->dataHelper->sendMailResult($emailVariable);
    }

    /**
     * Get warehouse by id
     *
     * @param $id
     * @return bool|\Wyomind\PointOfSale\Model\PointOfSale
     */
    public function getWarehouseById($id)
    {
        /** @var \Wyomind\PointOfSale\Model\PointOfSale $pos */
        $pos = $this->posFactory->create();
        $pos->load($id);
        if ($pos->getId()) {
            return $pos;
        }
        return false;
    }

}