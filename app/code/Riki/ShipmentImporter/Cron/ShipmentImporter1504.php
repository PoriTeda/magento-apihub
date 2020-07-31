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
class ShipmentImporter1504
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
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystemReader;
    /**
     * @var AreaList
     */
    protected $_areaList;

    /**
     * ShipmentImporter1504 constructor.
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param DirectoryList $directoryList
     * @param Sftp $sftp
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param File $fileSystem
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\ShipmentImporter\Helper\Data $dataHelper
     * @param \Riki\ShipmentImporter\Logger\Logger1504 $logger
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
        \Riki\ShipmentImporter\Logger\Logger1504 $logger,
        \Magento\Framework\Filesystem $filesystemReader,
        AreaList $areaList
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
        $this->_shipmentRepository = $shipmentRepository;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_fileSystemReader = $filesystemReader;
        $this->_areaList = $areaList;
    }

    /**
     *
     */
    public function execute()
    {
        //load Translation for cron
        $areaObject = $this->_areaList->getArea(\Magento\Framework\App\Area::AREA_FRONTEND);
        $areaObject->load(\Magento\Framework\App\Area::PART_TRANSLATE);
        //1504
        $taskName = 'Import Cash-on-delivery money collected file from WMS';
        $originDate = $this->_timezone->formatDateTime($this->_dateTime->gmtDate(), 2);
        $needDate = $this->_dateTime->gmtDate('Y-m-d H:i:s', $originDate);
        $needDateFile = $this->_dateTime->gmtDate('Ymd', $originDate);
        $needDateFileDone = $this->_dateTime->gmtDate('YmdHis', $originDate);
        $this->_dataHelper->backupLog($needDateFile, 'shipment1504.log', '1504');
        $this->_logger->info($taskName . ' run at :' . $needDate);
        if (!$this->_dataHelper->isEnable()) {
            $this->_logger->info('Import Cash-on-delivery money collected file from WMS 1504 has been disabled.');
            $this->sendMailResult();
            return;
        }
        //check sftp
        if (!$this->_dataHelper->checkSftpConnection($this->_sftp)) {
            $this->_logger->info('Could not connect to sFTP.');
            $this->sendMailResult();
            return;
        }
        $b = "Bizex";
        //begin read file
        $data = $this->getAllCsvFiles($needDateFileDone);
        if (!$data) {
            $this->sendMailResult();
            return;
        }
        $path = $this->_dataHelper->getLocation1504();
        $year = $this->_timezone->date()->format('Y');
        $month = $this->_timezone->date()->format('m');
        $day = $this->_timezone->date()->format('d');
        $hour = $this->_timezone->date()->format('H');
        foreach ($data as $filename => $content) {
            $reasonFail = '';
            $error = 0;
            $fileErrors = 0;
            if ($content == 1) {
                $error++;
                $fileErrors++;
                $reasonFail = sprintf('Content of file %s is null', $filename);
                $this->_logger->info(sprintf("Content of file %s is null", $filename));
            } elseif ($content == 2) {
                $error++;
                $fileErrors++;
                $reasonFail = sprintf('The file %s does not exist', $path . $filename);
                $this->_logger->info(sprintf("The file %s does not exist", $path . $filename));

            } else {
                $this->importProcess($content);
            }//end else
            if (!$error) {
                $this->_logger->info(sprintf('Import file %s successful', $filename));
            }
            //file errors
            if ($fileErrors) {
                //move to error file
                $this->_logger->info(sprintf('File: %s has an error.', $filename));
                $this->moveToError($needDateFileDone, $filename, true, $b);
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
                $this->_dataHelper->sendErrorImportingEmail($emailVariables, 'COD');
            }
        }//end foreach
        $this->sendMailResult();
        return;
    }

    /**
     * @param $needDateFile
     * @return array|bool
     */
    public function getAllCsvFiles($needDateFile)
    {
        $pathToyo = $this->_dataHelper->getLocation1504();
        $pathBizex = $this->_dataHelper->getLocation1504('b');
        $flagToyo = true;
        $flagBizex = true;
        $csvToyo = array();
        $csvBizex = array();
        //check Toyo folder
        if (!$this->_dataHelper->checkSftpLocation($this->_sftp, $pathToyo, true)) {
            $this->_logger->info(sprintf('Location of Toyo :%s in sFTP does not exist', $pathToyo));
            $flagToyo = false;
        } else {
            $csvToyo = $this->getSftpFiles($needDateFile);
            if ($csvToyo == 2) {
                $this->_logger->info('Toyo CSV files  not found.');
                $flagToyo = false;
            }
        }
        //check Bizex folder
        if (!$this->_dataHelper->checkSftpLocation($this->_sftp, $pathBizex, true)) {
            $this->_logger->info(sprintf('Location of Bizex :%s in sFTP does not exist', $pathBizex));
            $flagBizex = false;
        } else {
            $csvBizex = $this->getSftpFiles($needDateFile, 'b');
            if ($csvBizex == 2) {
                $this->_logger->info('Bizex CSV files  not found.');
                $flagBizex = false;
            }
        }

        if (!$flagToyo && !$flagBizex) {
            return false;
        } else {
            if($csvToyo!=2 && $csvBizex!=2)
            {
                if(is_array($csvToyo) && is_array($csvBizex))
                {
                    return array_merge($csvToyo, $csvBizex);
                }
                else
                {
                    if(is_array($csvToyo))
                    {
                        return $csvToyo;
                    }
                    if(is_array($csvBizex))
                    {
                       return $csvBizex;
                    }
                }

            }
            elseif($csvToyo!=2)
            {
                return $csvToyo;
            }
            else
            {
                return $csvBizex;
            }
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
        foreach ($content as $key => $item)
        {
            if (!empty($item[0]))
            {
                $shipment = $this->_getShipmentByIncrementId(trim($item[0]));
                if($shipment)
                {
                    $shipmentStatus = $shipment->getShipmentStatus();
                    if(in_array($shipmentStatus, $canImportStatus))
                    {
                        $this->_logger->info(sprintf('Processing shipment %s ', $item[0]));
                        $order = $this->_getOrderById($shipment->getData('order_id'));
                        if (!$order) {
                            $this->_logger->info('Order does not exist');
                        } else {
                            $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();
                            if ($paymentMethod != \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD) {
                                $this->_logger->info(sprintf('Order %s:  has payment method invalid: %s', $order->getIncrementId(), $paymentMethod));
                            } else {
                                $this->importItem($shipment, $item, $order);
                            }
                        }
                    }
                    else{
                        $this->_logger->info(sprintf('Can not import shipment number %s. Shipment status is invalid.', $item[0]));
                    }

                }
                else
                {
                    $this->_logger->info(sprintf('The shipment %s does not exist', $item[0]));
                }
            } else {
                $this->_logger->info('Shipment does not exist');
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

        $originDate = $this->_timezone->formatDateTime($this->_dateTime->gmtDate(), 2);
        $systemDate = $this->_dateTime->date('Y-m-d');

        if (!empty($item[3])) {
            $paymentDate = $this->_dateTime->date('Y-m-d', strtotime($item[3]));
        } else {
            $paymentDate = $this->_dateTime->date('Y-m-d', $originDate);
        }

        $amountCollected = !empty($item[4]) ? intval($item[4]) : 0;
        if(!$shipment->getData('grand_total') || $shipment->getData('grand_total')== $shipment->getData('base_shopping_point_amount')){
            $paymentStatus = '';
        }else{
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

            $this->_logger->info(sprintf('Processing shipment %s success', $item[0]));

            /*update payment status for related order*/
            $this->orderProcess($order, $paymentDate, $shipment->getIncrementId());

        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    /**
     * @param $order
     * @param $paymentDate
     */
    public function orderProcess(\Magento\Sales\Model\Order $order, $paymentDate, $shipNumber = null)
    {
        $finalStatus = $this->_dataHelper->isOrderCollected($order->getId());
        if($finalStatus) // completed
        {
            $this->_dataHelper->createInvoiceOrder($order, $paymentDate);
        }
        try {
            //update history
            $order->addStatusToHistory
            (
                $order->getStatus(), __('COD Money Collection, shipment number:') .
                $shipNumber
            )
            ->setIsCustomerNotified(false);
            $order->save();
            $this->_logger->info(sprintf('Change payment status for order %1 success', $order->getIncrementId()));
        } catch (\Exception $e) {
            $this->_logger->info(sprintf('Cannot change payment status for order %1', $order->getIncrementId()));
        }


    }

    public function sendMailResult()
    {
        $taskName = '1504';
        $filesystem = $this->_fileSystemReader;
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $reader = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        if ($this->_fileSystem->isExists($baseDir . '/var/log/shipment1504.log')) {
            $contentLog = $reader->openFile('/var/log/shipment1504.log', 'r')->readAll();
            $emailVariable = ['logContent' => $contentLog,'taskname'=>$taskName];

        } else {
            $emailVariable = ['logContent' => __("Log is not found!"),'taskname'=>$taskName];
        }

        $this->_dataHelper->sendMailResult($emailVariable);
    }


    /**
     * @param $needDateFile
     * @param null $b
     * @return array|int
     */
    public function getSftpFiles($needDateFile, $b = null)
    {
        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
        $host = $this->_dataHelper->getSftpHost();
        $port = $this->_dataHelper->getSftpPort();
        $username = $this->_dataHelper->getSftpUser();
        $password = $this->_dataHelper->getSftpPass();
        $patternRoot = $this->_dataHelper->getPattern1504($b);
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
            $location = $sfptRoot . $this->_dataHelper->getLocation1504($b);
        } else {
            $location = $this->_dataHelper->getLocation1504($b);
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
            if (!in_array($key, array('.', '..'))) {
                $pre = preg_match($pattern, $key);
                if ($pre) {
                    $extension = substr($key, strrpos($key, '.')); // Gets the File Extension
                    if (strtolower($extension) == ".csv") {
                        $filesall[] = $key; // Store in Array
                        $this->_sftp->read($location . DS . $key, $localPath . DS . $key);
                    } // Extensions Allowed
                    else {
                        //move to error
                        $this->_logger->info(sprintf('File: %s is not CSV extension', $key));
                        $this->moveToError($needDateFile, $key, true, $b);
                    }
                } else {
                    //move to error
                    $this->_logger->info(sprintf('File: %s does not match the configed pattern name (%s).', $key, $pattern));
                    $this->moveToError($needDateFile, $key, true, $b);
                }
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
                        $this->_logger->info($e->getMessage());
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
     * @param $needDateFile
     * @param $filename
     * @param bool $ext
     * @param null $b
     * @return bool
     */
    public function moveToError($needDateFile, $filename, $ext = false, $b = null)
    {
        if ($filename != '.' && $filename != '..') {
            $host = $this->_dataHelper->getSftpHost();
            $port = $this->_dataHelper->getSftpPort();
            $username = $this->_dataHelper->getSftpUser();
            $password = $this->_dataHelper->getSftpPass();
            $location = $this->_dataHelper->getLocation1504($b);
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
            $root = $this->_sftp->pwd();
            $location = $root . $location;
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
     * @param $orderId
     * @return bool
     */
    protected function _getOrderById($orderId)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter('entity_id', $orderId)
            ->create();

        $orderCollection = $this->_orderRepository->getList($criteria);

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
    protected function _getShipmentByIncrementId($incrementId)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter('increment_id', $incrementId)
            ->create();

        $shipmentCollection = $this->_shipmentRepository->getList($criteria);

        if ($shipmentCollection->getSize()) {
            return $shipmentCollection->getFirstItem();
        } else {
            return false;
        }
    }

}//end class