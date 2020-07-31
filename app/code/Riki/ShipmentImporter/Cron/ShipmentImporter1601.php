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
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\Order\ShipmentFactory;
use Riki\Shipment\Helper\ShipmentHistory;
use Riki\ShipmentImporter\Helper\Order as OrderStatusHelper;

/**
 * Class ShipmentImporter1601
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ShipmentImporter1601
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
    protected $_shipment;
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

    protected $_orderHistory;
    /**
     * @var
     */
    protected $_fileSystem;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Riki\ShipmentImporter\Helper\Email
     */
    protected $_emailHelper;
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
     * @var \Magento\Sales\Api\Data\ShipmentTrackInterface
     */
    protected $_shipmentTrackInterface;
    /**
     * @var \Magento\Sales\Api\ShipmentTrackRepositoryInterface
     */
    protected $_shipmentTrackRepository;
    /**
     * @var
     */
    protected $_shipmentTractCollectionFactory;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $_state;
    /**
     * @var \Magento\Framework\App\AreaList
     */
    protected $_areaList;
    /**
     * @var \Riki\ShipmentImporter\Helper\Order
     */
    protected $_orderHelper;

    CONST SAP_FLAG_INDEX = 10;
    /**
     * ShipmentImporter1601 constructor.
     * @param \Riki\ShipmentImporter\Helper\Data $dataHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param DirectoryList $directoryList
     * @param Sftp $sftp
     * @param \Riki\ShipmentImporter\Logger\Logger1601 $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $orderHistory
     * @param File $fileSystem
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\ShipmentImporter\Helper\Email $emailHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShipmentRepository $shipmentRepository
     * @param ShipmentFactory $shipmentFactory
     * @param ShipmentHistory $shipmentHistory
     * @param \Magento\Framework\Filesystem $filesystemReader
     * @param \Magento\Sales\Api\Data\ShipmentTrackInterface $shipmentTrack
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackRepository
     * @param \Magento\Sales\Api\ShipmentTrackRepositoryInterface $shipmentTrackRepositoryFactory
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Framework\App\AreaList $areaList
     * @param OrderStatusHelper $orderHelper
     */
    public function __construct(
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Riki\ShipmentImporter\Logger\Logger1601 $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $orderHistory,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\ShipmentImporter\Helper\Email $emailHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShipmentRepository $shipmentRepository,
        ShipmentFactory $shipmentFactory,
        ShipmentHistory $shipmentHistory,
        \Magento\Framework\Filesystem $filesystemReader,
        \Magento\Sales\Api\Data\ShipmentTrackInterface $shipmentTrack,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackRepository,
        \Magento\Sales\Api\ShipmentTrackRepositoryInterface $shipmentTrackRepositoryFactory,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\AreaList $areaList,
        \Riki\ShipmentImporter\Helper\Order $orderHelper
    )

    {
        $this->_dataHelper = $dataHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_dateTime = $dateTime;
        $this->_directoryList = $directoryList;
        $this->_sftp = $sftp;
        $this->_logger = $logger;
        $this->_timezone = $timezone;
        $this->_logger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_orderHistory = $orderHistory;
        $this->_fileSystem = $fileSystem;
        $this->_customerFactory = $customerFactory;
        $this->_emailHelper = $emailHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentFactory = $shipmentRepository;
        $this->shipmentHistory = $shipmentHistory;
        $this->_fileSystemReader = $filesystemReader;
        $this->_shipmentTrackInterface = $shipmentTrack;
        $this->_shipmentTrackRepository = $shipmentTrackRepository;
        $this->_state = $state;
        $this->_areaList = $areaList;
        $this->_shipmentTractCollectionFactory = $shipmentTrackRepositoryFactory;
        $this->_orderHelper = $orderHelper;

    }

    /**
     *
     */
    public function execute()
    {
        //load translation
        $area = $this->_areaList->getArea($this->_state->getAreaCode());
        $area->load(\Magento\Framework\App\Area::PART_TRANSLATE);
        $taskName = __('Completion of Shipped Out (Bizex) 1601');
        $originDate = $this->_timezone->formatDateTime($this->_dateTime->gmtDate(),  \IntlDateFormatter::MEDIUM);
        $needDate = $this->_dateTime->gmtDate('Y-m-d H:i:s', $originDate);
        $needDateFile = $this->_dateTime->gmtDate('Ymd', $originDate);
        $needDateFileDone = $this->_dateTime->gmtDate('YmdHis', $originDate);
        $this->_dataHelper->backupLog($needDateFile, 'shipment1601.log', '1601');
        $this->_logger->info($taskName . ' run at :' . $needDate);
        if (!$this->_dataHelper->isEnable()) {
            $this->_logger->info(sprintf('%s has been disabled.', $taskName));
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
        $location = $this->_dataHelper->getLocation1601();
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
        $payCollectedMethod = [
            \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE,
            \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE
        ];
        $path = $this->_dataHelper->getLocation1601();
        foreach ($data as $filename => $content) {
            $error = 0;
            if ($content == 1) {
                $error++;
                $this->_logger->info(sprintf('Content of file %s is null', $filename));

            } elseif ($content == 2) {
                $error++;
                $this->_logger->info(sprintf('The file %s does not exist', $path . $filename));

            } else {
                //process data
                foreach ($content as $key => $item) {
                    $error = 0;
                    $shipKey = $item[2];
                    $shipDate =  $this->_dateTime->date('Y-m-d',strtotime($item[6]));
                    $isDateValid = $item[29];
                    $sapFlag = $item[self::SAP_FLAG_INDEX];
                    $this->_logger->info(sprintf('Process shipment number : %s, order date: %s', $shipKey, $item[6]));
                    if(!$error && $isDateValid)
                    {
                        //import shipment
                        $importResult = $this->processShipment
                        (
                            $shipKey,
                            $shipDate,
                            ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT,
                            $sapFlag
                        );
                        if($importResult[0]) // import shipment success and change order status
                        {
                            $newShipment = $importResult[1];
                            $shipmentsArray = $importResult[2];
                            if($newShipment instanceof \Magento\Sales\Model\Order\Shipment)
                            {
                                $order = $newShipment->getOrder();
                                $orderNumber = $order->getIncrementId();
                                $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();

                                /*shipment payment status after shipped out - must be payment collected*/
                                $shipmentPaymentStatus = $this->_dataHelper->getShipmentPaymentStatusAfterShippedOut($paymentMethod, $order);

                                if ($shipmentPaymentStatus) {
                                    $newShipment->setData('payment_status', PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                                    $newShipment->setData('payment_date', $shipDate);
                                    $newShipment->setData('collection_date', $shipDate);
                                }

                                if ($paymentMethod == \Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE) {
                                    /*set flag_export_invoice_sales_shipment = 0 (waiting for export to bi - invoice shipment) */
                                    $newShipment->setData('flag_export_invoice_sales_shipment', 0);
                                }

                                //add Tracking Number here
                                $this->_processShipmentTracking($item, $newShipment);
                                //process order
                                $currentStatus = $this->_orderHelper->getCurrentOrderStatusBaseOnShipments($shipmentsArray);
                                if ($currentStatus == OrderStatusHelper::STEP_SHIPPED_ALL)
                                {
                                    //process order
                                    $this->_processOrder($order,$paymentMethod,$item);
                                }
                                elseif ($currentStatus == OrderStatusHelper::STEP_PARTIALL_SHIPPED)
                                {
                                    $shipmentStatus = ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT_PARTIAL;
                                    $orderStatus = OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED;
                                    $order->setShipmentStatus($shipmentStatus);
                                    $order->setStatus($orderStatus);
                                    //update order history
                                    $order->addStatusToHistory
                                    (
                                        $orderStatus, __('Imported from 3PL - Shipment Shipped out, shipment number: ') .
                                        $shipKey,
                                        false
                                    );
                                }
                                //update shipment and order
                                try
                                {
                                    $newShipment->save();
                                    $this->_logger->info(
                                        sprintf(
                                            'The shipment number %s has been changed to %s',
                                            $shipKey,
                                            ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT)
                                    );

                                    /*update order payment agent after change status*/
                                    if ($paymentMethod == \Bluecom\Paygent\Model\Paygent::CODE && empty($order->getData('payment_agent'))) {
                                        $paymentAgent = $this->_dataHelper->getPaymentAgentByOrderIncrementId($order->getIncrementId());
                                        if (!empty($paymentAgent)) {
                                            $order->setData('payment_agent', $paymentAgent);
                                        }
                                    }

                                    $order->save();
                                }catch(\Exception $e)
                                {
                                    $this->_logger->critical($e);
                                }
                            }
                        }
                    }//endif
                    else
                    {
                        $this->_logger->info(__('Error compatibility of imported data or imported date is invalid (%s)',$shipDate ));
                    }
                }//end foreach
                $this->_logger->info(sprintf("File %s has been imported.", $filename));
            }//end else
        }//end foreach
        return;
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
        $patternRoot = $this->_dataHelper->getPattern1601();
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
            $this->_logger->critical($e);
            return false;
        }
        $sfptRoot = $this->_sftp->pwd();
        if ($sfptRoot != DIRECTORY_SEPARATOR) {
            $location = $sfptRoot . $this->_dataHelper->getLocation1601();
        } else {
            $location = $this->_dataHelper->getLocation1601();
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
                    $this->_dataHelper->convertEncodeFile($filename);
                    $contentFile = $this->_dataHelper->getCsvData($baseDir . DS . $localPathShort . DS . $filename, true, true);
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
     *
     */
    public function sendMailResult()
    {
        $taskName = '1601';
        $filesystem = $this->_fileSystemReader;
        $reader = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $path = '/var/log/shipment1601.log';
        if ($reader->isExist($path))
        {
            $contentLog = $reader->openFile($path, 'r')->readAll();
            $emailVariable = ['logContent' => $contentLog,'taskname'=>$taskName];
            $this->_dataHelper->sendMailResult($emailVariable);

        }
    }
    /**
     * @param $shipKey
     * @param $shipDate
     * @param $importStatus
     * @return bool
     */
    public function processShipment($shipKey, $shipDate,$importStatus, $sapFlag = null)
    {
        $shipment = $this->_getSingleShipment($shipKey);
        //status which available to be import
        $allowStatusShipment = [
            ShipmentStatus::SHIPMENT_STATUS_EXPORTED,
            ShipmentStatus::SHIPMENT_STATUS_REJECTED,
            ShipmentStatus::SHIPMENT_STATUS_CREATED,
        ];
        $originDate = $this->_timezone->formatDateTime($this->_dateTime->gmtDate(),  \IntlDateFormatter::MEDIUM,  \IntlDateFormatter::MEDIUM);
        $systemDate = $this->_dateTime->date('Y-m-d H:i:s');
        if($shipment->getId())
        {
            $orderId = $shipment->getOrderId();
            $shipsArray = $this->_orderHelper->getShipmentsArray($orderId);
            //verify shipment again
            $shipmentStatus = $shipment->getShipmentStatus();
            if(in_array($shipmentStatus, $allowStatusShipment))
            {
                //change shipment status in array
                if(array_key_exists($shipKey,$shipsArray))
                {
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
                if(strlen($sapFlag))
                {
                    if(boolval($sapFlag))
                    {
                        $shipment->setIsExportedSap(\Riki\SapIntegration\Model\Api\Shipment::WAITING_FOR_EXPORT);
                    }
                    else
                    {
                        $shipment->setIsExportedSap(\Riki\SapIntegration\Model\Api\Shipment::NO_NEED_TO_EXPORT);
                    }
                }
                else
                {
                    $shipment->setIsExportedSap(\Riki\SapIntegration\Model\Api\Shipment::WAITING_FOR_EXPORT);
                }
                //add History
                $historyData =
                    [
                        'shipment_date' => $shipDate,
                        'shipment_status' => $importStatus,
                        'shipment_id' => $shipment->getId()
                    ];
                $this->shipmentHistory->addShipmentHistory($historyData);
                return [true,$shipment, $shipsArray];
            }
            else
            {
                $this->_logger->info
                (
                    sprintf('Shipment Status (%s) is not available to be import for shipment number: %s',$shipmentStatus,$shipKey)
                );
            }
        }
        else
        {
            $this->_logger->info(sprintf('The shipment number %s does not exist', $shipKey));
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
        if (in_array($paymentMethod, array("cvspayment", "paygent", "invoicedbasedpayment", "free"))) {

            if ($orderStatus == OrderStatus::STATUS_ORDER_CAPTURE_FAILED) {
                return false;
            }
            return true;
        }
        return false;
    }

    private function _processShipmentTracking
    (
        $item,
        & $shipment
    )
    {
        $trackingNumber = $item[5];
        if($trackingNumber){
            if(!$this->checkExistTracking($trackingNumber, $shipment->getEntityId()))
            {
                $trackings = explode(";", $item[5]);
                if ($item[9]) //carrier
                {
                    $carrierCode = $this->_dataHelper->getCarrierCode($item[9]);
                    $carrierTitle = $this->_dataHelper->getCarrierTitle($item[9]);
                    if ($carrierCode && $carrierTitle)
                    {
                        $trackingCodes = array();
                        $trackingUrl = array();
                        foreach ($trackings as $_track) {
                            $trackingCodes[] = $_track;
                            $trackingUrl[] = $this->_dataHelper->getCarrierUrl(
                                $carrierCode, $_track
                            );
                            //validate exist track number
                            if ($_track && $carrierCode) {
                                $shipmentTrack = $this->_shipmentTrackRepository->create()
                                    ->setTrackNumber($_track)
                                    ->setCarrierCode($carrierCode)
                                    ->setTitle($carrierTitle);
                                $shipment->addTrack($shipmentTrack);
                            }
                        }
                        //send email
                        if ($trackingCodes && $trackingUrl) {
                            $emailTemplateVariables = $this->_emailHelper->getEmailParameters($shipment);
                            $this->_dataHelper->sendTrackingCodeEmail(
                                $emailTemplateVariables
                            );
                        }
                    }
                }
            }
            else
            {
                $this->_logger->info(__('Tracking number %1 is already exists in shipment : %2', $trackingNumber, $shipment->getIncrementId()));
            }
        }
    } //end function

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $paymentMethod
     * @param $item
     */
    private function _processOrder
    (
        & $order,
        $paymentMethod,
        $item
    )
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

        $statusData = array(
            'order_id' => $order->getId(),
            'order_increment_id' => $order->getIncrementId(),
            'status_date' => $item[6],
            'status_shipment' => $status
        );
        //update Payment Status for InvoicedbasePayment, free only
        if (
            in_array($paymentMethod, [\Riki\PaymentBip\Model\InvoicedBasedPayment::PAYMENT_CODE, 'free']) ||
            $order->getFreeOfCharge()
        ) {
            $paymentStatus = PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED;
            $order->setPaymentStatus($paymentStatus);
            $this->_logger->info(sprintf('Change payment status of order %s to be payment collected', $orderNumber));
            $statusData['status_payment'] = $paymentStatus;
        }
        $this->_dataHelper->addOrderStatusHistory($statusData);
        $this->_logger->info(sprintf('Change status of order %s to be %s', $orderNumber, $status));
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
        $trackingCollection = $this->_shipmentTractCollectionFactory->getList($criteria);
        if($trackingCollection->getTotalCount())
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}//end class
