<?php

namespace Riki\ThirdPartyImportExport\Cron\CvsOrderPayment;

use Magento\Framework\Filesystem\Driver\File;
use Zend\Serializer\Serializer;

class Export
{
    const ORDERTYPE = 'order';
    const ORDERITEMTYPE = 'orderitem';
    /**
     * Default folder export
     *
     */
    const DEFAULT_LOCAL_SAVE = 'var/CVS_ORDER';

    /**
     * Payment method is cvspayment
     */
    const CVS_ORDER_PAYMENT = 'cvspayment';

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\CvsOrderPayment
     */
    protected $salesOrderHelper;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Riki\ThirdPartyImportExport\Logger\CvsOrderPayment\LoggerCSV $logger
     */
    protected $log;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $pathTmp;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $timeLastRunCron;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    /**
     * @var File
     */
    protected $fileSystem;

    /**
     * @var string
     */
    protected $baseDir;

    /**
     * @var string
     */
    protected $csv;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory
     */
    protected $orderAddressFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemFactory
     */
    protected $orderItemFactory;

    /**
     * @var string
     */
    protected $connection;

    /**
     * @var mixed
     */
    protected $listOrderId = [];

    /**
     * @var mixed
     */
    protected $listOrderAddress = [];

    /**
     * @var \Riki\Customer\Helper\CustomerHelper
     */
    protected $customerHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper
     */
    protected $bundleItemsHelper;

    /*store customer consumer db id*/
    protected $_consumerDb = [];
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;
    /**
     * Export constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Helper\CvsOrderPayment $salesOrderHelper
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Riki\ThirdPartyImportExport\Logger\CvsOrderPayment\LoggerCSV $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param File $file
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory $orderAddressFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Riki\Customer\Helper\CustomerHelper $customerHelper
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\CvsOrderPayment $salesOrderHelper,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Riki\ThirdPartyImportExport\Logger\CvsOrderPayment\LoggerCSV $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory $orderAddressFactory,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Riki\Customer\Helper\CustomerHelper $customerHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Magento\Framework\App\State $state
    ) {
        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
        $this->salesOrderHelper = $salesOrderHelper;
        $this->directoryList = $directoryList;
        $this->log = $logger;
        $this->log->setTimezone(new \DateTimeZone($timeZone->getConfigTimezone()));
        $this->dateTime = $dateTime;
        $this->timeZone = $timeZone;
        $this->fileSystem = $fileSystem;
        $this->file = $file;
        $this->baseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->orderAddressFactory = $orderAddressFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->connection = $resourceConnection->getConnection('sales');
        $this->customerHelper = $customerHelper;
        $this->bundleItemsHelper = $bundleItemsHelper;
        $this->appState = $state;
    }

    private function initExport()
    {
        $this->timeLastRunCron = $this->salesOrderHelper->getLastRunToCron();
        $baseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $localCsv = $this->salesOrderHelper->getLocalPathExport();

        if (!$localCsv) {
            $createFileLocal[] = $baseDir.DS.self::DEFAULT_LOCAL_SAVE;
            $createFileLocal[] = $this->pathTmp = $baseDir.DS.self::DEFAULT_LOCAL_SAVE.'_tmp';
            $this->path = self::DEFAULT_LOCAL_SAVE;
            $this->pathTmp = self::DEFAULT_LOCAL_SAVE.'_tmp';
        } else {
            if (trim($localCsv, -1) == DS) {
                $localCsv = str_replace(DS, '', $localCsv);
            }
            $createFileLocal[] = $baseDir.DS.$localCsv;
            $createFileLocal[] = $baseDir.DS.$localCsv.'_tmp';
            $this->path = $localCsv;
            $this->pathTmp = $localCsv.'_tmp';
        }

        // Delete file log exits before to write new log file
        $this->csv = new \Magento\Framework\File\Csv(new File());
        $this->salesOrderHelper->backupLog('cvs_order_payment', $this->log);

        foreach($createFileLocal as $path) {
            if (!$this->file->isDirectory($path)) {
                if (!$this->file->createDirectory($path)) {
                    $this->log->info(__('Can not create dir file').$path);
                    return;
                }
            }
            if (!$this->file->isWritable($path)) {
                $this->log->info(__('The folder have to change permission to 755').$path);
                return ;
            }
        }
    }

    /**
     * execute the main function
     */
    public function execute()
    {
        $this->appState->emulateAreaCode(
            \Magento\Framework\App\Area::AREA_FRONTEND,
            [$this, 'doExecute']
        );
    }
    /**
     * @return bool|void
     */
    public function doExecute()
    {
        if (!$this->salesOrderHelper->isEnable()) {
            return false;
        }

        $this->initExport();
        $this->exportProductHeader();
        $this->exportProductDetail();

        // Update last time to run cron job
        $pathFtp = $this->salesOrderHelper->getSFTPPathExport();
        $pathReportFtp = $this->salesOrderHelper->getReportPathExport();
        $this->salesOrderHelper->MoveFileToFtp('cvs_order_payment', $this->pathTmp, $this->path, $pathFtp, $this->log, $pathReportFtp);

        $this->salesOrderHelper->setLastRunToCron($this->salesOrderHelper->getTimeByUtc());
        $this->salesOrderHelper->sentMail('cvs_order_payment', $this->log);
    }

    /**
     * Export: XRXT1006_H_yyyyMMddhhmmss.csv
     */
    public function exportProductHeader()
    {
        // Header file csv export
        $arrColumnSalesOrder = $this->getAllColumnsName('sales_order');
        $arrColumnTmpSaleOrder = [];
        if (count($arrColumnSalesOrder) > 0) {
            foreach ($arrColumnSalesOrder as $showColumnOrder) {
                $arrColumnTmpSaleOrder[] = 'order.'.$showColumnOrder;
            }
        }

        $arrColumnSalesOrderAddress = $this->getAllColumnsName('sales_order_address');
        $arrColumnTmp = [];
        if (count($arrColumnSalesOrderAddress) > 0) {
            foreach ($arrColumnSalesOrderAddress as $showColumn) {
                $arrColumnTmp[] = 'order.billing_address_'.$showColumn;
            }
        }

        $arrColumnTmp[] = 'order.customer_consumer_db_id';

        $arrayExportHeader[] = array_merge($arrColumnTmpSaleOrder, $arrColumnTmp);

        $this->listOrderId();
        $arrayExport = array_merge($arrayExportHeader, $this->listDataExport());

        /*export date - base on config timezone */
        $exportDate = $this->timeZone->date()->format('YmdHis');
        /*export file name*/
        $nameCsv = 'XRXT1006_H_'.$exportDate.'.csv';

        // Create new file csv
        if (!$this->file->isExists($this->baseDir.DS.$this->pathTmp.DS.$nameCsv)) {
            $this->csv->saveData($this->baseDir.DS.$this->pathTmp.DS.$nameCsv, $arrayExport);
        }
    }

    /**
     * @return array|bool
     * List array SalesOrderId
     */
    public function listOrderId()
    {
        $select = $this->connection->select();
        $select->from('sales_order')
            ->joinLeft(['sp' => 'sales_order_payment'], 'sp.parent_id = sales_order.entity_id', [])
            ->where('sp.method = ?', self::CVS_ORDER_PAYMENT)
            ->where('sales_order.entity_id is not null')
            ->where('sales_order.status = ?', 'pending_cvs_payment')
            ->where('flag_cvs = ?', 0);

        $result = $this->connection->fetchAssoc($select);
        $countItem = count($result);
        if (!empty($countItem) && $countItem > 0)
            return $this->listOrderId = $this->connection->fetchAssoc($select);
        else
            return false;
    }

    /**
     * @return array|bool
     * Get all data to Export
     */
    public function listDataExport()
    {
        $listOrderId[] = array_keys($this->listOrderId);
        $result = [];
        if (count($this->listOrderId) > 0) {
            $select = $this->connection->select();
            $select->from('sales_order_address')
                ->where('parent_id in (?)', $listOrderId)
                ->where('address_type = ?', 'billing');
            $resultAll = $this->connection->fetchAssoc($select);
            if (!empty($resultAll)) {
                foreach ($resultAll as $item) {
                    $parentId = $item['parent_id'];
                    $customerId = $this->listOrderId[$parentId]['customer_id'];
                    $consumerDb = $this->getConsumerIdByCustomerId($customerId);
                    $item[] = $consumerDb;
                    $arrSaleOrder = $this->listOrderId[$parentId];
                    $result[] = array_merge(
                        array_values(
                            $this->convertDateTimeColumnsToConfigTimezone(self::ORDERTYPE, $arrSaleOrder)
                        ),
                        array_values($item)
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Export: XRXT1006_D_yyyyMMddhhmmss.csv
     */
    public function exportProductDetail()
    {
        /*data will be export*/
        $arrayExport = [];

        /*detail header columns*/
        $headerColumns = [];

        /*get all columns from sales_order_item table*/
        $arrColumnSalesOrderItem = $this->getAllColumnsName('sales_order_item');

        if ($arrColumnSalesOrderItem) {
            foreach ($arrColumnSalesOrderItem as $showColumnItem) {
                /*push prefix for all sales_order_item columns and add to header*/
                $headerColumns[] = 'product.'.$showColumnItem;
            }
        }

        /*get all columns from sales_order table*/
        $arrColumnSalesOrder = $this->getAllColumnsName('sales_order');

        /*alias column for sales_order*/
        $aliasColumn = [];

        if ($arrColumnSalesOrder) {
            foreach ($arrColumnSalesOrder as $showColumnOrder) {
                /*push prefix for all sales_order_item columns and add to header*/
                $headerColumns[] = 'product.order_'.$showColumnOrder;

                /*generate an alias column name for each sales_order column*/
                $aliasColumn['order.'.$showColumnOrder] = 'order.'.$showColumnOrder;
            }
        }

        /*push header columns to export data*/
        array_push($arrayExport, $headerColumns);

        $collection = $this->orderItemFactory->create();
        $collection->getSelect()->joinLeft(
            ['order' => 'sales_order'],
            'order.entity_id = main_table.order_id',
            $aliasColumn
        );

        $collection->getSelect()
            ->joinLeft(['sales_order_pay' => 'sales_order_payment'], 'sales_order_pay.parent_id = order.entity_id', ['sales_order_pay.method']);

        $collection->addFieldToFilter('order.flag_cvs', 0);
        $collection->addFieldToFilter('order.status', 'pending_cvs_payment');
        $collection->addFieldToFilter('sales_order_pay.method', self::CVS_ORDER_PAYMENT);

        if ($collection->getSize()) {

            foreach ($collection->getItems() as $key => $showItem) {

                /*array of order item data will be export*/
                $arrayDataOrderItem = array();

                foreach ($arrColumnSalesOrderItem as $columnOrderItem) {
                    if($columnOrderItem == 'product_options') {
                        $arrayDataOrderItem[$columnOrderItem] = Serializer::serialize($showItem->getData($columnOrderItem));
                    } else {
                        $arrayDataOrderItem[$columnOrderItem] = $showItem->getData($columnOrderItem);
                    }
                }

                /*convert sale order item datetime columns to config timezone*/
                $arrayDataOrderItem = $this->convertDateTimeColumnsToConfigTimezone(self::ORDERITEMTYPE,$arrayDataOrderItem);

                /*re calculate data for bundle children item*/
                $arrayDataOrderItem = $this->bundleItemsHelper->reCalculateOrderItem($arrayDataOrderItem);

                $arrayDataOrder = array();

                foreach ($arrColumnSalesOrder as $columnOrder) {
                    $arrayDataOrder['order.'.$columnOrder] = $showItem->getData('order.'.$columnOrder);
                }

                /*convert sales order datetime columns to config timezone*/
                $arrayDataOrder = $this->convertDateTimeColumnsToConfigTimezone(self::ORDERTYPE,$arrayDataOrder);

                /*export data*/
                $arrayExport[] = array_merge($arrayDataOrderItem,$arrayDataOrder);

                /*Update flag cvs payment export - to make sure do not export it again*/
                $this->updateFlagCvsPaymentExport($showItem->getData('order.entity_id'));
            }
        }

        /*export date - based on config timezone*/
        $exportDate = $this->timeZone->date()->format('YmdHis');

        /*export file name*/
        $nameCsv = 'XRXT1006_D_'.$exportDate.'.csv';

        // Create new file csv
        if (!$this->file->isExists($this->baseDir.DS.$this->pathTmp.DS.$nameCsv)) {
            $this->csv->saveData($this->baseDir.DS.$this->pathTmp.DS.$nameCsv, $arrayExport);
        }
    }

    /**
     * @param $entityId
     *
     * Update flag cvs payment export
     */
    public function updateFlagCvsPaymentExport($entityId)
    {
        if (!empty($entityId)) {
            $table = 'sales_order';
            $this->connection->update(
                $table,
                ['flag_cvs' => 1],
                ['entity_id = ?' => $entityId]
            );
        }
    }

    /**
     * @param string $table
     * @return array
     * Get all column in table database
     */
    public function getAllColumnsName($table)
    {
        if (!$table) {
            return;
        }
        $describe = $this->connection->describeTable($table);
        return array_keys($describe);
    }

    /**
     * Get consumer id by customer id
     *
     * @param $customerId
     * @return string
     */
    public function getConsumerIdByCustomerId($customerId)
    {
        if (isset($this->_consumerDb[$customerId])) {
            return $this->_consumerDb[$customerId];
        }

        $consumerDb = $this->customerHelper->getConsumerIdByCustomerId($customerId);

        if (!$consumerDb) {
            $consumerDb = '';
        }

        $this->_consumerDb[$customerId] = $consumerDb;

        return $consumerDb;
    }

    /**
     * Convert datetime columns to config timezone for order/order_item object
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
        }

        if ($dateTimeColumns) {
            foreach ($dateTimeColumns as $cl) {
                if (!empty($object[$cl])) {
                    $object[$cl] = $this->dateTime->date('Y-m-d H:i:s', $this->timeZone->formatDateTime($object[$cl], 2, 2));
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
            'order.created_at', 'order.updated_at', 'order.customer_dob', 'order.csv_start_date'
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
}