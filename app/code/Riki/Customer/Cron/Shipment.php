<?php

namespace Riki\Customer\Cron;

class Shipment
{
    const DEFAULT_LOCAL_SAVE = 'var/cedyna_shosha_shipment';

    const CONFIG_PATH_FTP_CSV = 'export_shosha/folder_setting_shipment/folder_ftp';
    const CONFIG_PATH_LOCAL_CSV = 'export_shosha/folder_setting_shipment/folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'export_shosha/folder_setting_shipment/cron_last_time_run';

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_resourceConfig;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_file;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper
     */
    protected $_dataHelper;
    /**
     * @var \Riki\Customer\Logger\Shosha\LoggerCSV
     */
    protected $_logger;

    /*csv processor*/
    protected $_csv;

    protected $_baseDir;
    protected $_path;
    protected $_pathTmp;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $_shipmentCollectionFactory;

    /**
     * Shipment constructor.
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $dataHelper
     * @param \Riki\Customer\Logger\Shosha\LoggerCSV $logger
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shippingCollectionFactory
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $dataHelper,
        \Riki\Customer\Logger\Shosha\LoggerCSV $logger,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shippingCollectionFactory,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Customer\Model\ShoshaFactory $shoshaFactory
    ){

        $this->_resourceConfig = $resourceConfig;
        $this->_scopeConfig = $scopeConfig;
        $this->_dateTime = $dateTime;
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_dataHelper = $dataHelper;
        $this->_logger = $logger;
        $this->_shipmentCollectionFactory = $shippingCollectionFactory;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_customerRepository = $customerRepository;
        $this->_shoshaFactory = $shoshaFactory;

        $this->_connection = $resourceConnection->getConnection();
        $this->_connectionSales = $resourceConnection->getConnection('sales');


    }

    /**
     * _initExport
     *
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function initExport(){

        $valid = true;

        $this->_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());

        $this->_baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);

        $localCsv = $this->getLocalPathExport();

        if (!$localCsv) {
            $this->_path = self::DEFAULT_LOCAL_SAVE;
        } else {
            if (trim($localCsv, -1) == DS) {
                $localCsv = str_replace(DS, '', $localCsv);
            }
            $this->_path = $localCsv;
        }

        $this->_pathTmp = $this->_path . '_tmp';

        //delete file log exits before to write new log file
        $this->_dataHelper->backupLog('cedyna_export_shosha_shipment', $this->_logger);

        if (!$this->_file->isDirectory($this->_baseDir. DS .$this->_path)) {
            if (!$this->_file->createDirectory($this->_baseDir. DS .$this->_path)) {
                $this->_logger->info(__('Can not create dir file') . $this->_path);
                $valid = false;
            }
        } else {
            if (!$this->_file->isWritable($this->_baseDir. DS .$this->_path)) {
                $this->_logger->info(__('The folder have to change permission to 755') . $this->_path);
                $valid = false;
            }
        }

        if (!$this->_file->isDirectory($this->_baseDir. DS .$this->_pathTmp)) {
            if (!$this->_file->createDirectory($this->_baseDir. DS .$this->_pathTmp)) {
                $this->_logger->info(__('Can not create dir file') . $this->_pathTmp);
                $valid = false;
            }
        } else {
            if (!$this->_file->isWritable($this->_baseDir. DS .$this->_pathTmp)) {
                $this->_logger->info(__('The folder have to change permission to 755') . $this->_pathTmp);
                $valid = false;
            }
        }

        return $valid;

    }


    /**
     * @return $this
     */
    public function execute(){

        /*prepare data and folder for export*/
        if(!$this->initExport()){
            return false;
        }

        /*export process*/
        $this->exportProcess();

        /* set last time to run */
        $this->setLastRunToCron($this->_dateTime->gmtDate('Y-m-d H:i:s'));

        /*move export folder to ftp*/
        $this->_dataHelper->MoveFileToFtp('shosha', $this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->_logger);
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function exportProcess(){

        list($aShipmentData,$aShoshaBusinessCode) = $this->_loadDataShipment();

        $sExportShipmentFile = 'shipmentheader-'.$this->_dateTime->date('Ymd').'.csv';

        //Create csv
        if (!$this->_file->isExists($this->_baseDir . DS . $this->_pathTmp . DS . $sExportShipmentFile)) {
            $this->_csv->saveData($this->_baseDir . DS . $this->_pathTmp . DS . $sExportShipmentFile, $aShipmentData);
        }


        //export shosha code
        $aShoshaData = $this->_loadShoshaBusinessCode($aShoshaBusinessCode);
        $sExportShoshaFile = 'shosha-'.$this->_dateTime->date('Ymd').'.csv';

        //Create csv
        if (!$this->_file->isExists($this->_baseDir . DS . $this->_pathTmp . DS . $sExportShoshaFile)) {
            $this->_csv->saveData($this->_baseDir . DS . $this->_pathTmp . DS . $sExportShoshaFile, $aShoshaData);
        }
    }


    /**
     * @param string $fileName
     * @return array
     */
    protected function _loadDataShipment(){


        $selectShipmentHistory = $this->_connection->select()->from([
            'sp' => $this->_connection->getTableName('riki_shipment_shipping_history')
        ])
        ->where('shipment_status = ?  ',\Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_SHIPPED_OUT)
        ->where(new \Zend_Db_Expr(sprintf("shipment_date BETWEEN '%s' AND %s", $this->getLastRunToCron(), new \Zend_Db_Expr('NOW()'))));


        $queryShipmentHistory = $this->_connection->query($selectShipmentHistory);

        $aShoshaBusinesCode = [];
        $aShipmentIds = [];
        while ($shipmentData = $queryShipmentHistory->fetch()) {
            if(isset($shipmentData['shipment_id'])) {

                try {
                    $isCedyna = false;
                    $shipment = $this->_shipmentRepository->get($shipmentData['shipment_id']);

                    $customer = $this->_customerRepository->getById($shipment->getCustomerId());

                    if($customer){
                        $oShoshaBusinessCode = $customer->getCustomAttribute('shosha_business_code');
                        if ($oShoshaBusinessCode) {
                            $oShoshaCollection = $this->_shoshaFactory->create()->getCollection()->addFieldToFilter('shosha_business_code', array('in' => [$oShoshaBusinessCode->getValue()]));
                            if ($oShoshaCollection->getSize()) {
                                foreach ($oShoshaCollection as $shosha) {
                                    if (\Riki\Customer\Model\Shosha\ShoshaCode::CEDYNA == $shosha->getData('shosha_code')) {
                                        $isCedyna = true;
                                        $aShoshaBusinesCode[] = $oShoshaBusinessCode->getValue();
                                    }
                                }
                            }
                        }
                    }


                    $order = $shipment->getOrder();
                    $payment = $order->getPayment();
                    $paymentMethod = '';

                    if ($payment) {
                        $paymentMethod = $payment->getMethodInstance()->getCode();
                    }

                    if ($isCedyna && \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_INVOICED == $paymentMethod) {

                        $this->_loadDataShipmentDetail($shipment->getId(), $shipment->getIncrementId());

                        $aShipmentIds[] = $shipment->getId();

                        //update export flag cedyna
                        $shipment->setData('exported_cedyna_flg', 1);
                        $shipment->save();

                    }

                }catch (\Exception $e){
                    $this->_logger->critical($e->getMessage());
                }
            }

        }

        //list shipment id
        $table = $this->_connectionSales->getTableName('sales_shipment');

        $columns = array_keys($this->_connectionSales->describeTable($table));

        $resultData = [$columns];

        if($aShipmentIds){

            $select = $this->_connectionSales->select()->from(
                $table, $columns
            )->where(
                $table . '.entity_id IN (?)',
                $aShipmentIds
            );

            $query = $this->_connectionSales->query($select);

            while($row = $query->fetch()){
                $resultData[] = $row;
            }
        }

        return [$resultData,$aShoshaBusinesCode];
    }

    /**
     * @param $iShipmentId
     * @param $iShipmentIncrementId
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function _loadDataShipmentDetail($iShipmentId,$iShipmentIncrementId){

        $table = $this->_connectionSales->getTableName('sales_shipment_item');

        $columns = array_keys($this->_connectionSales->describeTable($table));

        $aShipmentDetailData = [$columns];

        //conditon
        $select = $this->_connectionSales->select()->from(
            $table, $columns
        )->where(
            $table . '.parent_id = ?',
            $iShipmentId
        );

        $query = $this->_connectionSales->query($select);

        while($row = $query->fetch()){
            $aShipmentDetailData[] = $row;
        }

        $exportDate = $this->_dateTime->date('Ymd');
        $exportFile = 'shipmentdetail-'.$iShipmentIncrementId.'-'.$exportDate.'.csv';

        //Create csv
        if (!$this->_file->isExists($this->_baseDir . DS . $this->_pathTmp . DS . $exportFile)) {
            $this->_csv->saveData($this->_baseDir . DS . $this->_pathTmp . DS . $exportFile, $aShipmentDetailData);
        }
    }

    /**
     * @param $aShoshaBusinesCode
     * @return mixed
     */
    public function _loadShoshaBusinessCode($aShoshaBusinesCode){
        $columnShoshaBusinessCode = array_keys($this->_connection->describeTable($this->_connection->getTableName('riki_shosha_business_code')));

        /*export data { first record is columns name }*/
        $resultData = [$this->addColumnPrefix($columnShoshaBusinessCode)];

        if(count($aShoshaBusinesCode)){

            $selectShoshaBusinessCode = $this->_connection->select()->from(
                $this->_connection->getTableName('riki_shosha_business_code'), $columnShoshaBusinessCode
            )->where(
                $this->_connection->getTableName('riki_shosha_business_code') . '.shosha_business_code IN (?)',
                $aShoshaBusinesCode
            );

            $queryShoshaBusinessCode = $this->_connection->query($selectShoshaBusinessCode);

            while($aShoshaBusinesCodeData = $queryShoshaBusinessCode->fetch()){
                $resultData[] = $aShoshaBusinesCodeData;
            }
        }

        return $resultData;
    }

    /**
     * @param $columns
     * @return array
     */
    public function addColumnPrefix($columns){
        return array_map(function($value) { return 'shosha.'.$value; }, $columns);
    }
    /**
     * @return mixed
     */
    public function getLocalPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->_scopeConfig->getValue(self::CONFIG_PATH_LOCAL_CSV,$storeScope);
        return $LocalPath;
    }
    /**
     * @return mixed
     */
    public function getSFTPPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->_scopeConfig->getValue(self::CONFIG_PATH_FTP_CSV,$storeScope);
        return $LocalPath;
    }
    /**
     * @return mixed
     */
    public function getLastRunToCron()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->_scopeConfig->getValue(self::CONFIG_CRON_RUN_LAST_TIME, $storeScope);
    }

    /**
     * @param $time
     */
    public function setLastRunToCron($time)
    {
        $this->_resourceConfig->saveConfig(self::CONFIG_CRON_RUN_LAST_TIME,$time,'default',0);
    }
}