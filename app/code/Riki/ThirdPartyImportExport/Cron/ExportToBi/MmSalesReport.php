<?php
namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;
class MmSalesReport{
    const GLOBAL_NAME = 'shipment_item';
    const ORDERTYPE = 'order';
    const ORDERITEMTYPE = 'orderitem';
    const SHIPMENTTYPE = 'shipment';
    const DEFAULT_LOCAL_SAVE = 'var/BI_EXPORT_MM_SALES_REPORT';
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Data
     */
    protected $_dataHelper;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\LoggerCSV
     */
    protected $_log;
    /**
     * @var DateTime
     */
    protected $_datetime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var string
     */
    protected $_path;
    /**
     * @var string
     */
    protected $pathTmp;
    /**
     * @var File
     */
    protected $_file;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\MmSalesReportHelper
     */
    protected $_mmSalesReportHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper
     */
    protected $_bundleItemsHelper;
    /**
     * @var \Magento\Framework\App\ResourceConnection $_resourceConnection
     */
    protected $_resourceConnection ;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connectionDefault;
    protected $_listOrderId = [];
    protected $_listShipment = [];
    protected $_address = [];
    protected $_orderItems = [];
    protected $_customer = [];

    /**
     * MmSalesReport constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $dataHelper
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\MmSalesReport\LoggerCSV $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Filesystem $filesystem
     * @param File $file
     * @param DateTime $datetime
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\MmSalesReportHelper $mmSalesReportHelper
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $dataHelper,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\MmSalesReport\LoggerCSV $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Filesystem $filesystem,
        File $file,
        DateTime $datetime,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\MmSalesReportHelper $mmSalesReportHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
        $this->_dataHelper = $dataHelper;
        $this->_directoryList = $directoryList;
        $this->_log = $logger;
        $this->_log->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_datetime = $datetime;
        $this->_timezone = $timezone;
        $this->_file = $file;
        $this->_filesystem = $filesystem;
        $this->_mmSalesReportHelper = $mmSalesReportHelper;
        $this->_bundleItemsHelper = $bundleItemsHelper;
        $this->_connection = $resourceConnection->getConnection('sales');
        $this->_connectionDefault = $resourceConnection->getConnection(); //get connection default
    }

    /**
     * @return bool
     */
    public function execute(){


        if(!$this->_dataHelper->isEnable()){
            return false;
        }
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $localCsv = $this->_mmSalesReportHelper->getLocalPathExport();
        if(!$localCsv){
            $createFileLocal[] = $baseDir . DS .self::DEFAULT_LOCAL_SAVE;
            $createFileLocal[] = $this->pathTmp = $baseDir . DS .self::DEFAULT_LOCAL_SAVE . '_tmp';
            $this->_path = self::DEFAULT_LOCAL_SAVE;
            $this->pathTmp = self::DEFAULT_LOCAL_SAVE . '_tmp';
        }else{
            if(trim($localCsv,-1) == DS){
                $localCsv = str_replace(DS,'',$localCsv);
            }
            $createFileLocal[] = $baseDir . DS . $localCsv;
            $createFileLocal[] = $baseDir . DS . $localCsv . '_tmp';
            $this->_path = $localCsv;
            $this->pathTmp = $localCsv . '_tmp';
        }
        //delete file log exits before to write new log file
        $this->_dataHelper->backupLog('bi_export_mm_report_sales',$this->_log);

        foreach($createFileLocal as $path){
            if(!$this->_file->isDirectory($path)){
                if(!$this->_file->createDirectory($path)){
                    $this->_log->info(__('Can not create dir file').$path);
                    return;
                }
            }
            if(!$this->_file->isWritable($path)){
                $this->_log->info(__('The folder have to change permission to 755').$path);
                return ;
            }
        }
        $this->_csv = new \Magento\Framework\File\Csv(new File());
        $_header[] = $this->_prepareHeader();
        $shipmentIDs = $this->getIdShipemntExport();
        //  there are no shipments id return false
        if(!$shipmentIDs){
            $this->_log->info(__('There isnt shipment export in mm sales order report'));
            $arrayExport = $_header;
        }else{
            $this->_prepareListShipmentById($shipmentIDs);
            $this->_prepareListIdMmOrder();
            $this->_prepareListConsumerDb();
            $this->_prepareListAddress();
            $this->_prepareOrderItem();
            $_datas = $this->_prepareShipmentItem();
            $arrayExport = array_merge($_header,$_datas);
            $this->upDateStatusMMSalesReport($shipmentIDs);
        }
        //create Name csv
        $nameCsv = 'mm_sales_report-'.$this->_timezone->date()->format('YmdHis').'.csv';
        //Create csv
        if(!$this->_file->isExists($baseDir.DS.$this->pathTmp.DS.$nameCsv)){
            $this->_csv->saveData($baseDir.DS.$this->pathTmp.DS.$nameCsv,$arrayExport);
        }
        $pathFtp = $this->_mmSalesReportHelper->getSFTPPathExport();
        $pathFtpReport = $this->_mmSalesReportHelper->getSFTPPathExportReport();

        $this->_dataHelper->MoveFileToFtp('mm-sales-report',$this->pathTmp,$this->_path,$pathFtp,$this->_log,$pathFtpReport);
        //set last time to run
        $this->_mmSalesReportHelper->setLastRunToCron($this->_dataHelper->getTimeByUtc());
        $this->_dataHelper->sentMail('bi_export_mm_report_sales',$this->_log);
    }
    /**
     * @param $table
     * @return array|void
     */
    public function getNameColumnByName($table){
        if(!$table)
            return;
        $connection = $this->_connection;
        $detailTables = $connection->describeTable($table);
        return array_keys($detailTables);
    }
    /**
     * @return array
     */
    protected function _prepareHeader(){
        //get column shippment item
        $columnShipmentItems = $this->getNameColumnByName('sales_shipment_item');
        foreach($columnShipmentItems as $key => $columnShipmentItem){
            $columnShipmentItems[$key] = self::GLOBAL_NAME . '.' .$columnShipmentItem;
        }
        $columnShipments = $this->getNameColumnByName('sales_shipment');
        //add prefix shipment_
        foreach($columnShipments as $key => $columnShipment){
            $columnShipments[$key] = self::GLOBAL_NAME . '.' . 'shipment_' .$columnShipment;
        }
        //add prefix order_
        $columnSalesOrders = $this->getNameColumnByName('sales_order');
        foreach($columnSalesOrders as $key => $columnOrder){
            $columnSalesOrders[$key] = self::GLOBAL_NAME . '.' . 'order_' .$columnOrder;
        }
        //add prefix order_item_
        $columnSalesOrderItems = $this->getNameColumnByName('sales_order_item');
        foreach($columnSalesOrderItems as $key => $columnOrderItem){
            $columnSalesOrderItems[$key] = self::GLOBAL_NAME . '.' . 'order_item_' .$columnOrderItem;
        }
        //add prefix order_item_es
        $columnBillingAddress = $this->getNameColumnByName('sales_order_address');
        foreach($columnBillingAddress as $key => $column) {
            $columnBillingAddress[$key] = self::GLOBAL_NAME . '.' . 'billing_address_' .$column;
        }

        $columnShippingAddress = $this->getNameColumnByName('sales_order_address');
        foreach($columnShippingAddress as $key => $column){
            $columnShippingAddress[$key] = self::GLOBAL_NAME . '.' . 'shipping_address_' .$column;
        }
        return array_merge(
            $columnShipmentItems,
            $columnShipments,
            $columnSalesOrders,
            $columnSalesOrderItems,
            $columnBillingAddress,
            $columnShippingAddress,
            ['shipment_item.customer_consumer_db_id']
        );
    }
    /**
     * @return array
     */
    public function _prepareListShipmentById($shipmentIds = []){
        $select = $this->_connection->select();
        $select->from('sales_shipment','*')
            ->where('entity_id in (?)', $shipmentIds);
        return $this->_listShipment = $this->_connection->fetchAssoc($select);
    }
    /**
     * @return $this
     */
    public function _prepareListIdMmOrder(){
        $orderIds = [];
        foreach($this->_listShipment as $shipment){
            if(!in_array($shipment['order_id'],$orderIds)){
                $orderIds[] = $shipment['order_id'];
            }
        }
        $select = $this->_connection->select();
        $select->from('sales_order')
            ->where('entity_id in (?)', $orderIds);
        $results = $this->_connection->fetchAssoc($select);
        $this->_listOrderId = array_keys($results);
        return $this ;
    }

    public function _prepareListConsumerDb(){
        $select = $this->_connection->select();
        $select->from('sales_order', 'customer_id')
            ->where('entity_id in (?)', $this->_listOrderId);
        $customerIdArr = $this->_connection->fetchCol($select);

        $selectDefault = $this->_connectionDefault->select();
        $selectDefault->from('customer_entity')
            ->where('entity_id in (?)',$customerIdArr);
        return $this->_customer = $this->_connectionDefault->fetchAssoc($selectDefault);
    }
    /**
     * @return array
     */
    public function _prepareListAddress(){
        $select = $this->_connection->select();
        $select->from('sales_order_address','*')
            ->where('parent_id in (?)', $this->_listOrderId);
        return $this->_address = $this->_connection->fetchAssoc($select);
    }

    /**
     * @param $shipmentId
     * @return mixed
     */
    public function getOrderIdbyShipmentId($shipmentId){
        if(isset($this->_listShipment[$shipmentId]['order_id'])){
            return $this->_listShipment[$shipmentId]['order_id'];
        }
    }

    /**
     * @return $this
     */
    public function _prepareOrderItem(){
        $select = $this->_connection->select();
        $select->from('sales_order_item' , '*')
            ->where('order_id in (?)', $this->_listOrderId);

        $this->_orderItems = $this->_connection->fetchAssoc($select);
        return $this;
    }

    public function _prepareOrder(){
        $select = $this->_connection->select();
        $select->from('sales_order' , '*')
            ->where('entity_id in (?)', $this->_listOrderId);

        return $this->_connection->fetchAssoc($select);
    }

    /**
     * prepare shipment item to export
     * @return array
     */
    public function _prepareShipmentItem()
    {
        $result = [];

        $listShipmentId = array_keys($this->_listShipment);

        $orders = $this->_prepareOrder();

        $select = $this->_connection->select();

        $select->from(
            'sales_shipment_item','*'
        )->where(
            'parent_id in (?)', $listShipmentId
        );

        $shipmentItem = $this->_connection->fetchAll($select);

        if ($shipmentItem) {
            foreach ($shipmentItem as $item) {

                $orderId = $this->getOrderIdbyShipmentId($item['parent_id']);

                if (array_key_exists('customer_id',$orders[$orderId] )) {

                    $customerId = $orders[$orderId]['customer_id'];

                    $itemValue = array_values($item);

                    $consumerId = (isset($this->_customer[$customerId]['consumer_db_id']) ? [$this->_customer[$customerId]['consumer_db_id']] : []);

                    /*convert sales shipment datetime columns to config timezone*/
                    $shipmentData = $this->convertDateTimeColumnsToConfigTimezone(self::SHIPMENTTYPE, $this->_listShipment[$item['parent_id']]);

                    /*convert sales order datetime columns to config timezone*/
                    $orderData = $this->convertDateTimeColumnsToConfigTimezone(self::ORDERTYPE,$orders[$orderId]);

                    /*convert sales order item datetime columns to config timezone*/
                    $orderItemData = $this->convertDateTimeColumnsToConfigTimezone(self::ORDERITEMTYPE,$this->_orderItems[$item['order_item_id']]);

                    /*re calculate data for bundle children item*/
                    $orderItemData = $this->_bundleItemsHelper->reCalculateOrderItem($orderItemData);

                    $result[] = array_merge(
                        $itemValue,
                        array_values($shipmentData),
                        array_values($orderData),
                        array_values($orderItemData),
                        array_values($this->_address[$this->_listShipment[$item['parent_id']]['billing_address_id']]),
                        array_values($this->_address[$this->_listShipment[$item['parent_id']]['shipping_address_id']]),
                        $consumerId
                    );
                }
            }
        }

        return $result;
    }
    public function getIdShipemntExport(){
        $shipmentIds = [];
        $select = $this->_connection->select();
        $select->from('sales_shipment','entity_id')
            ->join('sales_order', 'sales_order.entity_id = sales_shipment.order_id' ,'')
            ->where('sales_order.mm_order_id is not null')
            ->where('sales_shipment.is_mm_exported = ?' ,0);
        $result = $this->_connection->fetchAssoc($select);
        if($result){
            $shipmentIds = array_keys($result);
        }
        return $shipmentIds;
    }
    public function upDateStatusMMSalesReport($shipemntIds = []){
        $this->_connection->update(
            'sales_shipment',
            ['is_mm_exported' => 1],
            'entity_id in ('.implode(",",$shipemntIds).')'
        );
    }

    /**
     * Convert datetime columns to config timezone for order/order_item/shipment object
     *
     * @param $type
     * @param $object
     * @return mixed
     */
    public function convertDateTimeColumnsToConfigTimezone($type, $object)
    {
        $dateTimeColumns = [];
        if ($type == self::ORDERTYPE) {
            $dateTimeColumns = $this->getOrderDateTimeColumns();
        } else if($type == self::ORDERITEMTYPE) {
            $dateTimeColumns = $this->getOrderItemDateTimeColumns();
        } else if($type == self::SHIPMENTTYPE) {
            $dateTimeColumns = $this->getShipmentDateTimeColumns();
        }

        if ($dateTimeColumns) {
            foreach ($dateTimeColumns as $cl) {

                if (!empty($object[$cl])) {
                    $object[$cl] = $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($object[$cl], 2, 2));
                }
            }
        }

        return $object;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order
     * @return mixed
     */
    public function getOrderDateTimeColumns()
    {
        return [
            'created_at', 'updated_at', 'customer_dob', 'csv_start_date'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order_item
     * @return mixed
     */
    public function getOrderItemDateTimeColumns()
    {
        return [
            'created_at', 'updated_at'
        ];
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_shipment
     * @return mixed
     */
    public function getShipmentDateTimeColumns()
    {
        return [
            'created_at', 'updated_at', 'shipment_date', 'payment_date', 'export_sap_date', 'nestle_payment_receive_date'
        ];
    }
}