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
use Magento\Framework\Filesystem;
use Riki\Sales\Helper\Order;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order\ShipmentRepository;
use Riki\Shipment\Helper\ShipmentHistory;
use Riki\Shipment\Helper\Data as ShipmentHelper;
use Riki\ShipmentImporter\Helper\Order as OrderStatusHelper;

/**
 * Class ShipmentComplete
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ShipmentComplete
{
    /**
     * @var
     */
    protected $dateTime;
    /**
     * @var
     */
    protected $orderCollectionFactory;
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
     * @var \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1502
     */
    protected $logger1502;
    /**
     * @var \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1602
     */
    protected $logger1602;
    /**
     * @var \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1702
     */
    protected $logger1702;
    /**
     * @var \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1802
     */
    protected $logger1802;
    /**
     * @var \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1902
     */
    protected $logger1902;
    /**
     * @var
     */
    protected $timezone;
    /**
     * @var
     */
    protected $fileSystem;
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
     * @var OrderStatusHelper
     */
    protected $orderStatusHelper;
    /**
     * @var ShipmentHelper
     */
    protected $shipmentHelper;
    /**
     * @var File
     */
    protected $fileObject;

    protected $shipmentCompleteBucketHelper;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Helper\Data
     */
    private $disengageProfileHelper;

    /**
     * ShipmentComplete constructor.
     * @param \Riki\ShipmentImporter\Helper\Data $dataHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param DirectoryList $directoryList
     * @param Sftp $sftp
     * @param \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1502 $logger1502
     * @param \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1602 $logger1602
     * @param \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1702 $logger1702
     * @param \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1802 $logger1802
     * @param \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1902 $logger1902
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param File $fileSystem
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepository $shipmentRepository
     * @param ShipmentHistory $shipmentHistory
     * @param Filesystem $filesystemReader
     * @param OrderStatusHelper $orderStatusHelper
     * @param ShipmentHelper $data
     * @param File $fileObject
     * @param \Riki\ShipmentImporter\Helper\CompleteBucket $completeBucket
     * @param Order $orderHelper
     * @param \Riki\SubscriptionProfileDisengagement\Helper\Data $disengageProfileHelper
     */
    public function __construct(
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1502 $logger1502,
        \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1602 $logger1602,
        \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1702 $logger1702,
        \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1802 $logger1802,
        \Riki\ShipmentImporter\Logger\ShipmentComplete\Logger\Logger1902 $logger1902,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepository $shipmentRepository,
        ShipmentHistory $shipmentHistory,
        Filesystem $filesystemReader,
        OrderStatusHelper $orderStatusHelper,
        ShipmentHelper $data,
        \Magento\Framework\Filesystem\Driver\File $fileObject,
        \Riki\ShipmentImporter\Helper\CompleteBucket $completeBucket,
        \Riki\Sales\Helper\Order $orderHelper,
        \Riki\SubscriptionProfileDisengagement\Helper\Data $disengageProfileHelper
    ) {

        $this->dataHelper = $dataHelper;
        $this->dateTime = $dateTime;
        $this->directoryList = $directoryList;
        $this->sftp = $sftp;
        $this->timezone = $timezone;
        $this->fileSystem = $fileSystem;
        $this->logger1502 = $logger1502;
        $this->logger1602 = $logger1602;
        $this->logger1702 = $logger1702;
        $this->logger1802 = $logger1802;
        $this->logger1902 = $logger1902;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentFactory = $shipmentRepository;
        $this->shipmentHistory = $shipmentHistory;
        $this->fileSystemReader = $filesystemReader;
        $this->orderStatusHelper = $orderStatusHelper;
        $this->shipmentHelper = $data;
        $this->fileObject = $fileObject;
        $this->shipmentCompleteBucketHelper = $completeBucket;
        $this->orderHelper = $orderHelper;
        $this->disengageProfileHelper = $disengageProfileHelper;
    }

    /**
     * import Toyo warehouse
     */
    public function import1502()
    {
        $this->execute('1502');
    }
    /**
     * import Bizex warehouse
     */
    public function import1602()
    {
        $this->execute('1602');
    }
    /**
     * import Hitachi warehouse
     */
    public function import1702()
    {
        $this->execute('1702');
    }
    /**
     * import Logical warehouse
     */
    public function import1802()
    {
        $this->execute('1802');
    }
    /**
     * import WH5 warehouse
     */
    public function import1902()
    {
        $this->execute('1902');
    }
    /**
     * main function importing
     */
    public function execute($warehouseId)
    {
        $taskName = 'Delivery complete shipment flow from 3PL (TOYO)';
        $originDate = $this->timezone->formatDateTime($this->dateTime->gmtDate(), \IntlDateFormatter::MEDIUM);
        $needDate = $this->dateTime->gmtDate('Y-m-d H:i:s', $originDate);
        $needDateFile = $this->dateTime->gmtDate('Ymd', $originDate);
        $needDateFileDone = $this->dateTime->gmtDate('YmdHis', $originDate);

        $this->getLogger($warehouseId);
        $this->shipmentCompleteBucketHelper->setLogger($this->logger);
        $logFilename = "shipment$warehouseId.log";
        $this->dataHelper->backupLog($needDateFile, $logFilename, $warehouseId);

        $this->writeToLog($taskName . ' run at :' . $needDate);
        if (!$this->dataHelper->isEnable()) {
            $this->writeToLog('Delivery complete shipments importing has been disabled.');
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
                $this->writeToLog(sprintf("Content of file %s is null", $filename));
            } elseif ($content == 2) {
                $this->writeToLog(sprintf("The file %s does not exist", $location . $filename));
            } else {
                $this->doImportShipments($content);
                $this->writeToLog(sprintf("File %s has been imported.", $filename));
            }//end else
        }//end foreach
    }

    /**
     * @param $content
     */
    public function doImportShipments($content)
    {
        foreach ($content as $key => $item) {
            $shipKey = $item[24];
            $shipmentDate = date('Y-m-d', strtotime($item[15]));
            $isRejected = (int)($item[1]);
            if ($shipmentDate == '1970-01-01' ||
                $this->dataHelper->validateDate($shipmentDate, 'Y-m-d') == false) {
                $shipmentDate = date('Y-m-d H:i:s');
            }

            if (!$shipKey) {
                $this->writeToLog('Shipment number in CSV column is invalid');
            } else {
                $bucketOrders = $this->shipmentCompleteBucketHelper->getBucketOrders($shipKey);
                if ($bucketOrders) {
                    $bucketData = [
                        'isRejected' => $isRejected,
                        'shipmentDate' => $shipmentDate,
                        'systemDate' =>  $this->dateTime->date('Y-m-d H:i:s')
                    ];
                    $this->writeToLog(sprintf("Begin process bucket Id: %s", $shipKey));
                    $this->shipmentCompleteBucketHelper->importBucketOrder($bucketOrders, $bucketData);
                } else {
                    $this->writeToLog(sprintf("Begin process shipment: %s", $shipKey));
                    $this->importNormalShipments($shipKey, $shipmentDate, $isRejected);
                }
            }
        }
    }

    /**
     * @param $shipKey
     * @param $shipmentDate
     * @param $isRejected
     */
    public function importNormalShipments($shipKey, $shipmentDate, $isRejected)
    {
        $importResult = $this->processShipment1502(
            $shipKey,
            $shipmentDate,
            ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
            $isRejected
        );
        if (!empty($importResult)) {
            $newShipment = $importResult[1];
            if ($newShipment instanceof \Magento\Sales\Model\Order\Shipment) {
                $order = $importResult[2];
                $shipmentsArray = $importResult[3];
                $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
                //process order after import shipment
                $currentStatus = $this->orderStatusHelper
                    ->getCurrentOrderStatusBaseOnShipments($shipmentsArray);
                $allowOrderStatus = [
                    OrderStatusHelper::STEP_DELIVERY_COMPLETED
                ];

                // NED-1851 set data on object on fly to
                // avoid wrong check for status in app/code/Riki/Loyalty/Observer/OrderCompleted.php
                foreach ($order->getShipmentsCollection() as $shipment) {
                    if ($shipment->getId() == $newShipment->getId()) {
                        $shipment->setShipmentStatus($newShipment->getShipmentStatus());
                        $shipment->setPaymentStatus($newShipment->getPaymentStatus());
                    }
                }
                if (in_array($currentStatus, $allowOrderStatus)) {
                    $this->updateOrderFinal($order, $paymentMethod, $shipmentDate, $newShipment);
                }
                //update shipment and order
                $this->updateResult($newShipment, $order, $shipKey, $importResult);
            }
        } else {
            $this->writeToLog(sprintf("Import shipment: %s failed", $shipKey));
        }
    }
    /**
     * @param $newShipment
     * @param $order
     * @param $shipKey
     * @param $importResult
     */
    public function updateResult($newShipment, $order, $shipKey, $importResult)
    {
        try {
            $newShipment->save();
            $this->writeToLog(sprintf(
                'Delivery complete shipment %s (%s)',
                $shipKey,
                $importResult[4]
            ));
            $order->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
    /**
     * @param $shipKey
     * @param $shipDate
     * @param $status
     * @param $isRejected
     * @return array
     */
    public function processShipment1502($shipKey, $shipDate, $status, $isRejected)
    {
        $shipment = $this->getSingleShipment($shipKey);
        //status which available to be import
        $allowStatusShipment = [
            ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT,
            ShipmentStatus::SHIPMENT_STATUS_REJECTED
        ];
        $originDate = $this->timezone->formatDateTime($this->dateTime->gmtDate(), \IntlDateFormatter::MEDIUM);
        $systemDate = $this->dateTime->gmtDate('Y-m-d H:i:s', $originDate);

        if ($shipment->getId()) {
            $order = $shipment->getOrder();
            $orderStatus = $order->getStatus();
            $paymentMethod = $order->getPayment()->getMethod();
            $shipmentStatus = $shipment->getShipmentStatus();
            $orderId = $shipment->getOrderId();
            $shipsArray = $this->orderStatusHelper->getShipmentsArray($orderId);
            //import
            if (in_array($shipmentStatus, $allowStatusShipment)) {
            // Reject shipment
                if ($isRejected) {
                    $rejectStatus = ShipmentStatus::SHIPMENT_STATUS_REJECTED;
                    //change shipment status in array
                    if (array_key_exists($shipKey, $shipsArray)) {
                        $shipsArray[$shipKey] = $rejectStatus;
                    }
                    $shipment->setData('shipment_status', $rejectStatus)
                        ->setData('shipment_date', $shipDate);

                    /*change shipment payment status to not applicable for cod shipment*/
                    if ($paymentMethod ==
                        \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
                        $shipment->setData(
                            'payment_status',
                            \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_NOT_APPLICABLE
                        );
                    }

                    //add history to order
                    $order->addStatusToHistory(
                        $order->getStatus(),
                        __('Rejected shipment number by Complete shipment: ' . $shipment->getIncrementId()),
                        false
                    );
                    //add History
                    $historyData = [
                        'shipment_status' => $rejectStatus,
                        'shipment_id' => $shipment->getId(),
                        'shipment_date' =>$systemDate
                    ];
                    $this->shipmentHistory->addShipmentHistory($historyData);
                    return [true,$shipment,$order, $shipsArray, $rejectStatus];
                } else {
                    //check if shipment can be a valid importing
                    if (in_array($shipmentStatus, $allowStatusShipment)) {
                    //change shipment status in array
                        if (array_key_exists($shipKey, $shipsArray)) {
                            $shipsArray[$shipKey] = $status;
                        }

                        /*update shipment status -> delivery_complete*/
                        $shipment->setShipmentStatus($status);
                        /*System date when we receive the delivery-complete message*/
                        //$shipment->setShipmentDate($systemDate);
                        /*The actual delivery completion date mentioned in the Delivery completion message*/
                        $shipment->setDeliveryCompleteDate($shipDate);
                        //update order history
                        $order->addStatusToHistory(
                            $orderStatus,
                            __('Imported from 3PL - Completion delivery, shipment number:') .
                            $shipment->getIncrementId(),
                            false
                        );
                        //add History
                        $historyData =
                            [
                                'shipment_date' => $systemDate,
                                'shipment_status' => $status,
                                'shipment_id' => $shipment->getId()
                            ];
                        $this->shipmentHistory->addShipmentHistory($historyData);
                        return [true,$shipment,$order, $shipsArray, $status];
                    } else {
                        $this->writeToLog(
                            sprintf('Shipment Status (%s) is not available to be 
                            import shipment complete for shipment number: %s', $shipmentStatus, $shipKey)
                        );
                        $this->writeToLog(
                            sprintf('Current status of order: (%s) ', $orderStatus)
                        );
                        return [false,$shipment,$order, $shipsArray,$status];
                    }
                }
            } else {
                $this->writeToLog(
                    sprintf('Shipment Status (%s) is not available to be import 
                    shipment complete for shipment number: %s', $shipmentStatus, $shipKey)
                );
                $this->writeToLog(
                    sprintf('Current status of order: (%s) ', $orderStatus)
                );
                return [false,$shipment,$order, $shipsArray,$status];
            }
        } else {
            $this->writeToLog(sprintf('The Shipment number %s does not exist', $shipKey));
        }
        return [];
    }
    /*
     * Send the log result
     */
    public function sendMailResult($warehouseId)
    {
        $taskName = 'shipment complete';
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
        $filesall = [];
        $localPath = $baseDir . '/import';
        if (!$this->fileObject->isExists($localPath)) {
            $this->fileObject->createDirectory($localPath, 0777);
        }
        foreach ($files as $key => $file) {
            $pre = preg_match($pattern, $key);
            if ($pre) {
                $extension = substr($key, strrpos($key, '.')); // Gets the File Extension
                if ($extension == ".csv") {
                    $filesall[] = $key; // Store in Array
                    $this->sftp->read($location . DS . $key, $localPath . DS . $key);
                } // Extensions Allowed
            }
        }
        $data = $this->getSftpFileContent($filesall, $baseDir, $localPath, $location, $needDateFile);
        $this->sftp->close();
        if (!empty($data)) {
            return $data;
        } else {
            return 2;
            //import file does not exists
        }
    }

    /**
     * @param $baseDir
     * @param $localPath
     * @param $location
     * @param $needDateFile
     * @return array
     */
    public function getSftpFileContent($filesall, $baseDir, $localPath, $location, $needDateFile)
    {
        $remoteFolder = "remote";
        $doneFolder = "done";
        $localPathShort = 'import';
        $data = [];
        if (!empty($filesall)) {
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

                    if ($this->fileObject->isExists($localPath . DS . $filename)) {
                        $this->fileObject->deleteFile($localPath . DS . $filename);
                    }
                }
            }
        }
        return $data;
    }
    /**
     * @param $order
     * @param $paymentMethod
     * @param $shipmentDate
     * @param $shipment
     */
    protected function updateOrderFinal(&$order, $paymentMethod, $shipmentDate, $shipment)
    {
        $orderId = $order->getId();
        $createInvoice = true;
        if ($paymentMethod == \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
                $createInvoice = $this->canCreateInvoiceForCodOrder($orderId, $shipment);
        }

        if ($this->orderHelper->isDelayPaymentOrder($order)
            && !$this->disengageProfileHelper->isDisengageMode($order->getData('subscription_profile_id'))) {
            $createInvoice = false;
        }
        if ($createInvoice) {
            $this->dataHelper->createInvoiceOrder($order, $shipmentDate);
        } else {
            $order->setStatus(OrderStatus::STATUS_ORDER_SHIPPED_ALL);
            $order->setShipmentStatus(
                ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
            );
            $order->addStatusHistoryComment(
                'Completion of delivery'
            )->setIsCustomerNotified(false);
        }
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
     * Check this order can create invoice or not
     *
     * @param $orderId
     * @param $shipment
     * @return bool
     */
    public function canCreateInvoiceForCodOrder($orderId, $shipment)
    {
        /*list of payment status which can create invoice*/
        $allowedPaymentStatus = [
            PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
            PaymentStatus::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE
        ];
        $allowedShipmentStatus = [
            ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
            ShipmentStatus::SHIPMENT_STATUS_REJECTED
        ];
        /*can not create invoice for case which current shipment payment status is not allowed*/
        if (!in_array($shipment->getPaymentStatus(), $allowedPaymentStatus)) {
            if ($shipment->getData('grand_total')
                && $shipment->getData('grand_total') != $shipment->getData('base_shopping_point_amount')) {
                return false;
            }
        }
        $criteria = $this->searchCriteriaBuilder->addFilter(
            'order_id',
            $orderId
        )->addFilter(
            'ship_zsim',
            1,
            'neq'
        )->addFilter(
            'entity_id',
            $shipment->getId(),
            'neq'
        )->create();

        /** @var \Magento\Sales\Api\Data\ShipmentSearchResultInterface $shipmentCollection */
        $shipmentCollection = $this->shipmentRepository->getList($criteria);

        $rs = false;
        if ($shipmentCollection->getTotalCount()) {
            foreach ($shipmentCollection->getItems() as $item) {
                if ($item->getShipmentStatus()==ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
                    && $item->getPaymentStatus() == PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
                ) {
                    $rs = true;
                } elseif ($item->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_REJECTED
                ) {
                    $rs = true;
                } elseif ($item->getShipmentStatus()==ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
                    && (!$item->getData('grand_total') ||
                        $item->getData('grand_total')== $item->getData('base_shopping_point_amount'))
                ) {
                    $rs = true;
                } else {
                    /*cannot create invoice if exist any shipment which did not rejected or collected*/
                    return false;
                }
            }
        } else {
            if (in_array($shipment->getPaymentStatus(), $allowedPaymentStatus)
                && in_array($shipment->getShipmentStatus(), $allowedShipmentStatus)) {
                $rs = true;
            }
        }
        return $rs;
    }
    /**
     * @param $warehouseId
     */
    public function getLogger($warehouseId)
    {
        switch ($warehouseId) {
            case '1502':
                $this->logger = $this->logger1502;
                break;
            case '1602':
                $this->logger = $this->logger1602;
                break;
            case '1702':
                $this->logger = $this->logger1702;
                break;
            case '1802':
                $this->logger = $this->logger1802;
                break;
            case '1902':
                $this->logger = $this->logger1902;
                break;
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
     * @param $message
     */
    public function writeToLog($message)
    {
        if ($this->dataHelper->isEnableLogger()) {
            $this->logger->info($message);
        }
    }
}//end class
