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
use Riki\SapIntegration\Model\Api\Shipment;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\Order\ShipmentFactory;
use Riki\Shipment\Helper\ShipmentHistory;
use Riki\ShipmentImporter\Helper\Order as OrderStatusHelper;

/**
 * Class ShipmentShippedOut
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ShipmentShippedOut
{
    const SAP_FLAG_INDEX = 10;
    /**
     * @var
     */
    protected $dateTime;
    /**
     * @var
     */
    protected $shipment;
    /**
     * @var
     */
    protected $dataHelper;
    /**
     * UpdateOrder constructor.
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
    /**
     * @var
     */
    protected $logger1501;
    /**
     * @var
     */
    protected $logger1601;
    /**
     * @var
     */
    protected $logger1701;
    /**
     * @var
     */
    protected $logger1801;
    /**
     * @var
     */
    protected $logger1901;
    /**
     * @var
     */
    protected $timezone;
    /**
     * @var
     */
    protected $orderHistory;
    /**
     * @var
     */
    protected $fileSystem;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var \Riki\ShipmentImporter\Helper\Email
     */
    protected $emailHelper;
    /**
     * @var ShipmentRepository
     */
    protected $shipmentRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var
     */
    protected $shipmentFactory;
    /**
     * @var
     */
    protected $shipmentHistory;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystemReader;
    /**
     * @var \Magento\Sales\Api\Data\ShipmentTrackInterface
     */
    protected $shipmentTrackInterface;
    /**
     * @var \Magento\Sales\Api\ShipmentTrackRepositoryInterface
     */
    protected $shipmentTrackRepository;
    /**
     * @var
     */
    protected $shipmentTractCollectionFactory;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;
    /**
     * @var \Magento\Framework\App\AreaList
     */
    protected $areaList;
    /**
     * @var \Riki\ShipmentImporter\Helper\Order
     */
    protected $orderHelper;
    /**
     * @var \Riki\ShipmentImporter\Helper\ShippedOutBucket
     */
    protected $shippedOutBucketHelper;

    /**
     * @var \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface
     */
    protected $shipmentSapExportedRepository;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * ShipmentShippedOut constructor.
     * @param \Riki\ShipmentImporter\Helper\Data $dataHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param DirectoryList $directoryList
     * @param Sftp $sftp
     * @param \Riki\ShipmentImporter\Logger\ShipmentShippedOut\Logger\Logger1501 $logger1501
     * @param \Riki\ShipmentImporter\Logger\ShipmentShippedOut\Logger\Logger1601 $logger1601
     * @param \Riki\ShipmentImporter\Logger\ShipmentShippedOut\Logger\Logger1701 $logger1701
     * @param \Riki\ShipmentImporter\Logger\ShipmentShippedOut\Logger\Logger1801 $logger1801
     * @param \Riki\ShipmentImporter\Logger\ShipmentShippedOut\Logger\Logger1901 $logger1901
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $orderHistory
     * @param File $fileSystem
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\ShipmentImporter\Helper\Email $emailHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepository $shipmentRepository
     * @param ShipmentHistory $shipmentHistory
     * @param \Magento\Framework\Filesystem $filesystemReader
     * @param \Magento\Sales\Api\Data\ShipmentTrackInterface $shipmentTrack
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackRepository
     * @param \Magento\Sales\Api\ShipmentTrackRepositoryInterface $shipmentTrackRepositoryFactory
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Framework\App\AreaList $areaList
     * @param OrderStatusHelper $orderHelper
     * @param \Riki\ShipmentImporter\Helper\ShippedOutBucket $shippedOutBucket
     * @param \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface $shipmentSapExportedRepository
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Riki\ShipmentImporter\Logger\ShipmentShippedOut\Logger\Logger1501 $logger1501,
        \Riki\ShipmentImporter\Logger\ShipmentShippedOut\Logger\Logger1601 $logger1601,
        \Riki\ShipmentImporter\Logger\ShipmentShippedOut\Logger\Logger1701 $logger1701,
        \Riki\ShipmentImporter\Logger\ShipmentShippedOut\Logger\Logger1801 $logger1801,
        \Riki\ShipmentImporter\Logger\ShipmentShippedOut\Logger\Logger1901 $logger1901,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $orderHistory,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\ShipmentImporter\Helper\Email $emailHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepository $shipmentRepository,
        ShipmentHistory $shipmentHistory,
        \Magento\Framework\Filesystem $filesystemReader,
        \Magento\Sales\Api\Data\ShipmentTrackInterface $shipmentTrack,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackRepository,
        \Magento\Sales\Api\ShipmentTrackRepositoryInterface $shipmentTrackRepositoryFactory,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\AreaList $areaList,
        \Riki\ShipmentImporter\Helper\Order $orderHelper,
        \Riki\ShipmentImporter\Helper\ShippedOutBucket $shippedOutBucket,
        \Riki\SapIntegration\Api\ShipmentSapExportedRepositoryInterface $shipmentSapExportedRepository,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Magento\Framework\Registry $registry
    ) {
        $this->dataHelper = $dataHelper;
        $this->dateTime = $dateTime;
        $this->directoryList = $directoryList;
        $this->sftp = $sftp;
        $this->logger1501 = $logger1501;
        $this->logger1601 = $logger1601;
        $this->logger1701 = $logger1701;
        $this->logger1801 = $logger1801;
        $this->logger1901 = $logger1901;
        $this->timezone = $timezone;
        $this->orderHistory = $orderHistory;
        $this->fileSystem = $fileSystem;
        $this->customerFactory = $customerFactory;
        $this->emailHelper = $emailHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentFactory = $shipmentRepository;
        $this->shipmentHistory = $shipmentHistory;
        $this->fileSystemReader = $filesystemReader;
        $this->shipmentTrackInterface = $shipmentTrack;
        $this->shipmentTrackRepository = $shipmentTrackRepository;
        $this->state = $state;
        $this->areaList = $areaList;
        $this->shipmentTractCollectionFactory = $shipmentTrackRepositoryFactory;
        $this->orderHelper = $orderHelper;
        $this->shippedOutBucketHelper = $shippedOutBucket;
        $this->shipmentSapExportedRepository = $shipmentSapExportedRepository;
        $this->dbTransaction = $dbTransaction;
        $this->registry = $registry;
    }
    /**
     * Import shipment shipped out for warehouse TOYO
     */
    public function import1501()
    {
        $this->execute('1501');
    }
    /**
     * Import shipment shipped out for warehouse BIZEX
     */
    public function import1601()
    {
        $this->execute('1601');
    }
    /**
     * Import shipment shipped out for warehouse HITACH-TS
     */
    public function import1701()
    {
        $this->execute('1701');
    }
    /**
     * Import shipment shipped out for warehouse LOGICALPLANT
     */
    public function import1801()
    {
        $this->execute('1801');
    }
    /**
     * Import shipment shipped out for warehouse WH5
     */
    public function import1901()
    {
        $this->execute('1901');
    }

    /**
     * @param $warehouseId
     */
    public function getLogger($warehouseId)
    {
        switch ($warehouseId) {
            case '1501':
                $this->logger = $this->logger1501;
                break;
            case '1601':
                $this->logger = $this->logger1601;
                break;
            case '1701':
                $this->logger = $this->logger1701;
                break;
            case '1801':
                $this->logger = $this->logger1801;
                break;
            case '1901':
                $this->logger = $this->logger1901;
                break;
        }
    }
    /**
     * main function to import
     */
    public function execute($warehouseId)
    {
        $area = $this->areaList->getArea($this->state->getAreaCode());
        $area->load(\Magento\Framework\App\Area::PART_TRANSLATE);
        $taskName = __('Completion of Shipped Out (3PLWH) 1701');
        $originDate = $this->timezone->formatDateTime($this->dateTime->gmtDate(), \IntlDateFormatter::MEDIUM);
        $needDate = $this->dateTime->gmtDate('Y-m-d H:i:s', $originDate);
        $needDateFile = $this->dateTime->gmtDate('Ymd', $originDate);
        $needDateFileDone = $this->dateTime->gmtDate('YmdHis', $originDate);
        $this->getLogger($warehouseId);
        $this->shippedOutBucketHelper->setLogger($this->logger);
        $logFilename = "shipment$warehouseId.log";
        $this->dataHelper->backupLog($needDateFile, $logFilename, $warehouseId);
        $this->writeToLog($taskName . ' run at :' . $needDate);
        if (!$this->dataHelper->isEnable()) {
            $this->writeToLog(sprintf('%s has been disabled.', $taskName));
            $this->sendMailResult($warehouseId);
            return;
        }
        //check sftp
        if (!$this->dataHelper->checkSftpConnection($this->sftp)) {
            $this->writeToLog('Could not connect to sFTP.');
            $this->sendMailResult($warehouseId);
            return;
        }
        //check permission of folder
        $location = $this->dataHelper->getLocationSftp($warehouseId);
        if (!$this->dataHelper->checkSftpLocation($this->sftp, $location)) {
            $this->writeToLog(sprintf('Location :%s in sFTP does not exist', $location));
            $this->sendMailResult($warehouseId);
            return;
        }
        //begin read file
        $data = $this->getAllCsvFiles($needDateFileDone, $warehouseId);
        if ($data == 2) {
            $this->writeToLog('CSV files not found.');
            $this->sendMailResult($warehouseId);
            return;
        }
        foreach ($data as $filename => $content) {
            if ($content == 1) {
                $this->writeToLog(sprintf('Content of file %s is null', $filename));
            } elseif ($content == 2) {
                $this->writeToLog(sprintf('The file %s does not exist', $location . $filename));
            } else {
                $this->doImportShipments($content, $filename);
            }
        }
    }

    /**
     * @param $content
     * @param $filename
     */
    public function doImportShipments($content, $filename)
    {
        //process data
        foreach ($content as $key => $item) {
            $this->doImportShipmentForItem($item);
        }//end foreach
        $this->writeToLog(sprintf("File %s has been imported.", $filename));
    }

    /**
     * @param $item
     * @param $num
     */
    public function doImportShipmentForItem($item, $num = 0)
    {
        $error = 0;
        $shipKey = $item[2];
        $shipDate = $this->dateTime->date('Y-m-d', strtotime($item[6]));
        $systemDate = $this->dateTime->date('Y-m-d H:i:s');
        $isDateValid = $item[29];
        $sapFlag = $item[self::SAP_FLAG_INDEX];
        $item['shipDate'] = $shipDate;
        $item['systemDate'] = $systemDate;
        $item['sapFlag'] = $sapFlag;
        $limit = 100;
        if ($num > 1) {
            $this->writeToLog(sprintf('Try to import shipment #%s still failed', $shipKey));
            return;
        }

        if (!$error && $isDateValid) {
            try {
                $this->dbTransaction->beginTransaction();
                // Check if counter is > limit => disconnect & reset counter
                if ($this->registry->registry('last_time_email_sent')) {
                    if (time() - $this->registry->registry('last_time_email_sent') > $limit) {
                        // Disconnect current connection from mail server
                        // connection will be re-establish next time an email sending process is trigger
                        $zendTransport = \Magento\Framework\App\ObjectManager::getInstance()
                            ->get(\Zend\Mail\Transport\Smtp::class);
                        if ($zendTransport->getConnection()
                            && $zendTransport->getConnection()->hasSession()) {
                            $zendTransport->getConnection()->disconnect();
                            $this->registry->unregister('last_time_email_sent');
                        }
                    }
                }
                //
                if ($this->shippedOutBucketHelper->getBucketOrders($shipKey)) {
                    $this->writeToLog(sprintf('Process bucket shipment : %s,  date: %s', $shipKey, $item[6]));
                    $this->shippedOutBucketHelper->importBucketOrder($shipKey, $item);
                } else {
                    //normal shipment
                    $this->writeToLog(sprintf('Process normal shipment : %s, order date: %s', $shipKey, $item[6]));
                    $this->importNormalShipments($item, $shipKey, $shipDate, $sapFlag);
                }

                $this->dbTransaction->commit();
            } catch (\Exception $e) {
                $this->dbTransaction->rollback();
                $this->writeToLog(sprintf('The shipment number #%s has been rolled back', $shipKey));
                $this->logger->critical($e);

                // try to import shipment for this item.
                $this->doImportShipmentForItem($item, ++$num);
            }
        } else {
            $this->writeToLog(
                __(
                    'Error compatibility of imported data or imported date is invalid (%s)',
                    $shipDate
                )
            );
        }
    }

    /**
     * @param $item
     * @param $shipKey
     * @param $shipDate
     * @param $sapFlag
     */
    public function importNormalShipments($item, $shipKey, $shipDate, $sapFlag)
    {
        $importResult = $this->processShipment(
            $shipKey,
            $shipDate,
            ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT,
            $sapFlag
        );
        if ($importResult[0]) {
            // import shipment success and change order status
            $newShipment = $importResult[1];
            $shipmentsArray = $importResult[2];
            if ($newShipment instanceof \Magento\Sales\Model\Order\Shipment) {
                $order = $newShipment->getOrder();
                $orderNumber = $order->getIncrementId();
                $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();

                /*shipment payment status after shipped out - must be payment collected*/
                $shipmentPaymentStatus = $this->dataHelper->getShipmentPaymentStatusAfterShippedOut(
                    $paymentMethod,
                    $order
                );
                if ($shipmentPaymentStatus) {
                    $newShipment->setData(
                        'payment_status',
                        PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
                    );
                    $newShipment->setData('payment_date', $shipDate);
                    $newShipment->setData('collection_date', $shipDate);
                }

                if ($paymentMethod == \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE) {
                    /*
                    * set flag_export_invoice_sales_shipment = 0
                    * (waiting for export to bi - invoice shipment)
                    */
                    $newShipment->setData('flag_export_invoice_sales_shipment', 0);
                }
                //add Tracking Number here
                $this->processShipmentTracking($item, $newShipment);
                //process order
                $currentStatus = $this->orderHelper->getCurrentOrderStatusBaseOnShipments(
                    $shipmentsArray
                );

                // NED-1851 set data on object on fly to
                // avoid wrong check for status in app/code/Riki/Loyalty/Observer/OrderCompleted.php
                foreach ($order->getShipmentsCollection() as $shipment) {
                    if ($shipment->getId() == $newShipment->getId()) {
                        $shipment->setShipmentStatus($newShipment->getShipmentStatus());
                        $shipment->setPaymentStatus($newShipment->getPaymentStatus());
                    }
                }

                if ($currentStatus == OrderStatusHelper::STEP_SHIPPED_ALL) {
                    //process order
                    $this->processOrder($order, $paymentMethod, $item);
                } elseif ($currentStatus == OrderStatusHelper::STEP_PARTIALL_SHIPPED) {
                    $shipmentStatus = ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT_PARTIAL;
                    $orderStatus = OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED;
                    $order->setShipmentStatus($shipmentStatus);
                    $order->setStatus($orderStatus);
                    //update order history
                    $order->addStatusToHistory(
                        $orderStatus,
                        __('Imported from 3PL - Shipment Shipped out, shipment number: ').$shipKey,
                        false
                    );
                }
                //update shipment and order
                $this->updateResult($newShipment, $shipKey, $paymentMethod, $order);
            }
        }
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
            $this->writeToLog(sprintf('Location :%s in sFTP does not exist', $sftpLocation));
            return 2;
        } else {
            return $this->getSftpFiles($needDateFile, $warehouseId);
        }
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
        $sfptRoot = $this->sftp->pwd();
        if ($sfptRoot != DIRECTORY_SEPARATOR) {
            $location = $sfptRoot . $this->dataHelper->getLocationSftp($warehouseId);
        } else {
            $location = $this->dataHelper->getLocationSftp($warehouseId);
        }
        $this->sftp->cd($location);
        $files = $this->sftp->rawls();
        $localPath = $baseDir . '/import';
        $localPathShort = 'import';
        if (!$this->fileSystem->isExists($localPath)) {
            $this->fileSystem->createDirectory($localPath, 0777);
        }
        $filesall = $this->csvFileFilter($files, $pattern, $location, $localPath, $needDateFile, $warehouseId);
        $data = $this->getCsvFilesContent($filesall, $location, $needDateFile);
        $this->sftp->close();
        if (!empty($data)) {
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
    public function moveToError($needDateFile, $filename, $warehouseId, $ext = false)
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
     * @param $warehouseId
     */
    public function sendMailResult($warehouseId)
    {
        $taskName = 'Import shipment shipped-out file from WMS';
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
     * @param $shipKey
     * @param $shipDate
     * @param $importStatus
     * @param null $sapFlag
     * @return array
     */
    public function processShipment($shipKey, $shipDate, $importStatus, $sapFlag = null)
    {
        $shipment = $this->getSingleShipment($shipKey);
        //status which available to be import
        $allowStatusShipment = [
            ShipmentStatus::SHIPMENT_STATUS_EXPORTED,
            ShipmentStatus::SHIPMENT_STATUS_REJECTED,
            ShipmentStatus::SHIPMENT_STATUS_CREATED,
        ];
        $originDate = $this->timezone->formatDateTime(
            $this->dateTime->gmtDate(),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM
        );
        $systemDate = $this->dateTime->date('Y-m-d H:i:s');
        if ($shipment->getId()) {
            $orderId = $shipment->getOrderId();
            $shipsArray = $this->orderHelper->getShipmentsArray($orderId);
            //verify shipment again
            $shipmentStatus = $shipment->getShipmentStatus();
            if (in_array($shipmentStatus, $allowStatusShipment)) {
                //change shipment status in array
                if (array_key_exists($shipKey, $shipsArray)) {
                    $shipsArray[$shipKey] = $importStatus;
                }
                // change status
                $statusDate = $shipDate;
                /*update shipment status -> shipped_out*/
                $shipment->setShipmentStatus($importStatus);
                $shipment->setIsReconciliationExported(1);
                /*System date when we receive the ship-out message*/
                $shipment->setShipmentDate($systemDate);
                /*The actual Ship-out date mentioned in the ship-out message*/
                $shipment->setShippedOutDate($statusDate);
                /*Waiting for export to SAP for shipped_out shipment*/

                /** $sapFlag in (0, 1, null) */
                if (trim($sapFlag) === '0') { //0 <=> import 0
                    $shipment->setIsExportedSap(Shipment::NO_NEED_TO_EXPORT);
                } else { // 1 and null <=> import 1
                    $shipment->setIsExportedSap(Shipment::WAITING_FOR_EXPORT);
                }

                //add History
                $historyData =
                    [
                        'shipment_date' => $shipDate,
                        'shipment_status' => $importStatus,
                        'shipment_id' => $shipment->getId()
                    ];
                $this->shipmentHistory->addShipmentHistory($historyData);
                return [true, $shipment, $shipsArray];
            } else {
                $this->writeToLog(
                    sprintf(
                        'Shipment Status (%s) is not available to be import for shipment number: %s',
                        $shipmentStatus,
                        $shipKey
                    )
                );
            }
        } else {
            $this->writeToLog(sprintf('The shipment number %s does not exist', $shipKey));
        }
        return [false];
    }
    /**
     * Check this shipment will be change to payment collected
     *
     * @param $paymentMethod
     * @param $orderStatus
     * @return bool
     */
    public function isShipmentPaymentCollected($paymentMethod, $orderStatus)
    {
        if (in_array($paymentMethod, ["cvspayment", "paygent", "invoicedbasedpayment", "free"])) {
            if ($orderStatus == OrderStatus::STATUS_ORDER_CAPTURE_FAILED) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @param $item
     * @param $shipment
     */
    protected function processShipmentTracking($item, & $shipment)
    {
        $trackingNumber = $item[5];
        if ($trackingNumber) {
            if (!$this->checkExistTracking($trackingNumber, $shipment->getEntityId())) {
                $trackings = explode(";", $item[5]);
                if ($item[9]) {
                    $this->shippedOutBucketHelper->importTracking($item, $trackings, $shipment);
                }
            } else {
                $this->writeToLog(__(
                    'Tracking number %1 is already exists in shipment : %2',
                    $trackingNumber,
                    $shipment->getIncrementId()
                ));
            }
        }
    } //end function
    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $paymentMethod
     * @param $item
     */
    protected function processOrder(& $order, $paymentMethod, $item)
    {
        $orderId = $order->getId();
        $orderNumber = $order->getIncrementId();
        $statusData['order_id'] = $orderId;
        $statusData['order_increment_id'] = $order->getIncrementId();

        $status = \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SHIPPED_ALL;
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->addStatusToHistory($status, 'Imported from 3PL')
            ->setIsCustomerNotified(false);
        //update Shipment Status for Order
        $order->setShipmentStatus(
            ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT
        );

        $statusData = [
            'order_id' => $order->getId(),
            'order_increment_id' => $order->getIncrementId(),
            'status_date' => $item[6],
            'status_shipment' => $status
        ];
        //update Payment Status for InvoicedbasePayment, free only
        if (in_array($paymentMethod, [\Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE, 'free']) ||
            $order->getFreeOfCharge()
        ) {
            $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
            $order->setPaymentStatus($paymentStatus);
            $this->writeToLog(sprintf('Change payment status of order %s to be payment collected', $orderNumber));
            $statusData['status_payment'] = $paymentStatus;
        }
        $this->dataHelper->addOrderStatusHistory($statusData);
        $this->writeToLog(sprintf('Change status of order %s to be %s', $orderNumber, $status));
    }

    /**
     * @param $incrementId
     * @return \Magento\Sales\Api\Data\ShipmentInterface
     * @throws \Exception
     */
    protected function getSingleShipment($incrementId)
    {
        try {
            $criteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId)
                ->addFilter('ship_zsim', 1, 'neq')
                ->create();
            $shipmentCollection = $this->shipmentRepository->getList($criteria);
            if ($shipmentCollection->getSize()) {
                foreach ($shipmentCollection->getItems() as $item) {
                    return $item;
                }
            } else {
                return $this->shipmentFactory->create();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param $trackingNumber
     * @param $shipmentId
     * @return bool
     */
    public function checkExistTracking($trackingNumber, $shipmentId)
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('track_number', $trackingNumber)
            ->addFilter('parent_id', $shipmentId)
            ->create();
        $trackingCollection = $this->shipmentTractCollectionFactory->getList($criteria);
        if ($trackingCollection->getTotalCount()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $message
     */
    public function writeToLog($message)
    {
        if ($this->dataHelper->isEnableLogger()) {
            $this->logger->info($message);
        }
    }

    /**
     * @param $newShipment
     * @param $shipKey
     * @param $paymentMethod
     * @param $order
     * @throws \Exception
     */
    public function updateResult($newShipment, $shipKey, $paymentMethod, $order)
    {
        try {
            $newShipment->save();
            $this->writeToLog(sprintf(
                'The shipment number %s has been changed to %s',
                $shipKey,
                ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT
            ));

            /*sync data for shipment sap exported after shipment was shipped out*/
            $this->syncDataForShipmentSapExported($newShipment);

            /*update order payment agent after change status*/
            if ($paymentMethod == \Bluecom\Paygent\Model\Paygent::CODE
                && empty($order->getData('payment_agent'))
            ) {
                $paymentAgent = $this->dataHelper->getPaymentAgentByOrderIncrementId(
                    $order->getIncrementId()
                );
                if (!empty($paymentAgent)) {
                    $order->setData('payment_agent', $paymentAgent);
                }
            }

            $this->registry->unregister('is_emulated');
            $this->registry->register('is_emulated',1);
            $order->save();
        } catch (\Exception $e) {
            $this->writeToLog($e->getMessage());
            if ($this->dataHelper->hasMysqlDeadLock($e)) {
                $this->writeToLog(__('Mysql Error: Deadlock found when trying to get lock'));
            }
            if ($this->dataHelper->hasMysqlLockWaitTimeOut($e)) {
                $this->writeToLog(__('Mysql Error: Lock wait timeout exceeded'));
            }
            throw $e;
        }
    }

    /**
     * @param $files
     * @param $pattern
     * @param $location
     * @param $localPath
     * @param $needDateFile
     * @param $warehouseId
     * @return array
     */
    public function csvFileFilter($files, $pattern, $location, $localPath, $needDateFile, $warehouseId)
    {
        $filesall = [];
        foreach ($files as $key => $file) {
            if (!in_array($key, ['.', '..'])) {
                $pre = preg_match($pattern, $key);
                if ($pre) {
                    $extension = substr($key, strrpos($key, '.')); // Gets the File Extension
                    if (strtolower($extension) == ".csv") {
                        $filesall[] = $key; // Store in Array
                        $this->sftp->read($location . DS . $key, $localPath . DS . $key);
                    } else {
                        //move to error
                        $this->writeToLog(sprintf('File: %s is not CSV extension', $key));
                        $this->moveToError($needDateFile, $key, $warehouseId, true);
                    }
                } else {
                    //move to error
                    $this->writeToLog(sprintf(
                        'File: %s does not match the configed pattern name (%s).',
                        $key,
                        $pattern
                    ));
                    $this->moveToError($needDateFile, $key, $warehouseId, true);
                }
            }
        }
        return $filesall;
    }

    /**
     * @param $location
     * @param $needDateFile
     * @return array
     */
    public function getCsvFilesContent($filesall, $location, $needDateFile)
    {
        $baseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $remoteFolder = "remote";
        $doneFolder = "done";
        $localPath = $baseDir . '/import';
        $localPathShort = 'import';
        if (!$this->fileSystem->isExists($localPath)) {
            $this->fileSystem->createDirectory($localPath, 0777);
        }
        $data = [];
        if (!empty($filesall)) {
            foreach ($filesall as $filename) {
                if ($this->fileSystem->isExists($localPath . DS . $filename)) {
                    $newfilename = str_replace('.csv', '_' . $needDateFile . '.csv', $filename);
                    $done = str_replace($remoteFolder, $doneFolder, $location . DS . $newfilename);
                    //remove if already exist in done folder
                    $this->sftp->rm($done);
                    $this->sftp->mv($location . DS . $filename, $done);
                    $this->dataHelper->convertEncodeFile($filename);
                    $contentFile = $this->dataHelper->getCsvData(
                        $baseDir . DS . $localPathShort . DS .
                        $filename,
                        true,
                        true
                    );
                    if ($contentFile == null || $contentFile == '') {
                        $data[$filename] = 1;
                    } else {
                        $data[$filename] = $contentFile;
                    }
                    try {
                        if ($this->fileSystem->isExists($localPath . DS . $filename)) {
                            $this->fileSystem->deleteFile($localPath . DS . $filename);
                        }
                    } catch (\Exception $e) {
                        $this->writeToLog($e->getMessage());
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Change SAP flag after shipment was shipped out
     *
     * @param $shipment
     */
    protected function syncDataForShipmentSapExported(
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        try {
            $shipmentSapExported = $this->shipmentSapExportedRepository->getById($shipment->getId());
        } catch (\Exception $e) {
            $this->writeToLog('Cannot get SAP data for shipment #'.$shipment->getIncrementId());
            return;
        }

        $shipmentSapExported->setIsExportedSap($shipment->getIsExportedSap());

        try {
            $this->shipmentSapExportedRepository->save($shipmentSapExported);
            $this->writeToLog(
                'SAP flag of shipment #'.$shipment->getIncrementId().
                ' has been changed to '.$shipmentSapExported->getIsExportedSap()
            );
        } catch (\Exception $e) {
            $this->writeToLog(
                'Cannot change SAP flag to '.$shipment->getIsExportedSap().' for shipment #'.$shipment->getIncrementId()
            );
            return;
        }
    }
}
