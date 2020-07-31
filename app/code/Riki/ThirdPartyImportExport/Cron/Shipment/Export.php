<?php

namespace Riki\ThirdPartyImportExport\Cron\Shipment;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;

class Export
{
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\DataShipping
     */
    protected $_dataHelper;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Io\Sftp
     */
    protected $_sftp;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\LoggerShippingCSV
     */
    protected $_logger1;
    /**
     * @var DateTime
     */
    protected $_datetime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $_collectionFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_connection;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    protected $_csv;
    protected $_baseDir;
    protected $_path;
    protected $pathTmp;
    protected $_file;

    /**
     * Export constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Helper\DataShipping $dataHelper
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Io\Sftp $sftp
     * @param \Riki\ThirdPartyImportExport\Logger\LoggerShippingCSV $logger
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shippingCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param DateTime $datetime
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\DataShipping $dataHelper,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\Sftp $sftp,
        \Riki\ThirdPartyImportExport\Logger\LoggerShippingCSV $logger,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shippingCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Filesystem $filesystem,
        DateTime $datetime,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->_dataHelper = $dataHelper;
        $this->_directoryList = $directoryList;
        $this->_sftp = $sftp;
        $this->_logger1 = $logger;
        $this->_logger1->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_timezone = $timezone;
        $this->_datetime = $datetime;
        $this->_collectionFactory = $shippingCollectionFactory;
        $this->_connection = $resourceConnection;
        $this->_orderFactory = $orderFactory;
        $this->_productFactory = $productFactory;
        $this->_fileSystem = $filesystem;
        $this->_encryptor = $encryptor;
    }

    public function initExport(){

        $valid = true;

        $this->_file = new File();

        $this->_csv = new \Magento\Framework\File\Csv( $this->_file );

        $this->_baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);

        $localCsv = $this->_dataHelper->getLocalPathExport();

        if (trim($localCsv, -1) == DS) {
            $localCsv = substr($localCsv,-1);
        }

        $this->_path = $localCsv;

        $this->pathTmp = $this->_path . '_tmp';

        //delete file log exits before to write new log file
        $this->backupLog($this->_timezone->date()->format('YmdHis'));

        if (!$this->_file->isDirectory($this->_baseDir. DS .$this->_path)) {
            if (!$this->_file->createDirectory($this->_baseDir. DS .$this->_path)) {
                $this->_logger1->info(__('Can not create dir file') . $this->_path);
                $valid = false;
            }
        } else {
            if (!$this->_file->isWritable($this->_baseDir. DS .$this->_path)) {
                $this->_logger1->info(__('The folder have to change permission to 755') . $this->_path);
                $valid = false;
            }
        }

        if (!$this->_file->isDirectory($this->_baseDir. DS .$this->pathTmp)) {
            if (!$this->_file->createDirectory($this->_baseDir. DS .$this->pathTmp)) {
                $this->_logger1->info(__('Can not create dir file') . $this->pathTmp);
                $valid = false;
            }
        } else {
            if (!$this->_file->isWritable($this->_baseDir. DS .$this->pathTmp)) {
                $this->_logger1->info(__('The folder have to change permission to 755') . $this->pathTmp);
                $valid = false;
            }
        }

        return $valid;

    }

    public function execute()
    {
//        if(!$this->_dataHelper->isEnable()){
//            return false;
//        }

        /*prepare data and folder for export*/
        if(!$this->initExport()){
            return false;
        }

        $baseDir = $this->_baseDir;

        $pathLocal  = $baseDir. '/' . $this->pathTmp . '/';

        /**
         * get list shipment which have status delivery completed
         */
        $shipment = $this->_collectionFactory->create();
        $shipment->addAttributeToFilter('shipment_status','delivery_completed');
        $shipment->addAttributeToFilter('flag_shipment_complete','0');
        $shipment->setPageSize(20);
        $i = 0;
        $totalCount = $shipment->getTotalCount();
        $arrayExportShipment = array();
        // list array move sftp
        $listFilesMove = array();
        if($totalCount){
            foreach($shipment->getItems() as $shipment){
                $this->_logger1->info(sprintf(__('Shiping export id  : %s'),$shipment->getData('entity_id')));
                if($i == 0){
                    $arrayExportShipment[$i] = array(
                        'entity_id',
                        'store_id',
                        'total_weight',
                        'total_qty',
                        'email_sent',
                        'send_email',
                        'shipping_address_id',
                        'billing_address_id',
                        'shipment_status',
                        'increment_id',
                        'created_at',
                        'updated_at',
                        'packages',
                        'shipping_label',
                        'customer_note',
                        'customer_note_notify',
                        'delivery_type',
                        'sap_customer_id',
                        'free_of_charge',
                        'substitution',
                        'warehouse',
                        'shipment_delivery_date',
                        'ship_out_date',
                        'shipment_completion_date',
                        'shipping_fee',
                        'payment_fee',
                        'collection_date',
                        'nestle_payment_date',
                        'nestle_payment_amount',
                        'ship_status',
                        'ship_zsim',
                        'delivery_date',
                        'delivery_time',
                        'ship_status_1501',
                        'ship_status_1502',
                        'ship_status_1504',
                        'is_exported',
                        'export_date'
                    );
                    $i++;
                }
                $order = $this->_orderFactory->create()->load($shipment->getData('order_id'));
                $arrayExportShipment[$i] = array(
                    $shipment->getData('entity_id'),
                    $shipment->getData('store_id'),
                    $shipment->getData('total_weight'),
                    $shipment->getData('total_qty'),
                    $shipment->getData('email_sent'),
                    $shipment->getData('send_email'),
                    $shipment->getData('shipping_address_id'),
                    $shipment->getData('billing_address_id'),
                    $shipment->getData('shipment_status'),
                    $shipment->getData('increment_id'),
                    $shipment->getData('created_at'),
                    $shipment->getData('updated_at'),
                    '',//$shipment->getData('packages'),
                    $shipment->getData('shipping_label'),
                    $shipment->getData('customer_note'),
                    $shipment->getData('customer_note_notify'),
                    '', // delivery_type
                    '', // sap_customer_id
                    '', // free_of_charge
                    '', // substitution
                    $shipment->getData('warehouse'),
                    '', // shipment_delivery_date
                    '', // ship_out_date
                    '' , // shipment_completion_date
                    '', // shipping_fee
                    $order->getData('fee'),//paymentFee
                    '', // collection_date
                    '', // nestle_payment_date
                    '', // nestle_payment_amount
                    $shipment->getData('ship_status'),
                    $shipment->getData('ship_zsim'),
                    $shipment->getData('delivery_date'),
                    $shipment->getData('delivery_time'),
                    $shipment->getData('ship_status_1501'),
                    $shipment->getData('ship_status_1502'),
                    $shipment->getData('ship_status_1504'),
                    $shipment->getData('is_exported'),
                    $shipment->getData('export_date')
                );

                $nameShippingOrder = 'shipmentheader_'. $shipment->getData('entity_id') . '_' .$this->_timezone->date()->format('YmdHis').'.csv';
                //Create csv
                $this->_csv->saveData($pathLocal.$nameShippingOrder,$arrayExportShipment);
                $shipment->setData('flag_shipment_complete','1');
                $shipment->save();
                //create Name csv
                $listFilesMove[] = $nameShippingOrder;
                //export detail shipping
                $listItemShipment = $this->exportItemsShipment($shipment,$order,$pathLocal);
                $listFilesMove = array_merge($listFilesMove,$listItemShipment);
                $i = 0;
            }
        }else{
            $this->_logger1->info(__('No shippment to export'));
        }
        /*
      * Move to sftp
      * */
        $host = $this->_dataHelper->getSftpHost();
        $port = $this->_dataHelper->getSftpPort();
        $username = $this->_dataHelper->getSftpUser();
        $password = $this->_encryptor->decrypt($this->_dataHelper->getSftpPass());
        $pathFtp = $this->_dataHelper->getSFTPPathExport();
        $location= DIRECTORY_SEPARATOR.$pathFtp;//dir path folder sFTP
        //connect ftp
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
            $this->_logger1->info($e->getMessage());
            return;
        }
        //create file on ftp
        $dirList = explode('/', $location);
        foreach ($dirList as $dir) {
            if($dir != '') {
                if (!$this->_sftp->cd($dir)) {
                    try {
                        $this->_sftp->mkdir('/' . $dir);
                    } catch (\Exception $e) {
                        $this->_logger1->info($e->getMessage());
                    }
                }
                try {
                    $this->_sftp->cd($dir);
                } catch(\Exception $e) {
                    $this->_logger1->info($e->getMessage());
                }
            }
        }

        try {
            //Move file
            if($listFilesMove){
                foreach ($listFilesMove as $file) :
                    $this->_sftp->write($this->_sftp->pwd() .'/' . $file , $pathLocal.$file);
                    $this->_logger1->info("Upload ".$pathLocal.$file." to FTP successfully");
                endforeach;
                $this->_logger1->info("summary of  ".$totalCount." shippment export successfully");
            }else{
                /**
                 * create empty file to sent
                 */
                $listfile = $this->CreateEmptyFileToSent($pathLocal);

                foreach ($listfile as $file) {
                    $this->_sftp->write($this->_sftp->pwd() .'/' . $file , $pathLocal.$file);
                }
            }

            /*move export folder to ftp*/
            $this->_dataHelper->MoveFileToFtp( $this->pathTmp, $this->_path, $pathFtp, $this->_logger1);

        } catch (\Exception $e) {
            $this->_logger1->info($e->getMessage());
        }

        $this->_sftp->close();
        //end control export file
        //sent mail file log
        $this->_logger1->info("Sending notification emails ....");
        $reader = $this->_fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $contentLog=  $reader->openFile('/var/log/shipping_delivery_complete_export.log','r')->readAll();
        $emailVariable = ['logContent'=> $contentLog];
        $this->_dataHelper->sendMailShipmentExporting($emailVariable);
    }
    /**
     * @param $needDate
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function backupLog( $needDate)
    {
        $varDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $fileSystem = new File();
        $backupFolder = '/log/ShippingExportBackup';
        $localPath = $varDir.$backupFolder;
        if(!$fileSystem->isDirectory($localPath)){
            if(!$fileSystem->createDirectory($localPath,0777)){
                $this->_logger1->info(__('Can not create dir file').$localPath);
                return;
            }
        }
        $fileLog = $varDir.'/log/shipping_delivery_complete_export.log';
        $newLog = $varDir.'/'.$backupFolder.'/'.'shipment_exporter_'.$needDate.'.log';
        if($fileSystem->isWritable($localPath) && $fileSystem->isExists($fileLog))
        {
            $fileSystem->rename($fileLog,$newLog);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     */
    private function exportItemsShipment($shipment,$order,$pathLocal){
        $j = 0;
        $listFilesMove = [];
        $arrayShippingDetails = array();
        foreach($shipment->getItems() as $item){
            if($j == 0){
                $arrayShippingDetails[$j] = array(
                    __('entity_id'),
                    __('parent_id'),
                    __('row_total'),
                    __('price'),
                    __('weight'),
                    __('qty'),
                    __('product_id'),
                    __('order_item_id'),
                    __('additional_data'),
                    __('description'),
                    __('name'),
                    __('sku'),
                    __('gps_price'),
                    __('discount_amount_excl_tax'),
                    __('sap_trans_id'),
                    __('distribution_channel'),
                    __('material_type'),
                    __('sales_organization'),
                    __('booking_wbs'),
                    __('booking_account'),
                    __('booking_center'),
                    __('tax_amount'),
                    __('commission_amount'),
                    __('commission_condition_type'),
                    __('point_amount'),
                    __('booking_point_wbs'),
                    __('booking_point_account'),
                    __('free_of_charge'),
                    __('gw_code'),
                    __('tax_class'),
                    __('wrapping_fee'),
                );
                $j++;
            }
            $product = $this->_productFactory->create()->load($item->getData('product_id'));
            $orderItem = $order->getItemById($item->getData('order_item_id'));
            $arrayShippingDetails[$j] = array(
                $item->getData('entity_id'),
                $item->getData('parent_id'),
                $item->getData('row_total'),
                $item->getData('price'),
                $item->getData('weight'),
                $item->getData('qty'),
                $item->getData('product_id'),
                $item->getData('order_item_id'),
                $item->getData('additional_data'),
                $item->getData('description'),
                $item->getData('name'),
                $item->getData('sku'),
                $product->getData('gps_price'),
                $orderItem->getData('discount_amount_excl_tax'),
                $item->getData('sap_trans_id'),
                '', //distribution_channel
                $product->getData('material_type'),
                $product->getData('sales_organization'),
                $product->getData('booking_wbs'),
                $product->getData('booking_account'),
                $product->getData('booking_center'),
                $orderItem->getData('tax_amount'),
                $order->getData('commission_amount'),
                '', //commission_condition_type
                '', //  point_amount
                $product->getData('booking_point_wbs'),
                $product->getData('booking_point_account'),
                '', //free_of_charge
                '', //gw_code
                '', //tax_class
                '', //wrapping_fee
            );
            $nameShippingDetailOrder = 'shipmentdetail_'. $shipment->getData('entity_id') .'_'.$this->_timezone->date()->format('YmdHis').'.csv';
            $this->_csv->saveData($pathLocal.$nameShippingDetailOrder,$arrayShippingDetails);
            $listFilesMove[] = $nameShippingDetailOrder;
            $j++;
        }
        return $listFilesMove;
    }
    /**
     * @param $pathLocal
     * @return array
     */
    private function CreateEmptyFileToSent($pathLocal){
        $arrayExportShipment[] = array(
            'entity_id',
            'store_id',
            'total_weight',
            'total_qty',
            'email_sent',
            'send_email',
            'shipping_address_id',
            'billing_address_id',
            'shipment_status',
            'increment_id',
            'created_at',
            'updated_at',
            'packages',
            'shipping_label',
            'customer_note',
            'customer_note_notify',
            'delivery_type',
            'sap_customer_id',
            'free_of_charge',
            'substitution',
            'warehouse',
            'shipment_delivery_date',
            'ship_out_date',
            'shipment_completion_date',
            'shipping_fee',
            'payment_fee',
            'collection_date',
            'nestle_payment_date',
            'nestle_payment_amount',
            'ship_status',
            'ship_zsim',
            'delivery_date',
            'delivery_time',
            'ship_status_1501',
            'ship_status_1502',
            'ship_status_1504',
            'is_exported',
            'export_date'
        );
        $arrayShippingDetails[] = array(
            __('entity_id'),
            __('parent_id'),
            __('row_total'),
            __('price'),
            __('weight'),
            __('qty'),
            __('product_id'),
            __('order_item_id'),
            __('additional_data'),
            __('description'),
            __('name'),
            __('sku'),
            __('gps_price'),
            __('discount_amount_excl_tax'),
            __('sap_trans_id'),
            __('distribution_channel'),
            __('material_type'),
            __('sales_organization'),
            __('booking_wbs'),
            __('booking_account'),
            __('booking_center'),
            __('tax_amount'),
            __('commission_amount'),
            __('commission_condition_type'),
            __('point_amount'),
            __('booking_point_wbs'),
            __('booking_point_account'),
            __('free_of_charge'),
            __('gw_code'),
            __('tax_class'),
            __('wrapping_fee'),
        );
        $nameShippingOrder = 'shipmentheader_' .$this->_timezone->date()->format('YmdHis').'.csv';
        $this->_csv->saveData($pathLocal.$nameShippingOrder,$arrayExportShipment);
        $listFilesMove[] = $nameShippingOrder;
        $nameShippingDetailOrder = 'shipmentdetail_'.$this->_timezone->date()->format('YmdHis').'.csv';
        $this->_csv->saveData($pathLocal.$nameShippingDetailOrder,$arrayShippingDetails);
        $listFilesMove[] = $nameShippingDetailOrder;
        return $listFilesMove;
    }
}