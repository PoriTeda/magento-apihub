<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use \Riki\ThirdPartyImportExport\Helper\RedisErrorEntity;

class ShipmentHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    const CONVERTSALESSHIPMENT = 1;
    const CONVERTSALESSHIPMENTTRACK = 2;
    const CONVERTSALESORDERITEM = 3;
    const LOG_START = 'start';
    const LOG_END = 'end';
    const PROFILER_CONFIG = 'di_data_export_setup/profiler/enable';
    const CONFIG_LIMIT = 'di_data_export_setup/data_cron_shipment/limit';
    const DEFAULT_LIMIT = 20000;
    const MAX_ARRAY_SIZE = 2000;
    const CONFIG_DEFAULT_LIMIT = 2000;
    const EXPORT_STATUS_WAITING = 0;
    const EXPORT_STATUS_SUCCESS = 1;
    const EXPORT_STATUS_BLOCK = 2;
    const WAREHOUSE_BIZEX = 'BIZEX';
    const WAREHOUSE_TOYO = 'TOYO';
    const WAREHOUSE_HITACHI = 'HITACHI';

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $taxCalculation;
    /**
     * @var \Riki\Shipment\Model\Status\Shipment
     */
    protected $statusHistory;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper
     */
    protected $bundleItemsHelper;
    /**
     * Default connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;
    /**
     * Sales connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connectionSales;

    /*list columns which data type is datetime or timestamp, table sales_shipment*/
    protected $shipmentDateTimecolumns;
    /*list columns which data type is datetime or timestamp, table sales_shipment_track*/
    protected $shipmentTrackDateTimecolumns;
    /*list columns which data type is datetime or timestamp, table sales_order_item*/
    protected $orderItemDateTimecolumns;
    /**
     *  Column sales_shipment_track
     */
    protected $shipmentTrackColumns;

    /**
     *  Column sales_order_address
     */
    protected $shipmentAddressColumns;

    protected $logTime = [];
    protected $currentTime = [];

    protected $shipmentIds = [];
    protected $deliveryInfo = [];

    protected $consumerDbId = [];
    protected $getConsumerData = false;
    protected $consumerData = [];
    protected $consumerCount = 0;
    protected $totalConsumer = 0;

    protected $shipmentDate = [];
    protected $getShipmentDateData = false;
    protected $shipmentDateData = [];
    protected $shipmentDateCount = 0;
    protected $totalShipmentDate = 0;

    protected $headerData = [];
    protected $detailData = [];

    /*array of entity id which is exported*/
    protected $exportEntityList = [];

    protected $originalHeaderData = [];

    protected $originalDetailData = [];

    /**
     * ShipmentHelper constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Riki\Shipment\Model\Status\Shipment $statusHistory
     * @param GlobalHelper\SftpExportHelper $sftpHelper
     * @param GlobalHelper\FileExportHelper $fileHelper
     * @param GlobalHelper\ConfigExportHelper $configHelper
     * @param GlobalHelper\EmailExportHelper $emailHelper
     * @param GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper
     * @param GlobalHelper\BundleItemsHelper $bundleItemsHelper
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Riki\Shipment\Model\Status\Shipment $statusHistory,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        parent::__construct(
            $context,
            $dateTime,
            $timezone,
            $resourceConfig,
            $sftpHelper,
            $fileHelper,
            $configHelper,
            $emailHelper,
            $dateTimeColumnsHelper,
            $connectionHelper
        );
        $this->taxCalculation = $taxCalculation;
        $this->statusHistory = $statusHistory;
        $this->orderRepository = $orderRepository;
        $this->bundleItemsHelper = $bundleItemsHelper;
        $this->connection = $connectionHelper->getDefaultConnection();
        $this->connectionSales = $connectionHelper->getSalesConnection();
    }

    /**
     * Get lock file
     *      this lock is used to tracking that system have same process is running
     *
     * @return string
     */
    public function getLockFile()
    {
        return $this->_path . DS . '.lock';
    }

    /**
     * @param $defaultLocalPath
     * @param $configLocalPath
     * @param $configSftpPath
     * @param $configReportPath
     * @param $configLastTimeRun
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initExport(
        $defaultLocalPath,
        $configLocalPath,
        $configSftpPath,
        $configReportPath,
        $configLastTimeRun
    ) {
        $initExport = parent::initExport(
            $defaultLocalPath,
            $configLocalPath,
            $configSftpPath,
            $configReportPath,
            $configLastTimeRun
        );

        // TODO: Change the autogenerated stub
        if ($initExport) {
            /*tmp file to ensure that system do not run same multiple process at the same time*/
            $lockFile = $this->getLockFile();
            if ($this->_fileHelper->isExists($lockFile)) {
                $this->_logger->info('Please wait, system have a same process is running and haven’t finish yet.');
                $message = __('Please wait, system have a same process is running and haven’t finish yet.');
                throw new \Magento\Framework\Exception\LocalizedException($message);
            } else {
                $this->_fileHelper->createFile($lockFile);
            }
        }

        return $initExport;
    }

    /**
     * Export process
     */
    public function exportProcess()
    {

        $this->writeLogTracking("Start tracking: Export Shipment BI");

        $this->export();

        /* set last time to run */
        $this->setLastRunToCron();

        /*move export folder to ftp*/
        $this->_sftpHelper->moveFileToFtp(
            $this->_pathTmp,
            $this->_path,
            $this->getSFTPPathExport(),
            $this->getReportPathExport()
        );
        /*send email notify*/
        $this->sentNotificationEmail();

        /*delete all records which is exported*/
        $this->deleteExportedSuccessRecord();

        /*delete lock file*/
        $this->deleteLockFile();
    }

    /**
     * Prepare all column for header file
     *
     * @param array $aAdditionColumns
     *
     * @return array
     */
    public function getShipmentExportColumns($aAdditionColumns = [])
    {
        $aColumns = [];

        $aColumnSaleShipments = $this->connectionSales->describeTable(
            $this->connectionSales->getTableName('sales_shipment')
        );

        foreach ($aColumnSaleShipments as $sColumnSaleShipment => $value) {
            $aColumns[] = 'shipment.' . $sColumnSaleShipment;
        }

        $aColumnSaleShipmentTrack = $this->connectionSales->describeTable(
            $this->connectionSales->getTableName('sales_shipment_track')
        );

        $this->shipmentTrackColumns = array_keys($aColumnSaleShipmentTrack);

        foreach ($aColumnSaleShipmentTrack as $aTrack => $value) {
            $aColumns[] = 'shipment.shipment_track_' . $aTrack;
        }

        $aColumnSaleOrderAddresses = $this->connectionSales->describeTable(
            $this->connectionSales->getTableName('sales_order_address')
        );

        $this->shipmentAddressColumns = array_keys($aColumnSaleOrderAddresses);

        foreach ($aColumnSaleOrderAddresses as $sColumnSaleOrderAddress => $value) {
            $aColumns[] = 'shipment.shipping_address_' . $sColumnSaleOrderAddress;
        }

        $aColumns = array_merge($aColumns, $this->getShipmentCustomColumn());

        $aColumns = array_merge($aColumns, $aAdditionColumns);

        return $aColumns;
    }

    /**
     * Column custom
     *
     * @return array
     */
    public function getShipmentCustomColumn()
    {
        return [
            'shipment.shipping_tax_rate',
            'shipment.time_slot_start',
            'shipment.time_slot_end',
            'shipment.warehouse_export_date',
            'shipment.customer_consumer_db_id',
            'shipment.order_increment_id',
            'shipment.order_shosha_business_code'
        ];
    }

    /**
     * export main process
     *
     * @return bool
     */
    public function export()
    {
        /*shipment data will be exported*/
        $shipmentExport = [];

        /*shipment detail data will be exported*/
        $shipmentItems = [];

        //prepare column customer for order
        $aColumnCustomers = [];

        /*list of shipment id will be export*/
        $this->exportEntityList = $this->getExportShipmentList();

        if (!empty($this->exportEntityList)) {
            /*block order to ensure that do not have any process handle it again*/
            $this->blockShipmentForExportProcess();

            /*shipment header columns*/
            $aColumns = $this->getShipmentExportColumns($aColumnCustomers);

            /*get shipment detail header columns*/
            $aColumnsDetail = $this->getShipmentDetailExportColumns([]);

            $count = 0;

            /*size of header data*/
            $sizeOfHeaderData = 0;

            /*flag to check shipment data is contain header data*/
            $pushHeader = false;

            $shipmentTable = $this->connectionSales->getTableName('sales_shipment');

            foreach ($this->exportEntityList as $shipmentId) {

                try {
                    if ($pushHeader == false) {
                        $pushHeader = true;

                        /*push shipment header columns to export data*/
                        array_push($shipmentExport, $aColumns);

                        /*push shipment detail header columns to shipment detail data*/
                        array_push($shipmentItems, $aColumnsDetail);
                    }

                    $this->originalHeaderData = $shipmentExport;

                    $this->originalDetailData = $shipmentItems;

                    $count++;

                    $sizeOfHeaderData++;

                    $this->resetLogTime();

                    $time = microtime(true);

                    $getShipmentQuery = $sqlShipments = $this->connectionSales->select()->from(
                        $shipmentTable
                    )->where(
                        $shipmentTable . '.entity_id = ?',
                        $shipmentId
                    );

                    $shipmentData = $this->connectionSales->fetchRow($getShipmentQuery);

                    if (empty($shipmentData)) {
                        continue;
                    }

                    /*shipment track data*/
                    $shipmentTrackData = $this->getShipmentTrackData(
                        $shipmentData['entity_id'],
                        $shipmentData['warehouse']
                    );

                    /*merge to shipment data*/
                    $shipmentData = array_merge(
                        $this->convertDateTimeColumnsToConfigTimezone(self::CONVERTSALESSHIPMENT, $shipmentData),
                        $shipmentTrackData
                    );

                    /*order address data*/
                    $orderAddressData = $this->getOrderAddressData($shipmentData['shipping_address_id']);

                    /*merge to shipment data*/
                    $shipmentData = array_merge($shipmentData, $orderAddressData);

                    /*additional columns*/
                    $shipmentData = $this->getShipmentCustomField($shipmentData);

                    /*push shipment data to shipment exported array*/
                    array_push($shipmentExport, $shipmentData);

                    /*shipment detail data*/
                    $shipmentDetail = $this->getShipmentDetailData($shipmentData);

                    /*push shipment detail to shipment detail exported array*/
                    $shipmentItems = array_merge($shipmentItems, $shipmentDetail);

                    /*push tmp data to main data*/
                    if ($sizeOfHeaderData == self::MAX_ARRAY_SIZE) {
                        $sizeOfHeaderData = 0;

                        $this->headerData = array_merge($this->headerData, $shipmentExport);
                        $this->detailData = array_merge($this->detailData, $shipmentItems);

                        $shipmentExport = [];
                        $shipmentItems = [];

                        // Workaround: wake up the default connection frequently
                        $this->connection->fetchOne('select 1 as "wake_up_please";');
                    }

                    $totalTime = microtime(true) - $time;

                    $timeDetail = $this->getLogTime($totalTime);

                    if (!empty($timeDetail)) {
                        foreach ($timeDetail as $keyLog => $spendTimeItem) {
                            $this->writeLogTracking(str_replace('_', ' ', $keyLog) . " : " . $spendTimeItem);
                        }
                    }

                    $this->writeLogTracking("Total time :" . $totalTime);

                    $this->writeLogTracking('Shipment entity id: ' . $shipmentData['entity_id']);

                    $this->writeLogTracking('Total record: ' . $count);
                } catch (\Exception $exception) {
                    $this->_logger->critical($exception);
                    $shipmentExport = $this->originalHeaderData;
                    $shipmentItems = $this->originalDetailData;
                    $this->rePutShipmentToQueue($shipmentId);
                }
            }
        }

        if (!empty($shipmentExport)) {
            $this->headerData = array_merge($this->headerData, $shipmentExport);
        }

        if (!empty($shipmentItems)) {
            $this->detailData = array_merge($this->detailData, $shipmentItems);
        }

        if (!empty($this->headerData)) {
            $this->createExportFile();
        }

        /*update is bi exported flag for exported records*/
        $this->updateAfterExportSuccess();

        return true;
    }

    /**
     * create local file
     */
    public function createExportFile()
    {
        /*get export date via config timezone*/
        $exportDate = $this->_timezone->date()->format('YmdHis');

        /*shipment header file name*/
        $shipmentHeaderFileName = 'shipmentheader-' . $exportDate . '.csv';

        /*export order header*/
        $this->createLocalFile([
            $shipmentHeaderFileName => $this->headerData
        ]);

        $this->headerData = [];

        /*export shipment detail*/
        if ($this->detailData) {
            $nameShipmentDetails = 'shipmentdetail-' . $exportDate . '.csv';

            $this->createLocalFile([
                $nameShipmentDetails => $this->detailData
            ]);

            $this->detailData = [];
        }
    }

    /**
     * Get shipment track data
     *
     * @param $shipmentId
     * @param  $warehouse
     * @return array
     */
    public function getShipmentTrackData($shipmentId, $warehouse)
    {
        $shipmentTrackData = [];

        $trackData = $this->getShipmentTrackDataForWarehouse($shipmentId, $warehouse);

        if ($trackData) {
            /*convert sale shipment track date time columns to config timezone*/
            $trackData = $this->convertDateTimeColumnsToConfigTimezone(self::CONVERTSALESSHIPMENTTRACK, $trackData);
            foreach ($trackData as $sColumn => $sValue) {
                $key = 'shipment.shipment_track_' . $sColumn;
                $shipmentTrackData[$key] = $sValue;
            }
        } else {
            foreach ($this->shipmentTrackColumns as $sColumn) {
                $sColumn = 'shipment.shipment_track_' . $sColumn;
                $shipmentTrackData[$sColumn] = '';
            }
        }

        return $shipmentTrackData;
    }

    /**
     * Get shipment track data based on warehouse information
     *
     * @param $shipmentId
     * @param $warehouse
     * @return array
     */
    public function getShipmentTrackDataForWarehouse($shipmentId, $warehouse)
    {
        $trackData = [];

        /**
         * All Warehouse, Delivery company = Sagawa ： export only biggest tracking # to SSRS
         */
        $trackData = $this->getShipmentTrackDataByCarrierCode(
            $shipmentId,
            \Riki\ShippingCarrier\Model\Carrier\Sagawa::CARRIER_CODE,
            \Magento\Framework\Api\SortOrder::SORT_DESC
        );

        if (!$trackData) {
            if ($warehouse == self::WAREHOUSE_BIZEX) {
                $carrierCode = \Riki\ShippingCarrier\Model\Carrier\Bizex::CARRIER_CODE;
                $sortOrder = \Magento\Framework\Api\SortOrder::SORT_ASC;
                $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);

                if (!$trackData) {
                    $carrierCode = \Riki\ShippingCarrier\Model\Carrier\Yupack::CARRIER_CODE;
                    $sortOrder = \Magento\Framework\Api\SortOrder::SORT_DESC;
                    $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);
                }

                /**
                 * Warehouse = BIZEX, Delivery company = yamato ： export only biggest tracking # in SSRS
                 */
                if (!$trackData) {
                    $carrierCode = \Riki\ShippingCarrier\Model\Carrier\YamatoAskul::CARRIER_CODE;
                    $sortOrder = \Magento\Framework\Api\SortOrder::SORT_DESC;
                    $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);
                }
            } elseif ($warehouse == self::WAREHOUSE_TOYO) {
                $sortOrder = \Magento\Framework\Api\SortOrder::SORT_DESC;
                $carrierCode = \Riki\ShippingCarrier\Model\Carrier\Kinki::CARRIER_CODE;
                $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);

                if (!$trackData) {
                    $carrierCode = \Riki\ShippingCarrier\Model\Carrier\Tokai::CARRIER_CODE;
                    $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);
                }

                /**
                 * Warehouse = TOYO, Delivery company = yamato ： export only biggest tracking # in SSRS
                 */
                if (!$trackData) {
                    $carrierCode = \Riki\ShippingCarrier\Model\Carrier\YamatoAskul::CARRIER_CODE;
                    $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);
                }
            }
        } elseif ($warehouse == self::WAREHOUSE_HITACHI) {
            /**
             * Warehouse = HITACHI, Delivery company = Sagawa ： export only biggest tracking # to SSRS
             */
            $sortOrder = \Magento\Framework\Api\SortOrder::SORT_DESC;
            $carrierCode = \Riki\ShippingCarrier\Model\Carrier\Sagawa::CARRIER_CODE;
            $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);
        }

        if (!$trackData) {
            $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, false, false);
        }

        return $trackData;
    }

    /**
     * Get shipment track data by carrier code
     *
     * @param $shipmentId
     * @param bool $carrierCode
     * @param bool $sortOrder
     * @return array
     */
    public function getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode = false, $sortOrder = false)
    {
        /*shipment track table*/
        $trackTable = $this->connectionSales->getTableName('sales_shipment_track');

        $getShipmentTrack = $this->connectionSales->select()->from($trackTable);
        $getShipmentTrack->where('sales_shipment_track.parent_id = ?', $shipmentId);

        if ($carrierCode) {
            $getShipmentTrack->where('sales_shipment_track.carrier_code = ?', $carrierCode);
            if ($sortOrder) {
                $getShipmentTrack->where(
                    "CONCAT('',sales_shipment_track.track_number * 1) = sales_shipment_track.track_number"
                );
                $getShipmentTrack->order(new \Zend_Db_Expr("(sales_shipment_track.track_number * 1) $sortOrder"));
                $getShipmentTrack->order(new \Zend_Db_Expr("(sales_shipment_track.track_number) $sortOrder"));
            }
        } else {
            $getShipmentTrack->order('sales_shipment_track.entity_id DESC');
        }

        $getShipmentTrack->limit(1);

        $trackData = $this->connectionSales->fetchRow($getShipmentTrack);

        return $trackData;
    }

    /**
     * Get order address
     *
     * @param $addressId
     * @return array
     */
    public function getOrderAddressData($addressId)
    {
        $orderAddressData = [];

        $orderAddressTbl = $this->connectionSales->getTableName('sales_order_address');

        $sqlOrderAddress = $this->connectionSales->select()->from(
            $orderAddressTbl
        )->where(
            $orderAddressTbl . '.entity_id = ?',
            $addressId
        );

        $queryOrderAddress = $this->connectionSales->query($sqlOrderAddress);

        while ($address = $queryOrderAddress->fetch()) {
            foreach ($address as $sColumn => $sValue) {
                $key = 'shipment.shipping_address_' . $sColumn;
                $orderAddressData[$key] = $sValue;
            }
        }

        if (empty($orderAddressData)) {
            foreach ($this->shipmentAddressColumns as $sColumn) {
                $sColumn = 'shipment.shipping_address_' . $sColumn;
                $orderAddressData[$sColumn] = '';
            }
        }

        return $orderAddressData;
    }

    /**
     * Custom field
     *
     * @param $shipmentData
     *
     * @return mixed
     */
    public function getShipmentCustomField($shipmentData)
    {
        //shipping_tax_rate
        $shipmentData['shipment.shipping_tax_rate'] = $this->getShippingTaxRate();

        $deliveryInfo = $this->getDeliveryInfo($shipmentData['order_id']);

        //time_slot_start
        $timeSlotStart = isset($deliveryInfo['delivery_timeslot_from']) ? $deliveryInfo['delivery_timeslot_from'] : '';
        $shipmentData['shipment.time_slot_start'] = $timeSlotStart;

        //time_slot_end
        $timeSlotEnd = isset($deliveryInfo['delivery_timeslot_to']) ? $deliveryInfo['delivery_timeslot_to'] : '';
        $shipmentData['shipment.time_slot_end'] = $timeSlotEnd;

        //export_date
        $shipmentData['shipment.warehouse_export_date'] = $this->getShipmentExportedDate($shipmentData['entity_id']);

        // get customer Db
        $shipmentData['shipment.customer_consumer_db_id'] = $this->getConsumerDbId($shipmentData['customer_id']);

        //get order increment id, shosha business code
        $orderAdditionalData = $this->getOrderAdditionalById($shipmentData['order_id']);

        /*order increment id*/
        $orderIncrementId = '';
        if (!empty($orderAdditionalData) && $orderAdditionalData['increment_id']) {
            $orderIncrementId = $orderAdditionalData['increment_id'];
        }
        $shipmentData['shipment.order_increment_id'] = $orderIncrementId;

        /*order shosha business code*/
        $orderShoshaBusinessCode = '';
        if (!empty($orderAdditionalData) && $orderAdditionalData['shosha_business_code']) {
            $orderShoshaBusinessCode = $orderAdditionalData['shosha_business_code'];
        }
        $shipmentData['shipment.order_shosha_business_code'] = $orderShoshaBusinessCode;

        return $shipmentData;
    }

    /**
     * Get additional order data for export - increment id, shosah_business_code
     *
     * @param $orderId
     * @return string
     */
    public function getOrderAdditionalById($orderId)
    {
        if (!$orderId) {
            return '';
        }
        $select = $this->connectionSales->select()
            ->from($this->connectionSales->getTableName('sales_order'), ['increment_id', 'shosha_business_code'])
            ->where('entity_id = ?', $orderId);
        return $this->connectionSales->fetchRow($select);
    }

    public function getIncrementOrderById($orderId)
    {
        if (!$orderId) {
            return '';
        }
        $select = $this->connectionSales->select()
            ->from($this->connectionSales->getTableName('sales_order'), 'increment_id')
            ->where('entity_id = ?', $orderId);
        return $this->connectionSales->fetchOne($select);
    }

    /**
     * Prepare data shipment detail
     *
     * @param $shipment
     *
     * @return array
     */
    public function getShipmentDetailData($shipment)
    {
        /*sales shipment item table*/
        $shipmentItemTbl = $this->connectionSales->getTableName('sales_shipment_item');

        $sqlShipmentItem = $this->connectionSales->select()->from(
            $shipmentItemTbl
        )->where(
            $shipmentItemTbl . '.parent_id = ?',
            $shipment['entity_id']
        );

        $queryShipmentItem = $this->connectionSales->query($sqlShipmentItem);

        $resultData = [];

        while ($shipmentItemData = $queryShipmentItem->fetch()) {
            $arrayType = [];

            /*sales order item table*/
            $orderItemTbl = $this->connectionSales->getTableName('sales_order_item');

            $sqlOrderItem = $this->connectionSales->select()->from(
                $orderItemTbl
            )->where(
                $orderItemTbl . '.item_id = ?',
                $shipmentItemData['order_item_id']
            );

            $queryOrderItem = $this->connectionSales->query($sqlOrderItem);

            $orderItem = [];

            while ($orderItemData = $queryOrderItem->fetch()) {
                /*convert sales order item datetime columns to config timezone*/
                $orderItemData = $this->convertDateTimeColumnsToConfigTimezone(
                    self::CONVERTSALESORDERITEM,
                    $orderItemData
                );

                /*re calculate data for bundle children item*/
                $orderItemData = $this->bundleItemsHelper->reCalculateOrderItem($orderItemData);

                // Ensure no more serialize string in product_options
                $productOptions = $this->bundleItemsHelper->convertDetailOptionsToJsonFormat($orderItemData['product_options']);
                $orderItemData['product_options'] = $productOptions;

                foreach ($orderItemData as $sColumn => $sValue) {
                    $key = 'shipment_item.order_item_' . $sColumn;
                    $orderItem[$key] = $sValue;
                }
            }

            /* merge order item */
            $shipmentItemData = array_merge($shipmentItemData, $orderItem);

            /*sales order table*/
            $orderTbl = $this->connectionSales->getTableName('sales_order');

            $sqlOrder = $this->connectionSales->select()->from(
                $orderTbl
            )->where(
                $orderTbl . '.entity_id = ?',
                $shipmentItemData['shipment_item.order_item_order_id']
            );

            $queryOrder = $this->connectionSales->query($sqlOrder);

            $rikiType = [];
            while ($orderData = $queryOrder->fetch()) {
                foreach ($orderData as $sColumn => $sValue) {
                    if ($sColumn == 'riki_type') {
                        $rikiType['shipment_item.order_riki_type'] = $sValue;
                    }
                }
            }
            /*merge riki type*/
            $shipmentItemData = array_merge($shipmentItemData, $rikiType);

            $shipmentItemData['shipment_item.shipment_increment_id'] = $shipment['increment_id'];

            $resultData[] = $shipmentItemData;
        }

        return $resultData;
    }

    /**
     * Prepare column for detail file
     *
     * @param array $aAdditionColumns
     *
     * @return array
     */
    public function getShipmentDetailExportColumns($aAdditionColumns = [])
    {
        $aColumns = [];
        $data = $aAdditionColumns;
        $tableSaleItem = $this->connectionSales->getTableName('sales_shipment_item');

        $aShipmentItemColumns = $this->connectionSales->describeTable($tableSaleItem);

        foreach ($aShipmentItemColumns as $sColumnShipmentItem => $sValue) {
            $aColumns[] = 'shipment_item.' . $sColumnShipmentItem;
        }

        $tableSaleOrderItems = $this->connectionSales->getTableName('sales_order_item');
        $aOrderItemColumns = $this->connectionSales->describeTable($tableSaleOrderItems);

        foreach ($aOrderItemColumns as $sColumnItem => $sValue) {
            $aColumns[] = 'shipment_item.order_item_' . $sColumnItem;
        }

        // riki type column
        $aColumns[] = 'shipment_item.order_riki_type';
        // increment id
        $aColumns[] = 'shipment_item.shipment_increment_id';
        return $aColumns;
    }

    /**
     * get last time which this cron was run
     */
    public function getLastRunToCron()
    {
        $lastTimeCronRun = '';

        try {
            /*config table name*/
            $configTable = $this->connection->getTableName('core_config_data');

            $getLastTimeCronRun = $this->connection->select()->from(
                $configTable,
                'value'
            )->where(
                'path = ?',
                $this->_configLastTimeRun
            )->limitPage(1, 1)->limit(1);

            $timeCronRun = $this->connection->fetchCol($getLastTimeCronRun);

            if (!empty($timeCronRun) && !empty($timeCronRun[0])) {
                $lastTimeCronRun = $timeCronRun[0];
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }

        if (empty($lastTimeCronRun)) {
            $lastTimeCronRun = parent::getLastRunToCron();
        }

        return $lastTimeCronRun;
    }

    /**
     * Get Shipping Tax Rate
     *
     * @return float
     */
    public function getShippingTaxRate()
    {
        $taxClassId = $this->scopeConfig->getValue(
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
            'default'
        );
        $rate = $this->taxCalculation->getCalculatedRate($taxClassId);
        return $rate;
    }

    /**
     * Get shipment exported date by shipment id
     *
     * @param $shipmentId
     * @return string
     */
    public function getShipmentExportedDate($shipmentId)
    {
        if (!isset($this->shipmentDate[$shipmentId])) {
            $this->setShipmentDateByShipmentId($shipmentId);
        }

        if (isset($this->shipmentDate[$shipmentId])) {
            return $this->shipmentDate[$shipmentId];
        } else {
            return '';
        }
    }

    /**
     * set shipment date By shipment Id
     *
     * @param $shipmentId
     */
    public function setShipmentDateByShipmentId($shipmentId)
    {
        $shipmentDateData = $this->getShipmentDateData();

        if (!empty($shipmentDateData)) {
            for ($this->shipmentDateCount;
                 $this->shipmentDateCount < $this->totalShipmentDate;
                 $this->shipmentDateCount++) {
                $shipData = $shipmentDateData[$this->shipmentDateCount];
                $this->shipmentDate[$shipData['shipment_id']] = $shipData['shipment_date'];
                if ($shipData['shipment_id'] == $shipmentId) {
                    break;
                }
            }
        }
    }

    /**
     * Get shipment date data
     *
     * @return array
     */
    public function getShipmentDateData()
    {
        if (!$this->getShipmentDateData) {
            /*shipment history table*/
            $shipmentTbl = $this->connection->getTableName('riki_shipment_shipping_history');

            $getShipmentDateQuery = $this->connection->select()->from(
                $shipmentTbl,
                ['shipment_id', 'shipment_date']
            )->where(
                'shipment_id IN (?)',
                $this->exportEntityList
            )->where(
                'shipment_status = ?',
                "exported"
            )->group('shipment_id');

            $shipmentDateData = $this->connection->fetchAll($getShipmentDateQuery);

            if (!empty($shipmentDateData)) {
                $this->shipmentDateData = $shipmentDateData;
                $this->totalShipmentDate = count($this->shipmentDateData);
            }

            $this->getShipmentDateData = true;
        }

        return $this->shipmentDateData;
    }

    /**
     * Get Delivery info of shipment
     *
     * @param $orderId
     *
     * @return array
     */
    public function getDeliveryInfo($orderId)
    {
        if (!isset($this->deliveryInfo[$orderId])) {
            $this->deliveryInfo[$orderId] = $this->getOrderDeliveryinfo($orderId);
        }
        return $this->deliveryInfo[$orderId];
    }

    public function getOrderDeliveryInfo($orderId)
    {
        $deliveryTimeslotFrom = '';
        $deliveryTimeslotTo = '';

        /*sales order item table*/
        $orderItemTbl = $this->connectionSales->getTableName('sales_order_item');

        $sqlOrderItem = $this->connectionSales->select()->from(
            $orderItemTbl,
            ['delivery_timeslot_from', 'delivery_timeslot_to']
        )->where(
            $orderItemTbl . '.order_id = ?',
            $orderId
        );

        $orderItemData = $this->connectionSales->query($sqlOrderItem);

        while ($orderItem = $orderItemData->fetch()) {
            if (!empty($orderItem['delivery_timeslot_from'])) {
                $deliveryTimeslotFrom = $orderItem['delivery_timeslot_from'];
            }

            if (!empty($orderItem['delivery_timeslot_to'])) {
                $deliveryTimeslotFrom = $orderItem['delivery_timeslot_to'];
            }
        }

        return [
            'delivery_timeslot_from' => $deliveryTimeslotFrom,
            'delivery_timeslot_to' => $deliveryTimeslotTo
        ];
    }

    /**
     * Get Order
     *
     * @param $id
     *
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder($id)
    {
        try {
            $order = $this->orderRepository->get($id);
        } catch (\Exception $e) {
            return false;
        }
        return $order;
    }

    /**
     * @param $customerId
     * @return string
     */
    public function getConsumerDbId($customerId)
    {
        if (!$customerId) {
            return '';
        }

        if (!isset($this->consumerDbId[$customerId])) {
            $this->setConsumerIdbyCustomerId($customerId);
        }

        if (isset($this->consumerDbId[$customerId])) {
            return $this->consumerDbId[$customerId];
        } else {
            return '';
        }
    }

    /**
     * set Consumer Id By Customer Id
     *
     * @param $customerId
     */
    public function setConsumerIdbyCustomerId($customerId)
    {
        $consumerData = $this->getConsumerData();

        if (!empty($consumerData)) {
            for ($this->consumerCount; $this->consumerCount < $this->totalConsumer; $this->consumerCount++) {
                $consData = $consumerData[$this->consumerCount];
                $this->consumerDbId[$consData['entity_id']] = $consData['consumer_db_id'];
                if ($consData['entity_id'] == $customerId) {
                    break;
                }
            }
        }
    }

    /**
     * Get consumer data
     *
     * @return array
     */
    public function getConsumerData()
    {
        if (!$this->getConsumerData) {
            /*shipment table*/
            $shipmentTbl = $this->connectionSales->getTableName('sales_shipment');

            $getCustomerQuery = $this->connectionSales->select()->from(
                $shipmentTbl,
                ['DISTINCT(customer_id)']
            )->where(
                'entity_id IN (?)',
                $this->exportEntityList
            );

            $customerIds = $this->connectionSales->fetchCol($getCustomerQuery);

            if (!empty($customerIds)) {
                $customerEntityTbl = $this->connection->getTableName('customer_entity');

                $select = $this->connection->select()
                    ->from($customerEntityTbl, ['entity_id', 'consumer_db_id'])
                    ->where('entity_id IN (?)', $customerIds);

                $consumerDB = $this->connection->fetchAll($select);

                if (!empty($consumerDB)) {
                    $this->consumerData = $consumerDB;
                    $this->totalConsumer = count($this->consumerData);
                }
            }

            $this->getConsumerData = true;
        }

        return $this->consumerData;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_shipment
     * @return mixed
     */
    public function getShipmentDateTimeColumns()
    {
        if (empty($this->shipmentDateTimecolumns)) {
            $this->shipmentDateTimecolumns = $this->_dateTimeColumnsHelper->getShipmentDateTimeColumns();
        }

        return $this->shipmentDateTimecolumns;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_shipment
     * @return mixed
     */
    public function getShipmentTrackDateTimeColumns()
    {
        if (empty($this->shipmentTrackDateTimecolumns)) {
            $this->shipmentTrackDateTimecolumns = $this->_dateTimeColumnsHelper->getShipmentTrackDateTimeColumns();
        }

        return $this->shipmentTrackDateTimecolumns;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order_item
     * @return mixed
     */
    public function getOrderItemDateTimeColumns()
    {
        if (empty($this->orderItemDateTimecolumns)) {
            $this->orderItemDateTimecolumns = $this->_dateTimeColumnsHelper->getOrderItemDateTimeColumns();
        }

        return $this->orderItemDateTimecolumns;
    }

    /**
     * Convert date time from UTC to config timezone
     *
     * @param $type
     * @param $object {sales_order object, sales_order_item object}
     * @return string
     */
    public function convertDateTimeColumnsToConfigTimezone($type, $object)
    {
        if ($object) {
            /*get datetime columns by type*/
            if ($type == self::CONVERTSALESSHIPMENT) {
                $datetimeColumns = $this->getShipmentDateTimeColumns();
            } elseif ($type == self::CONVERTSALESSHIPMENTTRACK) {
                $datetimeColumns = $this->getShipmentTrackDateTimeColumns();
            } elseif ($type == self::CONVERTSALESORDERITEM) {
                $datetimeColumns = $this->getOrderItemDateTimeColumns();
            }

            foreach ($datetimeColumns as $column) {
                if (!empty($object[$column])) {
                    /*convert datetime from column data to config timezone*/
                    $object[$column] = $this->convertToConfigTimezone($object[$column]);
                }
            }
        }

        return $object;
    }

    /**
     * Set log time
     *
     * @param $keyLog
     * @param $flag
     * @return bool
     */
    public function setLogTime($keyLog, $flag)
    {
        if (!$this->_configHelper->getConfig(self::PROFILER_CONFIG)) {
            return false;
        }

        if ($flag == self::LOG_START) {
            $this->currentTime[$keyLog] = microtime(true);
        } elseif ($flag == self::LOG_END) {
            $spendTime = microtime(true) - $this->currentTime[$keyLog];

            $this->currentTime[$keyLog] = 0;

            if (!isset($this->logTime[$keyLog])) {
                $this->logTime[$keyLog] = $spendTime;
            } else {
                $totalTime = $this->logTime[$keyLog];
                $totalTime += $spendTime;
                $this->logTime[$keyLog] = $totalTime;
            }
        }
    }

    /**
     * Get log time
     *
     * @param int $totalTime
     * @return array
     */
    public function getLogTime($totalTime = 0)
    {
        if (!$this->_configHelper->getConfig(self::PROFILER_CONFIG)) {
            return false;
        }

        $aReturn = [];
        foreach ($this->logTime as $keyLog => $spendTime) {
            if (isset($this->logTime[$keyLog])) {
                $dataLog = ($totalTime > 0 ? (float)$spendTime * 100 / $totalTime : 0);
                $aReturn[$keyLog] = $this->logTime[$keyLog] . '(' . $dataLog . '%)';
            } else {
                $aReturn[$keyLog] = 0;
            }
        }
        return $aReturn;
    }

    /**
     * Reset log time
     */
    public function resetLogTime()
    {
        foreach ($this->logTime as $keyLog => $spendTime) {
            $this->logTime[$keyLog] = 0;
        }
    }

    /**
     * @param $message
     * @return bool
     */
    public function writeLogTracking($message)
    {
        if (!$this->_configHelper->getConfig(self::PROFILER_CONFIG)) {
            return false;
        }

        $this->_logger->info($message);
    }

    /**
     * Get shipment list which will be exported
     *
     * @return array|bool
     */
    public function getExportShipmentList()
    {
        /*get limit record*/
        $limit = $this->getLimit();

        /*version tbl*/
        $versionTbl = $this->connectionSales->getTableName('riki_shipment_version_bi_export');

        $shipmentList = $this->connectionSales->select()->from(
            $versionTbl,
            ['entity_id', new \Zend_Db_Expr('MIN(version_id)')]
        )->where(
            $versionTbl . '.is_bi_exported = ' . self::EXPORT_STATUS_WAITING
        )->group('entity_id')->limit(
            $limit,
            0
        );

        try {
            return $this->connectionSales->fetchCol($shipmentList);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }

        return false;
    }

    /**
     * after export success, change flag is_bi_exported = 1
     */
    public function updateAfterExportSuccess()
    {
        /*update data to ensure that this record is exported to BI*/
        if (!empty($this->exportEntityList)) {
            $table = $this->connectionSales->getTableName('riki_shipment_version_bi_export');
            $bind = ['is_bi_exported' => self::EXPORT_STATUS_SUCCESS];
            $where = [
                'entity_id IN (?)' => $this->exportEntityList,
                'is_bi_exported = ?' => self::EXPORT_STATUS_BLOCK
            ];
            try {
                $this->connectionSales->update($table, $bind, $where);
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }
    }

    /**
     * lock shipment to ensure that do not have any process handle it again
     */
    public function blockShipmentForExportProcess()
    {
        if (!empty($this->exportEntityList)) {
            $table = $this->connectionSales->getTableName('riki_shipment_version_bi_export');
            $bind = ['is_bi_exported' => self::EXPORT_STATUS_BLOCK];
            $where = [
                'entity_id IN (?)' => $this->exportEntityList,
                'is_bi_exported = ?' => 0
            ];
            try {
                $this->connectionSales->update($table, $bind, $where);
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }
    }

    /**
     * delete all records which is exported success
     */
    public function deleteExportedSuccessRecord()
    {
        $table = $this->connectionSales->getTableName('riki_shipment_version_bi_export');
        $where = [
            'is_bi_exported' => self::EXPORT_STATUS_SUCCESS,
            'entity_id IN (?)' => $this->exportEntityList
        ];
        try {
            $this->connectionSales->delete($table, $where);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    /**
     * Delete lock file
     */
    public function deleteLockFile()
    {
        $this->_fileHelper->deleteFile($this->getLockFile());
    }

    /**
     * get total record will be exported per file
     *
     * @return int|mixed
     */
    public function getLimit()
    {
        $limit = $this->_configHelper->getConfig(self::CONFIG_LIMIT);
        if (empty($limit)) {
            $limit = self::DEFAULT_LIMIT;
        }

        return $limit;
    }

    /**
     * Put error shipment into queue
     * @param $entityId
     */
    public function rePutShipmentToQueue($entityId)
    {
        $table = $this->connectionSales->getTableName('riki_shipment_version_bi_export');
        $bind = ['is_bi_exported' => self::EXPORT_STATUS_WAITING];
        $where = [
            'entity_id = ?' => $entityId,
        ];
        try {
            $this->connectionSales->update($table, $bind, $where);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }
}
