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
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\Order\ShipmentFactory;
use Riki\Shipment\Helper\ShipmentHistory;
use Riki\Shipment\Helper\Data as ShipmentHelper;
use Riki\ShipmentImporter\Helper\Order as OrderStatusHelper;

/**
 * Class ShipmentImporter1501
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ShipmentImporter1502
{
    /**
     * @var
     */
    protected $_dateTime;
    /**
     * @var
     */
    protected $_orderCollectionFactory;
    /**
     * @var
     */
    protected $_dataHelper;
    /**
     * UpdateOrder constructor.
     */
    protected $_directoryList;
    /**
     * @var
     */
    protected $_sftp;
    /**
     * @var
     */
    protected $_logger;
    /**
     * @var
     */
    protected $_timezone;
    /**
     * @var
     */
    protected $_fileSystem;
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
    protected $_fileSystemReader;
    /**
     * @var OrderStatusHelper
     */
    protected $_orderStatusHelper;
    /**
     * @var ShipmentHelper
     */
    protected $shipmentHelper;
    /**
     * ShipmentImporter1502 constructor.
     * @param \Riki\ShipmentImporter\Helper\Data $dataHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param DirectoryList $directoryList
     * @param Sftp $sftp
     * @param \Riki\ShipmentImporter\Logger\Logger1502 $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param File $fileSystem
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepository $shipmentRepository
     * @param ShipmentFactory $shipmentFactory
     * @param ShipmentHistory $shipmentHistory
     * @param Filesystem $filesystemReader
     * @param OrderStatusHelper $orderStatusHelper
     */
    public function __construct(
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Riki\ShipmentImporter\Logger\Logger1502 $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepository $shipmentRepository,
        ShipmentFactory $shipmentFactory,
        ShipmentHistory $shipmentHistory,
        Filesystem $filesystemReader,
        OrderStatusHelper $orderStatusHelper,
        ShipmentHelper $data
    )

    {
        $this->_dataHelper = $dataHelper;
        $this->_dateTime = $dateTime;
        $this->_directoryList = $directoryList;
        $this->_sftp = $sftp;
        $this->_logger = $logger;
        $this->_timezone = $timezone;
        $this->_logger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_fileSystem = $fileSystem;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentFactory = $shipmentRepository;
        $this->shipmentHistory = $shipmentHistory;
        $this->_fileSystemReader = $filesystemReader;
        $this->_orderStatusHelper = $orderStatusHelper;
        $this->shipmentHelper = $data;
    }

    /**
     *
     */
    public function execute()
    {
        //1502
        $taskName = 'Delivery confirmation flow from 3PL (TOYO)';
        $originDate = $this->_timezone->formatDateTime($this->_dateTime->gmtDate(), \IntlDateFormatter::MEDIUM);
        $needDate = $this->_dateTime->gmtDate('Y-m-d H:i:s', $originDate);
        $needDateFile = $this->_dateTime->gmtDate('Ymd', $originDate);
        $needDateFileDone = $this->_dateTime->gmtDate('YmdHis', $originDate);
        $this->_dataHelper->backupLog($needDateFile, 'shipment1502.log', '1502');
        $this->_logger->info($taskName . ' run at :' . $needDate);
        if (!$this->_dataHelper->isEnable()) {
            $this->_logger->info('Shipment importing 1502 has been disabled.');
            $this->sendMailResult();
            return;
        }
        //check sftp
        if (!$this->_dataHelper->checkSftpConnection($this->_sftp)) {
            $this->_logger->info('Could not connect to sFTP.');
            $this->sendMailResult();
            return;
        }
        //check permission of folder
        $location = $this->_dataHelper->getLocation1502();
        if (!$this->_dataHelper->checkSftpLocation($this->_sftp, $location)) {
            $this->_logger->info(sprintf('Location :%s in sFTP does not exist', $location));
            $this->sendMailResult();
            return;
        }
        //begin read file
        $data = $this->getSftpFiles($needDateFileDone);
        if ($data == 2) {
            $this->_logger->info('CSV files not found.');
            $this->sendMailResult();
            return;
        }
        $path = $this->_dataHelper->getLocation1502();
        foreach ($data as $filename => $content) {
            $error = 0;
            if ($content == 1) {
                $error++;
                $this->_logger->info(sprintf("Content of file %s is null", $filename));

            } elseif ($content == 2) {
                $error++;
                $this->_logger->info(sprintf("The file %s does not exist", $path . $filename));

            }
            else
            {
                foreach ($content as $key => $item)
                {
                    $shipKey = $item[24];
                    $shipmentDate = date('Y-m-d', strtotime($item[15]));
                    $isRejected = intval($item[1]);
                    if ($shipmentDate == '1970-01-01' || $this->_dataHelper->validateDate($shipmentDate,'Y-m-d') == false) {
                        $shipmentDate = date('Y-m-d H:i:s');
                    }

                    if (!$shipKey)
                    {
                        $this->_logger->info('Shipment number in CSV column is invalid');
                    }
                    else
                    {
                        //import shipment
                        $this->_logger->info(sprintf("Begin process shipment: %s", $shipKey));
                        $importResult = $this->processShipment1502
                        (
                            $shipKey,
                            $shipmentDate,
                            ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                            $isRejected
                        );
                        if(count($importResult)>1)
                        {
                            $newShipment = $importResult[1];
                            if($newShipment instanceof \Magento\Sales\Model\Order\Shipment)
                            {
                                $order = $importResult[2];
                                $shipmentsArray = $importResult[3];
                                $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
                                //process order after import shipment
                                $currentStatus = $this->_orderStatusHelper->getCurrentOrderStatusBaseOnShipments($shipmentsArray);
                                $allowOrderStatus = [
                                    OrderStatusHelper::STEP_DELIVERY_COMPLETED
                                ];
                                if (in_array($currentStatus, $allowOrderStatus))
                                {
                                    $this->_updateOrderFinal($order, $paymentMethod, $shipmentDate, $newShipment);
                                }
                                //update shipment and order
                                try
                                {
                                    $newShipment->save();
                                    $this->_logger->info(sprintf('Shipment %s has been changed to status %s', $shipKey, $importResult[4]));
                                    $order->save();

                                }catch(\Exception $e)
                                {
                                    $this->_logger->critical($e);
                                }
                            }
                        }
                    }
                }//end foreach
                $this->_logger->info(sprintf("File %s has been imported.", $filename));
            }//end else
        }//end foreach
        return;
    }
    /**
     * @param $shipKey
     * @param $shipDate
     * @param $status
     * @param $isRejected
     * @return array
     */
    public function processShipment1502($shipKey,$shipDate, $status, $isRejected)
    {
        $shipment = $this->_getSingleShipment($shipKey);
        //status which available to be import
        $allowStatusShipment = [
            ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT,
            ShipmentStatus::SHIPMENT_STATUS_REJECTED
        ];
        $allowStatusOrder = [
            OrderStatus::STATUS_ORDER_SHIPPED_ALL,
            OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED,
            OrderStatus::STATUS_ORDER_IN_PROCESSING
        ];
        $originDate = $this->_timezone->formatDateTime($this->_dateTime->gmtDate(), \IntlDateFormatter::MEDIUM);
        $systemDate = $this->_dateTime->gmtDate('Y-m-d H:i:s', $originDate);

        if($shipment->getId())
        {
            $order = $shipment->getOrder();
            $orderStatus = $order->getStatus();
            $paymentMethod = $order->getPayment()->getMethod();
            $shipmentStatus = $shipment->getShipmentStatus();
            $orderId = $shipment->getOrderId();
            $shipsArray = $this->_orderStatusHelper->getShipmentsArray($orderId);
            //import
            if(in_array($shipmentStatus, $allowStatusShipment))
            {
                // Reject shipment
                if($isRejected)
                {
                    $rejectStatus = ShipmentStatus::SHIPMENT_STATUS_REJECTED;
                    //change shipment status in array
                    if(array_key_exists($shipKey,$shipsArray))
                    {
                        $shipsArray[$shipKey] = $rejectStatus;
                    }
                    $shipment->setData('shipment_status', $rejectStatus)
                        ->setData('shipment_date', $shipDate);

                    /*change shipment payment status to not applicable for cod shipment*/
                    if ($paymentMethod == \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
                        $shipment->setData(
                            'payment_status',
                            \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_NOT_APPLICABLE
                        );
                    }

                    //add history to order
                    $order->addStatusToHistory
                    (
                        $order->getStatus(),
                        __('Rejected shipment number by 1502: ' . $shipment->getIncrementId()),
                        false
                    );
                    //add History
                    $historyData = [
                        'shipment_status' => $rejectStatus,
                        'shipment_id' => $shipment->getId(),
                        'shipment_date' =>$systemDate
                    ];
                    $this->shipmentHistory->addShipmentHistory($historyData);
                    $this->_logger->info(sprintf('Shipment %s has been rejected.', $shipKey));
                    return [true,$shipment,$order, $shipsArray, $rejectStatus];
                }
                else // normally
                {
                    //check if shipment can be a valid importing
                    if(in_array($shipmentStatus,$allowStatusShipment))
                    {
                        //change shipment status in array
                        if(array_key_exists($shipKey,$shipsArray))
                        {
                            $shipsArray[$shipKey] = $status;
                        }

                        /*update shipment status -> delivery_complete*/
                        $shipment->setShipmentStatus($status);
                        /*System date when we receive the delivery-complete message*/
                        //$shipment->setShipmentDate($systemDate);
                        /*The actual delivery completion date mentioned in the Delivery completion message*/
                        $shipment->setDeliveryCompleteDate($shipDate);
                        //update order history
                        $order->addStatusToHistory
                        (
                            $orderStatus,
                            __('Imported from 3PL - Completion delivery, shipment number:') .$shipment->getIncrementId(),
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
                    }//end if
                    else
                    {
                        $this->_logger->info
                        (
                            sprintf('Shipment Status (%s) is not available to be import 1502 for shipment number: %s',$shipmentStatus,$shipKey)
                        );
                        $this->_logger->info
                        (
                            sprintf('Current status of order: (%s) ',$orderStatus)
                        );
                        return [false,$shipment,$order, $shipsArray,$status];
                    }
                }
            }
            else
            {
                $this->_logger->info
                (
                    sprintf('Shipment Status (%s) is not available to be import 1502 for shipment number: %s',$shipmentStatus,$shipKey)
                );
                $this->_logger->info
                (
                    sprintf('Current status of order: (%s) ',$orderStatus)
                );
                return [false,$shipment,$order, $shipsArray,$status];
            }
        }
        else
        {
            $this->_logger->info(sprintf('The Shipment number %s does not exist', $shipKey));
        }
        return [false];
    }
    /*
     * Send the log result
     */
    public function sendMailResult()
    {
        $taskName = '1502';
        $filesystem = $this->_fileSystemReader;
        $reader = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $path = '/var/log/shipment1502.log';
        if ($reader->isExist($path)) {
            $contentLog = $reader->openFile($path, 'r')->readAll();
            $emailVariable = ['logContent' => $contentLog,'taskname'=>$taskName];
            $this->_dataHelper->sendMailResult($emailVariable);
        }

    }

    /**
     * @return array|int
     */
    public function getSftpFiles($needDateFile)
    {
        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
        $host = $this->_dataHelper->getSftpHost();
        $port = $this->_dataHelper->getSftpPort();
        $username = $this->_dataHelper->getSftpUser();
        $password = $this->_dataHelper->getSftpPass();
        $patternRoot = $this->_dataHelper->getPattern1502();
        $pattern = "/^$patternRoot/";
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $remoteFolder = "remote";
        $doneFolder = "done";

        try {
            $this->_sftp->open(
                array(
                    'host' => $host . ':' . $port,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 300
                )
            );
        } catch (\Exception $e) {
            $this->_sftp->close();
            return false;
        }
        $sfptRoot = $this->_sftp->pwd();
        if ($sfptRoot != DIRECTORY_SEPARATOR) {
            $location = $sfptRoot . $this->_dataHelper->getLocation1502();
        } else {
            $location = $this->_dataHelper->getLocation1502();
        }
        $this->_sftp->cd($location);

        $files = $this->_sftp->rawls();
        $filesall = [];
        $localPath = $baseDir . '/import';
        $localPathShort = 'import';

        $fileObject = new File();
        if (!$fileObject->isExists($localPath)) {
            $fileObject->createDirectory($localPath, 0777);
        }
        foreach ($files as $key => $file) {
            $pre = preg_match($pattern, $key);
            if ($pre) {
                $extension = substr($key, strrpos($key, '.')); // Gets the File Extension
                if ($extension == ".csv") {
                    $filesall[] = $key; // Store in Array
                    $this->_sftp->read($location . DS . $key, $localPath . DS . $key);
                } // Extensions Allowed
            }
        }
        $data = [];
        if (sizeof($filesall) > 0) {
            foreach ($filesall as $filename) {
                if ($this->_fileSystem->isExists($localPath . DS . $filename)) {
                    $newfilename = str_replace('.csv', '_' . $needDateFile . '.csv', $filename);
                    $done = str_replace($remoteFolder, $doneFolder, $location . DS . $newfilename);
                    //remove if already exist in done folder
                    $this->_sftp->rm($done);
                    $this->_sftp->mv($location . DS . $filename, $done);
                    $contentFile = $this->_dataHelper->getCsvData($baseDir . DS . $localPathShort . DS . $filename);
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
                        $this->_logger->critical($e);
                    }
                }
            }
        }
        $this->_sftp->close();
        if (sizeof($data) > 0) {
            return $data;
        } else {
            return 2;
            //import file does not exists
        }
    }

    /**
     * @param $order
     * @param $paymentMethod
     * @param $shipmentDate
     * @param $shipment
     */
    private function _updateOrderFinal( &$order, $paymentMethod, $shipmentDate, $shipment)
    {
        $orderId = $order->getId();
        $createInvoice = true;
        /* REM-266 */
//        if ($paymentMethod == \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
//                $createInvoice = $this->canCreateInvoiceForCodOrder($orderId, $shipment);
//        }
        if ($createInvoice) {
            $this->_dataHelper->createInvoiceOrder($order, $shipmentDate);
        }
        else
        {
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
    private function _getSingleShipment($incrementId)
    {
        try {
            $criteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId)
                ->addFilter('ship_zsim', 1,'neq')
                ->create();
            $shipmentCollection = $this->shipmentRepository->getList($criteria);
            if ($shipmentCollection->getSize()) {
                return $shipmentCollection->getFirstItem();
            } else {
                return $this->shipmentFactory->create();
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
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
        /*can not create invoice for case which current shipment payment status is not allowed*/
        if (!in_array($shipment->getPaymentStatus(), $allowedPaymentStatus)) {
            if($shipment->getData('grand_total') && $shipment->getData('grand_total') != $shipment->getData('base_shopping_point_amount'))
            {
                return false;
            }
        }

        $criteria = $this->searchCriteriaBuilder->addFilter(
            'order_id', $orderId
        )->addFilter(
            'ship_zsim', 1,'neq'
        )->addFilter(
            'entity_id', $shipment->getId(), 'neq'
        )->create();

        /** @var \Magento\Sales\Api\Data\ShipmentSearchResultInterface $shipmentCollection */
        $shipmentCollection = $this->shipmentRepository->getList($criteria);

        $rs = false;

        foreach ($shipmentCollection->getItems() as $item) {

            if ($item->getShipmentStatus()==ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
                && $item->getPaymentStatus() == PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
            ) {
                $rs = true;
            } else if (
                $item->getShipmentStatus() == ShipmentStatus::SHIPMENT_STATUS_REJECTED
            ) {
                $rs = true;
            } else if($item->getShipmentStatus()==ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED
                && (!$item->getData('grand_total') || $item->getData('grand_total')== $item->getData('base_shopping_point_amount'))
            ){
                $rs = true;
            } else {
                /*cannot create invoice if exist any shipment which did not rejected or collected*/
                return false;
            }
        }

        return $rs;
    }

}//end class