<?php
/**
 * Riki Shipment Importer
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ShipmentImporter\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\AreaList;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;

/**
 * Class ShipmentImporter1504
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CodMoneyCollection1504
{
    /**
     * @var
     */
    protected $dateTime;
    /**
     * @var
     */
    protected $dataHelper;
    /**
     * UpdateOrder constructor.
     */
    protected $objectManager;
    /**
     * @var
     */
    protected $directoryList;
    /**
     * @var
     */
    protected $sftp;

    /**
     * @var
     */
    protected $logger;

    protected $logger1504;
    protected $logger1604;
    protected $logger1704;
    protected $logger1804;
    protected $logger1904;
    /**
     * @var
     */
    protected $timezone;
    /**
     * @var
     */
    protected $fileSystem;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystemReader;
    /**
     * @var AreaList
     */
    protected $areaList;

    /**
     * CodMoneyCollection1504 constructor.
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param DirectoryList $directoryList
     * @param Sftp $sftp
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param File $fileSystem
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\ShipmentImporter\Helper\Data $dataHelper
     * @param \Riki\ShipmentImporter\Logger\CodMoneyCollection\Logger\Logger1504 $logger1504
     * @param \Riki\ShipmentImporter\Logger\CodMoneyCollection\Logger\Logger1604 $logger1604
     * @param \Riki\ShipmentImporter\Logger\CodMoneyCollection\Logger\Logger1704 $logger1704
     * @param \Riki\ShipmentImporter\Logger\CodMoneyCollection\Logger\Logger1804 $logger1804
     * @param \Riki\ShipmentImporter\Logger\CodMoneyCollection\Logger\Logger1904 $logger1904
     * @param \Magento\Framework\Filesystem $filesystemReader
     * @param AreaList $areaList
     */

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        \Riki\ShipmentImporter\Logger\CodMoneyCollection\Logger\Logger1504 $logger1504,
        \Riki\ShipmentImporter\Logger\CodMoneyCollection\Logger\Logger1604 $logger1604,
        \Riki\ShipmentImporter\Logger\CodMoneyCollection\Logger\Logger1704 $logger1704,
        \Riki\ShipmentImporter\Logger\CodMoneyCollection\Logger\Logger1804 $logger1804,
        \Riki\ShipmentImporter\Logger\CodMoneyCollection\Logger\Logger1904 $logger1904,
        \Magento\Framework\Filesystem $filesystemReader,
        AreaList $areaList
    ) {
        $this->dataHelper = $dataHelper;
        $this->dateTime = $dateTime;
        $this->directoryList = $directoryList;
        $this->sftp = $sftp;
        $this->timezone = $timezone;
        $this->fileSystem = $fileSystem;
        $this->shipmentRepository = $shipmentRepository;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->fileSystemReader = $filesystemReader;
        $this->areaList = $areaList;
        $this->logger1504 = $logger1504;
        $this->logger1604 = $logger1604;
        $this->logger1704 = $logger1704;
        $this->logger1804 = $logger1804;
        $this->logger1904 = $logger1904;
    }

    /**
     * import money collection for COD payment method and warehouse TOYO
     */
    public function import1504()
    {
        $this->importMoneyCollection('1504');
    }
    /**
     * import money collection for COD payment method and warehouse BIZEX
     */
    public function import1604()
    {
        $this->importMoneyCollection('1604');
    }
    /**
     * import money collection for COD payment method and warehouse HITACHI-TS
     */
    public function import1704()
    {
        $this->importMoneyCollection('1704');
    }
    /**
     * import money collection for COD payment method and warehouse LOGICALPLANT
     */
    public function import1804()
    {
        $this->importMoneyCollection('1804');
    }
    /**
     * import money collection for COD payment method and warehouse WH5
     */
    public function import1904()
    {
        $this->importMoneyCollection('1904');
    }
    /**
     * @param $warehouseId
     */
    public function importMoneyCollection($warehouseId)
    {
        //load Translation for cron
        $areaObject = $this->areaList->getArea(\Magento\Framework\App\Area::AREA_FRONTEND);
        $areaObject->load(\Magento\Framework\App\Area::PART_TRANSLATE);
        $taskName = 'Import Cash-on-delivery money collected file from WMS';
        $originDate = $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2);
        $needDate = $this->dateTime->gmtDate('Y-m-d H:i:s', $originDate);
        $needDateFile = $this->dateTime->gmtDate('Ymd', $originDate);
        $needDateFileDone = $this->dateTime->gmtDate('YmdHis', $originDate);
        $logFilename = "shipment$warehouseId.log";
        $this->dataHelper->backupLog($needDateFile, $logFilename, $warehouseId);
        $this->getLogger($warehouseId);
        $this->logger->info($taskName . ' run at :' . $needDate);
        if (!$this->dataHelper->isEnable()) {
            $this->logger->info('Import Cash-on-delivery money collected file from WMS has been disabled.');
            $this->sendMailResult($warehouseId);
            return;
        }
        //check sftp
        if (!$this->dataHelper->checkSftpConnection($this->sftp)) {
            $this->logger->info('Could not connect to sFTP.');
            $this->sendMailResult($warehouseId);
            return;
        }
        $data = $this->getAllCsvFiles($needDateFileDone, $warehouseId);
        if (!$data) {
            $this->sendMailResult($warehouseId);
            return;
        }
        $path = $this->dataHelper->getLocationSftp($warehouseId);
        $year = $this->timezone->date()->format('Y');
        $month = $this->timezone->date()->format('m');
        $day = $this->timezone->date()->format('d');
        $hour = $this->timezone->date()->format('H');
        foreach ($data as $filename => $content) {
            $reasonFail = '';
            $error = 0;
            $fileErrors = 0;
            if ($content == 1) {
                $error++;
                $fileErrors++;
                $reasonFail = sprintf('Content of file %s is null', $filename);
                $this->logger->info(sprintf("Content of file %s is null", $filename));
            } elseif ($content == 2) {
                $error++;
                $fileErrors++;
                $reasonFail = sprintf('The file %s does not exist', $path . $filename);
                $this->logger->info(sprintf("The file %s does not exist", $path . $filename));
            } else {
                $this->importProcess($content);
            }//end else
            if (!$error) {
                $this->logger->info(sprintf('Import file %s successful', $filename));
            }
            //file errors
            if ($fileErrors) {
                //move to error file
                $this->logger->info(sprintf('File: %s has an error.', $filename));
                $this->moveToError($needDateFileDone, $filename, true, $warehouseId);
                /* controlled by Email Marketing */
                /* Email: Import COD collection error */
                $emailVariables =
                    [
                        'reason' => $reasonFail,
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                        'hour' => $hour
                    ];
                $this->dataHelper->sendErrorImportingEmail($emailVariables, 'COD');
            }
        }//end foreach
    }

    /**
     * @param $needDateFile
     * @param $warehouseId
     * @return array|bool|int
     */
    public function getAllCsvFiles($needDateFile, $warehouseId)
    {
        $sftpLocation = $this->dataHelper->getLocationSftp($warehouseId);
        //Validate folder in sftp server
        if (!$this->dataHelper->checkSftpLocation($this->sftp, $sftpLocation, true)) {
            $this->logger->info(sprintf('Location :%s in sFTP does not exist', $sftpLocation));
            return false;
        } else {
            $csvData = $this->getSftpFiles($needDateFile, $warehouseId);
            if ($csvData == 2) {
                $this->logger->info('CSV files  not found.');
                return false;
            }
            return $csvData;
        }
    }

    /**
     * @param $content
     */
    private function importProcess($content)
    {
        /**
         * Content of CSV
         * Column 1 : Shipment number
         * Column 2 : Serial number 0001,0002,0003....
         * Column 3 : Order number
         * Column 4 : Payment date
         * Column 5 : Payment Amount
         */
        $canImportStatus = [ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                            ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT,
                            ShipmentStatus::SHIPMENT_STATUS_REJECTED];
        foreach ($content as $key => $item) {
            if (!empty($item[0])) {
                $shipment = $this->getShipmentByIncrementId(trim($item[0]));
                if ($shipment) {
                    $shipmentStatus = $shipment->getShipmentStatus();
                    if (in_array($shipmentStatus, $canImportStatus)) {
                        $this->logger->info(sprintf('Processing shipment %s ', $item[0]));
                        $order = $this->getOrderById($shipment->getData('order_id'));
                        if (!$order) {
                            $this->logger->info('Order does not exist');
                        } else {
                            $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
                            if ($paymentMethod != \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD) {
                                $this->logger->info(
                                    sprintf(
                                        'Order %s:  has payment method invalid: %s',
                                        $order->getIncrementId(),
                                        $paymentMethod
                                    )
                                );
                            } else {
                                $this->importItem($shipment, $item, $order);
                            }
                        }
                    } else {
                        $this->logger->info(
                            sprintf('Can not import shipment number %s. Shipment status is invalid.', $item[0])
                        );
                    }
                } else {
                    $this->logger->info(sprintf('The shipment %s does not exist', $item[0]));
                }
            } else {
                $this->logger->info('Shipment does not exist');
            }
        }
    }

    /**
     * @param $shipment
     * @param $item
     * @param $order
     */
    public function importItem($shipment, $item, $order)
    {
        $originDate = $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2);
        $systemDate = $this->dateTime->date('Y-m-d');

        if (!empty($item[3])) {
            $paymentDate = $this->dateTime->date('Y-m-d', strtotime($item[3]));
        } else {
            $paymentDate = $this->dateTime->date('Y-m-d', $originDate);
        }

        $amountCollected = !empty($item[4]) ? (int)$item[4] : 0;
        if (!$shipment->getData('grand_total') ||
            $shipment->getData('grand_total')==
            $shipment->getData('base_shopping_point_amount')) {
            $paymentStatus = '';
        } else {
            $paymentStatus = \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED;
        }

        try {
            /*update shipment payment status -> payment_collected*/
            $shipment->setPaymentStatus($paymentStatus);

            /*System date when we receive the payment collected message*/
            $shipment->setPaymentDate($systemDate);

            /*The actual Payment collection date mentioned message*/
            $shipment->setCollectionDate($paymentDate);

            /*collected amount*/
            $shipment->setAmountCollected($amountCollected);

            $shipment->save();

            $this->logger->info(sprintf('Processing shipment %s success', $item[0]));

            /*update payment status for related order*/
            $this->orderProcess($order, $paymentDate, $shipment->getIncrementId());
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * @param $order
     * @param $paymentDate
     */
    public function orderProcess(\Magento\Sales\Model\Order $order, $paymentDate, $shipNumber = null)
    {
        $finalStatus = $this->dataHelper->isOrderCollected($order->getId());
        if ($finalStatus) {
            $this->dataHelper->createInvoiceOrder($order, $paymentDate);
        }
        try {
            //update history
            $order->addStatusToHistory($order->getStatus(), __('COD Money Collection, shipment number:') . $shipNumber)
                ->setIsCustomerNotified(false);
            $order->save();
            $this->logger->info(sprintf('Change payment status for order %1 success', $order->getIncrementId()));
        } catch (\Exception $e) {
            $this->logger->info(sprintf('Cannot change payment status for order %1', $order->getIncrementId()));
        }
    }
    /**
     * @param $warehouseId
     */
    public function sendMailResult($warehouseId)
    {
        $taskName = 'Import Cash-on-delivery money collected file from WMS';
        $filesystem = $this->fileSystemReader;
        $baseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $reader = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $logFilename = "/var/log/importshipment/shipment$warehouseId.log";
        if ($this->fileSystem->isExists($baseDir . $logFilename)) {
            $contentLog = $reader->openFile($logFilename, 'r')->readAll();
            $emailVariable = ['logContent' => $contentLog,'taskname'=>$taskName];
        } else {
            $emailVariable = ['logContent' => __("Log is not found!"),'taskname'=>$taskName];
        }
        $this->dataHelper->sendMailResult($emailVariable);
    }
    /**
     * @param $needDateFile
     * @param $warehouseId
     * @return array|int
     */
    public function getSftpFiles($needDateFile, $warehouseId)
    {
        if (defined('DS') === false) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        $host = $this->dataHelper->getSftpHost();
        $port = $this->dataHelper->getSftpPort();
        $username = $this->dataHelper->getSftpUser();
        $password = $this->dataHelper->getSftpPass();
        $patternRoot = $this->dataHelper->getPatternRegex($warehouseId);
        $pattern = "/^$patternRoot/";
        $baseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $remoteFolder = "remote";
        $doneFolder = "done";
        try {
            $this->sftp->open(
                [   'host' => $host . ':' . $port,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 300
                ]
            );
        } catch (\Exception $e) {
            $this->sftp->close();
            return false;
        }
        $sfptRoot = $this->sftp->pwd();
        if ($sfptRoot != DIRECTORY_SEPARATOR) {
            $location = $sfptRoot . $this->dataHelper->getLocationSftp($warehouseId);
        } else {
            $location = $this->dataHelper->getLocationSftp($warehouseId);
        }
        $this->sftp->cd($location);
        $files = $this->sftp->rawls();
        $filesall = [];
        $localPath = $baseDir . '/import';
        $localPathShort = 'import';

        $fileObject = new File();
        if (!$fileObject->isExists($localPath)) {
            $fileObject->createDirectory($localPath, 0777);
        }

        foreach ($files as $key => $file) {
            if (!in_array($key, ['.', '..'])) {
                $pre = preg_match($pattern, $key);
                if ($pre) {
                    $extension = substr($key, strrpos($key, '.')); // Gets the File Extension
                    if (strtolower($extension) == ".csv") {
                        $filesall[] = $key; // Store in Array
                        $this->sftp->read($location . DS . $key, $localPath . DS . $key);
                    } else {
                        $this->logger->info(sprintf('File: %s is not CSV extension', $key));
                        $this->moveToError($needDateFile, $key, true, $warehouseId);
                    }
                } else {
                    //move to error
                    $this->logger->info(
                        sprintf(
                            'File: %s does not match the configed pattern name (%s).',
                            $key,
                            $pattern
                        )
                    );
                    $this->moveToError($needDateFile, $key, true, $warehouseId);
                }
            }
        }
        $data = [];
        if (count($filesall) > 0) {
            foreach ($filesall as $filename) {
                if ($this->fileSystem->isExists($localPath . DS . $filename)) {
                    $newfilename = str_replace('.csv', '_' . $needDateFile . '.csv', $filename);
                    $done = str_replace($remoteFolder, $doneFolder, $location . DS . $newfilename);
                    //remove if already exist in done folder
                    $this->sftp->rm($done);
                    $this->sftp->mv($location . DS . $filename, $done);
                    $contentFile = $this->dataHelper->getCsvData($baseDir . DS . $localPathShort . DS . $filename);
                    if ($contentFile == null || $contentFile == '') {
                        $data[$filename] = 1;
                    } else {
                        $data[$filename] = $contentFile;
                    }
                    try {
                        if ($fileObject->isExists($localPath . DS . $filename)) {
                            $fileObject->deleteFile($localPath . DS . $filename);
                        }
                    } catch (\Exception $e) {
                        $this->logger->info($e->getMessage());
                    }
                }
            }
        }
        $this->sftp->close();
        if (count($data)) {
            return $data;
        } else {
            return 2;
            //import file does not exists
        }
    }

    /**
     * @param $needDateFile
     * @param $filename
     * @param bool $ext
     * @param $warehouseId
     * @return bool
     */
    public function moveToError($needDateFile, $filename, $ext = false, $warehouseId = null)
    {
        if ($filename != '.' && $filename != '..') {
            $host = $this->dataHelper->getSftpHost();
            $port = $this->dataHelper->getSftpPort();
            $username = $this->dataHelper->getSftpUser();
            $password = $this->dataHelper->getSftpPass();
            $location = $this->dataHelper->getLocationSftp($warehouseId);
            $remoteFolder = 'remote';
            $doneFolder = "complete";
            $errorFolder = "error";
            try {
                $this->sftp->open(
                    [
                        'host' => $host . ':' . $port,
                        'username' => $username,
                        'password' => $password,
                        'timeout' => 300
                    ]
                );
            } catch (\Exception $e) {
                $this->sftp->close();
                return false;
            }
            $root = $this->sftp->pwd();
            $location = $root . $location;
            try {
                if ($ext) {
                    $newfilename = $filename;
                    $completeFile = $location . DS . $newfilename;
                    $errorFile = str_replace($remoteFolder, $errorFolder, $location . DS . $newfilename);
                    $this->sftp->rm($errorFile);
                } else {
                    $newfilename = str_replace('.csv', '_' . $needDateFile . '.csv', $filename);
                    $completeFile = str_replace($remoteFolder, $doneFolder, $location . DS . $newfilename);
                    $errorFile = str_replace($remoteFolder, $errorFolder, $location . DS . $newfilename);
                }
                //remove if already exist in done folder
                $this->sftp->mv($completeFile, $errorFile);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param $orderId
     * @return bool
     */
    protected function getOrderById($orderId)
    {
        $criteria = $this->searchCriteriaBuilder->addFilter('entity_id', $orderId)
            ->create();

        $orderCollection = $this->orderRepository->getList($criteria);

        if ($orderCollection->getSize()) {
            return $orderCollection->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * @param $incrementId
     * @return bool
     */
    protected function getShipmentByIncrementId($incrementId)
    {
        $criteria = $this->searchCriteriaBuilder->addFilter('increment_id', $incrementId)
            ->create();

        $shipmentCollection = $this->shipmentRepository->getList($criteria);

        if ($shipmentCollection->getSize()) {
            return $shipmentCollection->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * @param $warehouseId
     */
    protected function getLogger($warehouseId)
    {
        switch ($warehouseId) {
            case '1504':
                $this->logger = $this->logger1504;
                break;
            case '1604':
                $this->logger = $this->logger1604;
                break;
            case '1704':
                $this->logger = $this->logger1704;
                break;
            case '1804':
                $this->logger = $this->logger1804;
                break;
            case '1904':
                $this->logger = $this->logger1904;
                break;
        }
        $this->logger->setTimezone(new \DateTimeZone($this->timezone->getConfigTimezone()));
    }
}
