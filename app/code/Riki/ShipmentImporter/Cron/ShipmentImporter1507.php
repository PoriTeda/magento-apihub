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
use Riki\CvsPayment\Model\CvsPayment;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Riki\AutomaticallyShipment\Model\CreateShipment;
use Riki\Sales\Helper\OrderStatus as OrderStatusHelper;
use Magento\Framework\App\AreaList;
use Magento\Framework\Escaper;
/**
 * Class ShipmentImporter1507
 *
 * @category  RIKI
 * @package   Riki\ShipmentImporter\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ShipmentImporter1507
{
    /**
     * @var
     */
    protected $_dateTime;
    /**
     * @var
     */
    protected $_dataHelper;
    /**
     * UpdateOrder constructor.
     */
    protected $_objectManager;
    /**
     * @var
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
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $_shipmentRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var CreateShipment
     */
    protected $shipmentCreator;
    /**
     * @var OrderStatusHelper
     */
    protected $orderStatusHelper;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystemReader;
    /**
     * @var AreaList
     */
    protected $_areaList;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;
    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     * @var \Riki\ShipmentImporter\Helper\Email
     */
    protected $_emailHelper;

    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $_preorderHelper;

    /**
     * List order import success
     * @var array
     */
    protected $_listOrderSuccess = [];
    /**
     * @var Escaper
     */
    protected $_escapter;

    /**
     * ShipmentImporter1507 constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param DirectoryList $directoryList
     * @param Sftp $sftp
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param File $fileSystem
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Riki\ShipmentImporter\Helper\Data $dataHelper
     * @param \Riki\ShipmentImporter\Logger\Logger1507 $logger
     * @param CreateShipment $createShipment
     * @param OrderStatusHelper $orderStatusHelper
     * @param \Magento\Framework\Filesystem $filesystemReader
     * @param \Riki\ShipmentImporter\Helper\Email $emailHelper
     * @param AreaList $areaList
     * @param \Riki\Preorder\Helper\Data $preorderHelper
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
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Riki\ShipmentImporter\Helper\Data $dataHelper,
        \Riki\ShipmentImporter\Logger\Logger1507 $logger,
        CreateShipment $createShipment,
        OrderStatusHelper $orderStatusHelper,
        \Magento\Framework\Filesystem $filesystemReader,
        \Riki\ShipmentImporter\Helper\Email $emailHelper,
        AreaList $areaList,
        \Riki\Preorder\Helper\Data $preorderHelper,
        Escaper $escaper
    ){
        $this->_dataHelper = $dataHelper;
        $this->_dateTime = $dateTime;
        $this->_directoryList = $directoryList;
        $this->_sftp = $sftp;
        $this->_logger = $logger;
        $this->_timezone = $timezone;
        $this->_logger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_fileSystem = $fileSystem;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentCreator = $createShipment;
        $this->orderStatusHelper = $orderStatusHelper;
        $this->_fileSystemReader = $filesystemReader;
        $this->_areaList = $areaList;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_emailHelper = $emailHelper;
        $this->_preorderHelper = $preorderHelper;
        $this->_escapter = $escaper;
    }
    /**
     *
     */
    public function execute()
    {
        //load Translation for cron
        $areaObject = $this->_areaList->getArea(\Magento\Framework\App\Area::AREA_FRONTEND);
        $areaObject->load(\Magento\Framework\App\Area::PART_TRANSLATE);
        //1507
        $taskName = 'Import CVS payment collection file from WMS';
        $originDate =  $this->_timezone->formatDateTime($this->_dateTime->gmtDate(),2);
        $needDate = $this->_dateTime->gmtDate('Y-m-d H:i:s',$originDate);
        $needDateFile = $this->_dateTime->gmtDate('Ymd',$originDate);
        $needDateFileDone = $this->_dateTime->gmtDate('YmdHis', $originDate);
        $this->_dataHelper->backupLog($needDateFile,'shipment1507.log','1507');
        $this->_logger->info($taskName. ' run at :'.$needDate);
        if(!$this->_dataHelper->isEnable())
        {
            $this->_logger->info('Import CVS payment collection file from WMS 1507 has been disabled.');
            $this->sendMailResult();
            return ;
        }
        //check sftp
        if(!$this->_dataHelper->checkSftpConnection($this->_sftp))
        {
            $this->_logger->info(__('Could not connect to sFTP.'));
            $this->sendMailResult();
            return ;
        }
        //check permission of folder
        $location = $this->_dataHelper->getLocation1507();
        if(!$this->_dataHelper->checkSftpLocation($this->_sftp,$location,true))
        {
            $this->_logger->info(sprintf('Location :%s in sFTP does not exist', $location));
            $this->sendMailResult();
            return ;

        }
        //begin read file
        $data = $this->getSftpFiles($needDateFileDone);
        if($data == 2){
            $this->_logger->info('CSV files not found.');
            $this->sendMailResult();
            return ;
        }
        $path = $this->_dataHelper->getLocation1507();
        foreach($data as $filename=>$content)
        {
            $error = 0;
            $fileErrors = 0;
            if ($content == 1) {
                $error++;
                $fileErrors++;
                $this->_logger->info(sprintf('Content of file %s is null',$filename));
            } elseif ($content == 2) {
                $error++;
                $fileErrors++;
                $this->_logger->info(sprintf('The file %s does not exist', $path . $filename));

            } else {
                $this->importProcess($content);
            }//end else
            if(!$error)
            {
                $this->_logger->info(sprintf('Import file %s successful.',$filename));
            }
            //file errors
            if($fileErrors)
            {
                //move to error file
                $this->_logger->info(sprintf('File: %s has an error.',$filename));
                $this->moveToError($needDateFileDone,$filename);
            }
        }//end foreach
        $this->sendMailResult();
        return;
    }

    /**
     * @param $content
     * @throws \Exception
     */
    public function importProcess( $content )
    {
        $index = 3;
        $incrementNumber = 1;
        $notificationMessage = array();
        $year = $this->_timezone->date()->format('Y');
        $month = $this->_timezone->date()->format('m');
        $day = $this->_timezone->date()->format('d');
        $hour = $this->_timezone->date()->format('H');
        $canceledCvs = array();
        foreach ($content as $key => $item) {
            $orderNumber = false;
            if(isset($item[$index])){
                $orderNumber = utf8_encode(substr(substr($item[$index],1),0,-1));
            }
            if ($orderNumber) {
                $order = $this->_getOrderByOrderNumber($orderNumber);
                if(!$order){
                    $notificationMessage[] = sprintf(__('%s has been update failed at line %s'), $orderNumber, $incrementNumber);
                    $this->_logger->info(sprintf('%s  has been update failed at line %s', $orderNumber, $incrementNumber));
                } else {

                    $paymentMethod = $order->getPayment() ? $order->getPayment()->getMethod() : '';

                    if($order->getStatus()=="hold_cvs_nopayment")
                    {
                        $notificationMessage[] = sprintf(__('Order has been cancelled : %s'),  $orderNumber);
                        $this->_logger->info(sprintf('Order has been cancelled : %s',  $orderNumber));
                        if ($paymentMethod == CvsPayment::PAYMENT_METHOD_CVS_CODE)
                        {
                            /*push order which is cancelled to array*/
                            $canceledCvs[] =  $orderNumber;

                            /*create invoice for this order*/
                            $this->createInvoiceOrder($order);
                        }
                    }
                    else
                    {
                        $this->_logger->info(sprintf('Processing order %s ', $orderNumber));
                        if ($paymentMethod != CvsPayment::PAYMENT_METHOD_CVS_CODE) {
                            $this->_logger->info(sprintf('%s:  has payment method invalid: %s', $orderNumber, $paymentMethod));
                            $this->_logger->info(sprintf('Order has been update failed at line %1',  $incrementNumber));
                            $notificationMessage[] = sprintf(__('Order has been update failed at line %2'),  $incrementNumber);
                        }
                        else
                        {
                            $resultMessage = $this->_processOrder($order, $incrementNumber, $item);
                            $notificationMessage[] = $resultMessage;
                        }

                    }
                }
            }
            $incrementNumber++;
        }

        /*change payment status -> payment collected for list order success*/
        if (!empty($this->_listOrderSuccess)) {
            $this->afterImportSuccess();
        }

        /* controlled by Email Marketing */
        /* Email: Payment collection from Wellnet */
        $emailVariables =
            [
                'reason' => implode("\r\n", $notificationMessage),
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'hour' => $hour
            ];
        $this->_dataHelper->sendErrorImportingEmail($emailVariables, 'CVS');
        if($canceledCvs)
        {
            $emailVariables2[''] = implode("\r\n", $canceledCvs);
            $this->_emailHelper->sendCancelationEmailCvs($emailVariables2);
        }
    }

    public function sendMailResult()
    {
        $taskName = '1507';
        $filesystem = $this->_fileSystemReader;
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $reader = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        if($this->_fileSystem->isExists($baseDir.'/var/log/shipment1507.log')) {
            $contentLog = $reader->openFile('/var/log/shipment1507.log', 'r')->readAll();
            $emailVariable = ['logContent' => $contentLog,'taskname'=>$taskName];

        }
        else
        {
            $emailVariable = ['logContent' => __("Log is not found!"),'taskname'=>$taskName];
        }

        $this->_dataHelper->sendMailResult($emailVariable);
    }
    /**php
     * @return array|int
     */
    public function getSftpFiles($needDateFile)
    {
        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
        $host = $this->_dataHelper->getSftpHost();
        $port = $this->_dataHelper->getSftpPort();
        $username = $this->_dataHelper->getSftpUser();
        $password = $this->_dataHelper->getSftpPass();
        $patternRoot = $this->_dataHelper->getPattern1507();
        $pattern  = "/^$patternRoot/";
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $remoteFolder = "remote";
        $doneFolder = "done";
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
            $this->_sftp->close();
            return false;
        }
        $sfptRoot = $this->_sftp->pwd();
        if($sfptRoot!=DIRECTORY_SEPARATOR)
        {
            $location = $sfptRoot.$this->_dataHelper->getLocation1507();
        }
        else
        {
            $location = $this->_dataHelper->getLocation1507();
        }
        $this->_sftp->cd($location);
        $files = $this->_sftp->rawls();
        $filesall =[];
        $localPath = $baseDir.'/import';
        $localPathShort = 'import';
        $fileObject = new File();
        if(!$fileObject->isExists($localPath)) {
            $fileObject->createDirectory($localPath, 0777);
        }
        foreach($files as $key=>$file){
            if(!in_array($key,array('.','..'))) {
                $pre = preg_match($pattern, $key);
                if ($pre) {
                    $extension = substr($key, strrpos($key, '.')); // Gets the File Extension
                    if (strtolower($extension) == ".csv") {
                        $filesall[] = $key; // Store in Array
                        $this->_sftp->read($location . DS . $key, $localPath . DS . $key);
                    } // Extensions Allowed
                    else {
                        //move to error
                        $this->_logger->info(sprintf(__("File: %s is not CSV extension."), $key));
                        $this->moveToError($needDateFile, $key, true);
                    }
                } else {
                    //move to error
                    $this->_logger->info(sprintf(__("File: %s does not match the configed pattern name (%s)."), $key, $pattern));
                    $this->moveToError($needDateFile, $key, true);
                }
            }
        }
        $data =[];
        if(sizeof($filesall) > 0) {
            foreach ($filesall as $filename) {
                if ($this->_fileSystem->isExists($localPath .DS. $filename)) {
                    $newfilename = str_replace('.csv','_'.$needDateFile.'.csv',$filename);
                    $done = str_replace($remoteFolder, $doneFolder, $location.DS.$newfilename);
                    //remove if already exist in done folder
                    $this->_sftp->rm($done);
                    $this->_sftp->mv($location . DS . $filename, $done);
                    $contentFile =$this->_dataHelper->getCsvData($baseDir.DS.$localPathShort.DS. $filename);
                    if($contentFile == null || $contentFile ==''){
                        $data[$filename] = 1;
                    }
                    else{
                        $data[$filename] = $contentFile;
                    }
                    try {
                        if ($fileObject->isExists($localPath . DS . $filename)) {
                            $fileObject->deleteFile($localPath . DS . $filename);
                        }
                    } catch (\Exception $e) {
                        $this->_logger->info($e->getMessage());
                    }
                }
            }
        }
        $this->_sftp->close();
        if(sizeof($data) > 0){
            return $data;
        }else{
            return 2;
            //import file does not exists
        }
    }
    /**
     * @param $needDateFile
     * @param $filename
     * @return bool
     */
    public function moveToError($needDateFile,$filename, $ext = false)
    {
        if($filename!='.' && $filename!='..') {
            $host = $this->_dataHelper->getSftpHost();
            $port = $this->_dataHelper->getSftpPort();
            $username = $this->_dataHelper->getSftpUser();
            $password = $this->_dataHelper->getSftpPass();
            $remoteFolder = 'remote';
            $doneFolder = "complete";
            $errorFolder = "error";
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
            $location = $sfptRoot.$this->_dataHelper->getLocation1507();

            try {
                if ($ext) {
                    $newfilename = $filename;
                    $completeFile = $location . DS . $newfilename;
                    $errorFile = str_replace($remoteFolder, $errorFolder, $location . DS . $newfilename);
                    $this->_sftp->rm($errorFile);
                } else {
                    $newfilename = str_replace('.csv', '_' . $needDateFile . '.csv', $filename);
                    $completeFile = str_replace($remoteFolder, $doneFolder, $location . DS . $newfilename);
                    $errorFile = str_replace($remoteFolder, $errorFolder, $location . DS . $newfilename);

                }
                //remove if already exist in done folder
                $this->_sftp->mv($completeFile, $errorFile);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * @param $orderNumber
     * @return bool
     */
    private function _getOrderByOrderNumber($orderNumber)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter('increment_id', $orderNumber )
            ->create();

        $orderCollection = $this->_orderRepository->getList($criteria);

        if($orderCollection->getTotalCount())
        {
            return $orderCollection->getFirstItem();
        }
        else
        {
            return false;
        }
    }

    /**
     * @param $orderId
     * @return bool|\Magento\Sales\Api\Data\ShipmentInterface[]
     */
    private function _getShipmentByOrderId($orderId)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter('order_id', $orderId )
            ->create();
        $shipmentCollection = $this->_shipmentRepository->getList($criteria);

        if($shipmentCollection->getTotalCount())
        {
            return $shipmentCollection->getItems();
        }
        else
        {
            return false;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param int $line
     * @param $item
     * @return string
     */
    private function _processOrder(\Magento\Sales\Model\Order $order, $line = 0, $item)
    {
        $notificationMessage = '';
        $orderNumber = $order->getIncrementId();

        /*check order status before process*/
        if ($order->getStatus() == OrderStatus::STATUS_ORDER_PENDING_CVS) {

            /*order can ship*/
            if ($order->canShip()) {

                try {

                    $orderStatus = OrderStatus::STATUS_ORDER_NOT_SHIPPED;

                    /* change order status to not shipped */
                    $order->setStatus($orderStatus);

                    /* change order state to processing */
                    $order->setState(
                        \Magento\Sales\Model\Order::STATE_PROCESSING
                    );

                    /* change payment status to payment collected */
                    $order->setPaymentStatus(
                        \Riki\Shipment\Model\ResourceModel\Status\Options\Payment::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
                    );

                    /* add order status history */
                    $order->addStatusHistoryComment(
                        __('Update order success , Import from Welnet 1507'), $orderStatus
                    );

                    $order->setIsNotified(false);

                    /* add order status to history */
                    $this->addOrderStatusToHistory($order);

                    /* create shipment for not pre-order */
                    if (!$this->_preorderHelper->getOrderIsPreorderFlag($order)) {

                        /*actual collection date for order shipment*/
                        if (!empty($item[7])) {
                            $collectionDate = $this->_dateTime->date('Y-m-d', strtotime($item[7]));
                        } else {
                            $collectionDate = $this->_timezone->date()->format('Y-m-d');
                        }

                        /**
                         * flag data - do not map with any column from sale order table
                         *  will be set for shipment collection_date column while create shipment
                         */
                        $order->setData('cvs_collection_date', $collectionDate);

                        /**
                         * do not change order status after create shipment success
                         */
                        $order->setData('do_not_change_order_status', 1);

                        /* create shipment for not pre order */
                        $createShipment = $this->shipmentCreator->createShipment($order, __('Shipment Importer Cron'));

                        if ($createShipment) {
                            $orderStatus = OrderStatus::STATUS_ORDER_IN_PROCESSING;

                            /* change order shipment status to created */
                            $order->setShipmentStatus(
                                \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_CREATED
                            );

                            /*change order shipment created to 1 , flag to check this order is created shipment*/
                            $order->setShipmentCreated(1);

                            /*change order status to in processing*/
                            $order->setStatus(
                                OrderStatus::STATUS_ORDER_IN_PROCESSING
                            );

                            /*add status history and do not push notification*/
                            $order->addStatusHistoryComment(
                                __('Shipments created by Shipment Importer 1507'), $orderStatus
                            );
                        } else {
                            $this->_logger->info(__('Can not create shipment for order %1', $orderNumber));
                        }
                    }

                    $order->save();

                    /* flag success import for this order */
                    $this->_listOrderSuccess[$order->getId()] = [
                        'status' => $orderStatus
                    ];

                    $notificationMessage = sprintf(__('Order has been paid : %s'),  $order->getIncrementId());

                } catch (\Exception $e) {
                    $this->_logger->info($e->getMessage());
                }
                //update CVS order
            } else {
                $notificationMessage = sprintf(__('Order has been update failed at line %s'),  $line);
                $this->_logger->info(sprintf('The order : %s could not create any shipments',$orderNumber));
            }
        } else {

            $notificationMessage = sprintf(__('Order has been update failed at line %s'),  $line);
            $this->_logger->info(sprintf('The status of order : %s is not valid %s',$orderNumber, $order->getStatus()));
        }

        return $notificationMessage;
    }

    /**
     * Create invoice for order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param null $date
     */
    public function createInvoiceOrder(\Magento\Sales\Model\Order $order, $date = null)
    {
        /*set order status*/
        $newStatus = \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CVS_CANCELLATION_WITH_PAYMENT;

        $order->setStatus($newStatus);

        $order->addStatusToHistory(
            $newStatus,
            __('Update canceled CVS order, Import from Welnet')
        );

        try {
            /*create invoice for this order*/
            if ($order->canInvoice()) {
                $invoice = $this->_invoiceService->prepareInvoice($order);
                $captureType= \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE;
                $invoice->setRequestedCaptureCase($captureType);
                $invoice->register();
                $this->_transaction->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();
            } else {
                $order->save();
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * after import success
     *      Change payment status to payment collected for success list
     */
    public function afterImportSuccess()
    {
        if (!empty($this->_listOrderSuccess)) {

            /*list order id which imported success*/
            $listOrderSuccess = array_keys($this->_listOrderSuccess);

            $criteria = $this->_searchCriteriaBuilder->addFilter(
                'entity_id', $listOrderSuccess, 'in'
            )->create();

            $orderCollection = $this->_orderRepository->getList($criteria);

            if ($orderCollection->getTotalCount()) {
                foreach ($orderCollection->getItems() as $order) {

                    /*flag to check order data is valid*/
                    $valid = true;

                    if (!empty($this->_listOrderSuccess[$order->getId()])) {

                        /* make sure order status is correct after import success */
                        if ($order->getStatus() != $this->_listOrderSuccess[$order->getId()]['status']) {

                            $valid = false;

                            /*set order status again*/
                            $order->setStatus(
                                $this->_listOrderSuccess[$order->getId()]['status']
                            );
                        }

                        /* make sure order payment status is correct after import success */
                        if ($order->getPaymentStatus() != \Riki\Shipment\Model\ResourceModel\Status\Options\Payment::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED) {

                            $valid = false;

                            /*set order payment status again*/
                            $order->setPaymentStatus(
                                \Riki\Shipment\Model\ResourceModel\Status\Options\Payment::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
                            );
                        }

                    }

                    if (!$valid) {
                        try {
                            $this->_orderRepository->save($order);
                        } catch (\Exception $e) {
                            $this->_logger->info('Can not change payment status for order number %1', $order->getIncrementId());
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Add order status to history
     *
     * @param $order
     */
    public function addOrderStatusToHistory($order)
    {
        $statusData = [
            'order_id' => $order->getId(),
            'order_increment_id' => $order->getIncrementId(),
            'status_payment' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED
        ];

        $this->orderStatusHelper->addOrderPayShipStatus($statusData);
    }

}//end class