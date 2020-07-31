<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

class InvoiceSaleShipmentHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    const ORDERITEMTYPE = 'order_item';
    const SHIPMENTTYPE = 'shipment';
    const SHIPMENTTRACKTYPE = 'shipment_track';
    const WAREHOUSE_BIZEX = 'BIZEX';
    const WAREHOUSE_TOYO = 'TOYO';
    const SHIPMENT_EXCEPTIONAL_TAX_FLAG_COLUMN = 'tax_exceptional_flag';
    const CONFIG_RUN_ONE_TIME_PER_MONTH = 'di_data_export_setup/data_cron_invoice_sale_shipment/one_time_per_month';

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $taxCalculation;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper
     */
    protected $_bundleItemsHelper;
    /**
     * @var \Riki\Shipment\Model\Status\Shipment
     */
    protected $statusHistory;
    /**
     * @var \Riki\Customer\Helper\ShoshaHelper
     */
    protected $_shoshaHelper;
    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $_orderHelper;
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
    protected $_shipmentDateTimecolumns;
    /*list columns which data type is datetime or timestamp, table sales_shipment_track*/
    protected $_shipmentTrackDateTimecolumns;
    /*list columns which data type is datetime or timestamp, table sales_order_item*/
    protected $_orderItemDateTimecolumns;

    /*export date - current date by config timezone*/
    protected $_exportDate;

    /*flag array to store Cedyna customer value */
    protected $cedynaCustomer = [];

    /*flag array to store invoice order value */
    protected $invoicedOrder = [];

    /* list shipment has exported success*/
    protected $successList = [];

    /* list shipment do not need to export*/
    protected $noNeedExportList = [];

    protected $shipmentTrackColumns = [];

    /**
     * @var \Riki\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * InvoiceSaleShipmentHelper constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param GlobalHelper\SftpExportHelper $sftpHelper
     * @param GlobalHelper\FileExportHelper $fileHelper
     * @param GlobalHelper\ConfigExportHelper $configHelper
     * @param GlobalHelper\EmailExportHelper $emailHelper
     * @param GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper
     * @param GlobalHelper\BundleItemsHelper $bundleItemsHelper
     * @param \Riki\Shipment\Model\Status\Shipment $statusHistory
     * @param \Riki\Customer\Helper\ShoshaHelper $shoshaHelper
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     * @param \Riki\Tax\Helper\Data $taxHelper
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Riki\Shipment\Model\Status\Shipment $statusHistory,
        \Riki\Customer\Helper\ShoshaHelper $shoshaHelper,
        \Riki\Sales\Helper\Order $orderHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Riki\Tax\Helper\Data $taxHelper
    ) {
        parent::__construct($context, $dateTime, $timezone, $resourceConfig, $sftpHelper, $fileHelper, $configHelper, $emailHelper, $dateTimeColumnsHelper, $connectionHelper);
        $this->orderRepository = $orderRepository;
        $this->taxCalculation = $taxCalculation;
        $this->_bundleItemsHelper = $bundleItemsHelper;
        $this->statusHistory = $statusHistory;
        $this->_shoshaHelper = $shoshaHelper;
        $this->_orderHelper = $orderHelper;
        $this->connection = $connectionHelper->getDefaultConnection();
        $this->connectionSales = $connectionHelper->getSalesConnection();
        $this->taxHelper = $taxHelper;
    }

    /**
     * @return bool
     */
    public function exportProcess()
    {
        if ($this->canRunExport()) {

            /*export main process*/
            $this->export();

            /* set last time to run */
            $this->setLastRunToCron();

            /*move export folder to ftp*/
            $this->_sftpHelper->moveFileToFtp($this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->getReportPathExport());

            if (!empty($this->successList)) {
                $this->_logger->info('All shipments has already exported to BI.');
            }

            /*send email notify*/
            $this->sentNotificationEmail();
        }
    }

    /**
     * Can run export process?
     *      Monthly export: only export one time per month (yes/no)
     *
     * @return bool
     */
    public function canRunExport()
    {
        /*get config for this cron - only run one time or multi time per month*/
        $runOneTimePerMonth = $this->getRunOneTimePerMonth();

        /*can run export process if config for run one time per month is 0*/
        if (!$runOneTimePerMonth) {
            return true;
        }

        /*last time that this cron is run*/
        $getLastTimeCron = $this->getLastRunToCron();

        /*get year and month from last time that this cron is run*/
        $getMonthCron = $this->_timezone->date(strtotime($getLastTimeCron))->format('Y-m');

        /*can run export process if month from last time to run this cron is difference with current month( for run one time per month case )*/
        if ($getMonthCron != $this->_timezone->date()->format('Y-m')) {
            return true;
        }

        return false;
    }

    /**
     * export main process
     */
    public function export()
    {
        /*set export date*/
        $this->_exportDate = $this->_timezone->date()->format('YmdHis');

        $shipmentExport = [];

        // PREPARE COLUMN CUSTOMER FOR ORDER
        $aColumnCustomers = [];

        $aColumns = $this->getShipmentExportColumns($aColumnCustomers);

        array_push($shipmentExport, $aColumns);

        $shipmentData = $this->getShipmentData($aColumns);

        if ($shipmentData) {
            $i=0;
            foreach ($shipmentData as $shipment) {
                array_push($shipmentExport, $shipment);
                $this->exportShipmentItem($shipment,$i);
                $i++;
                $this->_logger->info('Shipment #'.$shipment['increment_id'].' has ready to export to BI.');
            }
        } else {
            /*create empty detail file*/
            $this->exportShipmentItem([]);
        }

        /*export header file name*/
        $exportHeaderFileName = 'invoiceshipmentheader-'.$this->_exportDate.'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportHeaderFileName => $shipmentExport
        ]);

        /*update flag_export_invoice_sales_shipment column to avoid export again*/
        $this->updateFlagExport();
    }

    /**
     * @param array $aAdditionColumns
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
            $aColumns[] = 'shipment_track.' . $aTrack;
        }

        $aColumnSaleOrderAddresses = $this->connectionSales->describeTable(
            $this->connectionSales->getTableName('sales_order_address')
        );
        foreach ($aColumnSaleOrderAddresses as $sColumnSaleOrderAddress => $value) {
            $aColumns[] = 'shipping_address.' . $sColumnSaleOrderAddress;
        }

        $aColumns = array_merge($aColumns, $this->getShipmentCustomColumn());
        $aColumns = array_merge($aColumns, $aAdditionColumns);
        return $aColumns;
    }

    /**
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
     * @param array $aColumns
     * @return array
     */
    public function getShipmentData($aColumns = [])
    {
        /*shipment table*/
        $shipmentTbl = $this->connectionSales->getTableName('sales_shipment');

        /**
         * Get shipment data to export
         *      customer is Cedyna customer
         *      order is created with invoice payment method
         *          Additional condition: flag_export_invoice_sales_shipment = 0
         */
        $sqlShipments = $this->connectionSales->select()->from($shipmentTbl)->where(
            $shipmentTbl.'.shipment_status IN (?)', [
                \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_SHIPPED_OUT,
                \Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_DELIVERY_COMPLETED
            ]
        )->where(
            $shipmentTbl.'.flag_export_invoice_sales_shipment = ?', 0
        );

        $queryShipments = $this->connectionSales->query($sqlShipments);

        /*array to store export data*/
        $resultData = [];

        while ($shipmentData = $queryShipments->fetch()) {

            $canExportShipment = $this->canExportShipment($shipmentData);

            if ($canExportShipment) {

                $shipmentTrackData = $this->getShipmentTrackData($shipmentData['entity_id'], $shipmentData['warehouse']);

                /*shipment track to shipment export data*/
                $shipmentData = array_merge(
                    $this->convertDateTimeColumnsToConfigTimezone(self::SHIPMENTTYPE,$shipmentData),
                    $shipmentTrackData
                );

                /*get order addres data*/
                $orderAddressTbl = $this->connectionSales->getTableName('sales_order_address');
                $sqlOrderAddress = $this->connectionSales->select()->from($orderAddressTbl)
                    ->where($orderAddressTbl.'.entity_id = ?', $shipmentData['shipping_address_id']);
                $queryOrderAddress = $this->connectionSales->query($sqlOrderAddress);

                /*order address data will be export*/
                $orderAddressData = [];

                while ($address = $queryOrderAddress->fetch()) {
                    foreach ($address as $sColumn => $sValue) {
                        $key = 'shipping_address.' . $sColumn;
                        $orderAddressData[$key] = $sValue;
                    }
                }

                if (!$orderAddressData) {
                    foreach ($aColumns as $sColumn) {
                        if (strpos($sColumn, 'shipping_address.') !== false) {
                            $orderAddressData[$sColumn] = '';
                        }
                    }
                }

                /*merge order address to shipment export data*/
                $shipmentData = array_merge($shipmentData, $orderAddressData);

                /*get additional columns data*/
                $shipmentData = $this->getShipmentCustomField($shipmentData);

                /*push export row data to export array*/
                $resultData[] = $shipmentData;

                /*store success export shipment id*/
                array_push($this->successList, $shipmentData['entity_id']);
            } else {
                /*store success export shipment id*/
                array_push($this->noNeedExportList, $shipmentData['entity_id']);
            }
        }

        return $resultData;
    }

    /**
     * update shipment
     *      set flag_export_invoice_sales_shipment = 1
     *      for success export order
     *      set flag_export_invoice_sales_shipment = 2
     *      for do not need export order
     */
    public function updateFlagExport()
    {
        $table = $this->connectionSales->getTableName('sales_shipment');
        if (!empty($this->successList)) {
            try {
                $this->connectionSales->update(
                    $table,
                    ['flag_export_invoice_sales_shipment' => 1],
                    ['entity_id in (?)' => $this->successList]
                );
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }

        if (!empty($this->noNeedExportList)) {
            try {
                $this->connectionSales->update(
                    $table,
                    ['flag_export_invoice_sales_shipment' => 2],
                    ['entity_id in (?)' => $this->noNeedExportList]
                );
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }
    }

    /**
     * @param $shipmentData
     * @return mixed
     */
    public function getShipmentCustomField($shipmentData)
    {
        // shipping_tax_rate
        $shipmentData['shipment.shipping_tax_rate'] = $this->getShippingTaxRate();

        $deliveryInfo = $this->getDeliveryInfo($shipmentData['order_id']);

        // time_slot_start
        $shipmentData['shipment.time_slot_start'] = isset($deliveryInfo['delivery_timeslot_from']) ? $deliveryInfo['delivery_timeslot_from'] : '';

        // time_slot_end
        $shipmentData['shipment.time_slot_end'] = isset($deliveryInfo['delivery_timeslot_to']) ? $deliveryInfo['delivery_timeslot_to'] : '';;

        // export_date
        $shipmentData['shipment.warehouse_export_date'] = $this->getShipmentDateByStatus($shipmentData['entity_id'], 'exported');

        // get customer Db
        $shipmentData['shipment.customer_consumer_db_id'] = $this->getConsumerDbId($shipmentData['customer_id']);
        //get order increment id, shosha business code
        $orderAdditionalData = $this->getOrderAdditionalById($shipmentData['order_id']);
        /*order increment id*/
        $shipmentData['shipment.order_increment_id'] = !empty($orderAdditionalData) && $orderAdditionalData['increment_id'] ? $orderAdditionalData['increment_id'] : '';
        /*order shosha business code*/
        $shipmentData['shipment.order_shosha_business_code'] = !empty($orderAdditionalData) && $orderAdditionalData['shosha_business_code'] ? $orderAdditionalData['shosha_business_code'] : '';

        return $shipmentData;
    }

    /**
     * @param $shipment
     * @return array
     */
    public function getShipmentDetailData($shipment)
    {
        $shipmentItemTbl = $this->connectionSales->getTableName('sales_shipment_item');

        $sqlShipmentItem = $this->connectionSales->select()->from(
            $shipmentItemTbl
        )->where(
            $shipmentItemTbl.'.parent_id = ?', $shipment['entity_id']
        );

        $queryShipmentItem = $this->connectionSales->query($sqlShipmentItem);

        $resultData = [];

        while ($shipmentItemData = $queryShipmentItem->fetch()) {

            /*order item table*/
            $orderItemTbl = $this->connectionSales->getTableName('sales_order_item');

            $sqlOrderItem = $this->connectionSales->select()->from(
                $orderItemTbl
            )->where(
                $orderItemTbl.'.item_id = ?', $shipmentItemData['order_item_id']
            );

            $queryOrderItem = $this->connectionSales->query($sqlOrderItem);

            $orderItem = [];

            while ($orderItemData = $queryOrderItem->fetch()) {
                /*convert sales order item date time columns to config timezone*/
                $orderItemData = $this->convertDateTimeColumnsToConfigTimezone(self::ORDERITEMTYPE, $orderItemData);

                /*re calculate data for bundle children item*/
                $orderItemData = $this->_bundleItemsHelper->reCalculateOrderItem($orderItemData);

                $orderItemData[self::ORDERITEMTYPE.'.'.self::SHIPMENT_EXCEPTIONAL_TAX_FLAG_COLUMN] =
                    $this->taxHelper->getTaxExceptionalFlag(
                        $orderItemData['tax_percent'],
                        $shipment['shipped_out_date']
                    );

                foreach ($orderItemData as $sColumn => $sValue) {
                    $key = 'order_item.' . $sColumn;
                    $orderItem[$key] = $sValue;
                }
            }

            // MERGE ORDER ITEM
            $shipmentItemData = array_merge($shipmentItemData, $orderItem);

            //NED-1507 (log shipment item data)
            $this->_logger->info("Items of Shipment #".$shipment['entity_id'].": ". $shipmentItemData['entity_id']);

            /*order table*/
            $orderTbl = $this->connectionSales->getTableName('sales_order');

            $sqlOrder = $this->connectionSales->select()->from(
                $orderTbl, ['riki_type']
            )->where(
                $orderTbl.'.entity_id = ?', $shipmentItemData['order_item.order_id']
            );

            $rikiType = $this->connectionSales->fetchOne($sqlOrder);

            if ($rikiType) {
                $shipmentItemData['shipment_item.order_riki_type'] = $rikiType;
            } else {
                $shipmentItemData['shipment_item.order_riki_type'] = '';
            }

            // GET DATA TABLE: shipment_increment_id
            $shipmentItemData['shipment_increment_id'] = $shipment['increment_id'];

            // RESULT DATA
            $resultData[] = $shipmentItemData;
        }

        return $resultData;
    }

    /**
     * Create detail file
     *
     * @param [] $shipment
     * @param int $i
     */
    public function exportShipmentItem($shipment,$i = 0)
    {
        $detailExport = [];
        $aColumns = $this->getShipmentDetailExportColumns([]);

        array_push($detailExport, $aColumns);

        if (!empty($shipment)) {
            $ashipmentDetailData = $this->getShipmentDetailData($shipment);

            foreach ($ashipmentDetailData as $aShipmentDetailItem) {
                $aShipmentDetailItem['order_item.product_options'] = $this->_bundleItemsHelper->convertDetailOptionsToJsonFormat($aShipmentDetailItem['order_item.product_options']);
                array_push($detailExport, $aShipmentDetailItem);
            }
        }

        /*export detail file name*/
        $timeExport = $this->_timezone->date()->modify('+'.$i.' seconds')->format('YmdHis');
        $exportDetailFileName = 'invoiceshipmentdetail-'.$timeExport.'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportDetailFileName => $detailExport
        ]);
    }

    /**
     * @param array $aAdditionColumns
     * @return array
     */
    public function getShipmentDetailExportColumns($aAdditionColumns = [])
    {
        $aColumns = [];

        $aShipmentItemColumns = $this->connectionSales->describeTable($this->connectionSales->getTableName('sales_shipment_item'));
        foreach ($aShipmentItemColumns as $sColumnShipmentItem => $sValue) {
            $aColumns[] = 'shipment_item.'.$sColumnShipmentItem;
        }
        /*Exceptional tax flag column*/
        $aOrderItemColumns = $this->connectionSales->describeTable($this->connectionSales->getTableName('sales_order_item'));
        foreach ($aOrderItemColumns as $sColumnItem => $sValue) {
            $aColumns[] = 'order_item.'.$sColumnItem;
        }
        $aColumns = array_merge($aColumns, [self::ORDERITEMTYPE.'.'.self::SHIPMENT_EXCEPTIONAL_TAX_FLAG_COLUMN]);
        // RIKI TYPE COLUMN
        $aColumns[] = 'shipment_item.order_riki_type';

        // INCREMENT ID
        $aColumns[] = 'shipment_increment_id';

        $aColumns = array_merge($aColumns, $aAdditionColumns);

        return $aColumns;
    }

    /**
     * @return float
     */
    public function getShippingTaxRate()
    {
        /*get tax class id from config*/
        $taxClassId = $this->scopeConfig->getValue(
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, 'default'
        );

        $rate = $this->taxCalculation->getCalculatedRate($taxClassId);

        return $rate;
    }

    /**
     * @param $shipmentId
     * @param $status
     * @return string
     */
    public function getShipmentDateByStatus($shipmentId, $status)
    {
        $collection = $this->statusHistory->getCollection()
            ->addFieldToFilter('shipment_status', $status)
            ->addFieldToFilter('shipment_id', $shipmentId);

        if ($collection->getSize()) {
            return $collection->setPageSize(1)->getFirstItem()->getShipmentDate();
        }

        return '';
    }

    /**
     * @param $orderId
     * @return array
     */
    public function getDeliveryInfo($orderId)
    {
        $order = $this->getOrder($orderId);
        $result = [];

        foreach ($order->getItems() as $item) {
            if ($item->getData('delivery_timeslot_from')) {
                $result['delivery_timeslot_from'] = $item->getData('delivery_timeslot_from');
            } if ($item->getData('delivery_timeslot_to')) {
                $result['delivery_timeslot_to'] = $item->getData('delivery_timeslot_to');
            }
            return $result;
        }

        return $result;
    }

    /**
     * @param $id
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
     * Get customer consumer db id
     *
     * @param $customerId
     * @return string
     */
    public function getConsumerDbId($customerId)
    {
        if(!$customerId){
            return '';
        }

        $table = $this->connection->getTableName('customer_entity');

        $select = $this->connection->select()
            ->from($table,'consumer_db_id')
            ->where('entity_id = ?' , $customerId)
            ->limit(1);

        $cunsumerDB = $this->connection->fetchOne($select);

        if ($cunsumerDB) {
            return $cunsumerDB;
        }

        return '';
    }

    /**
     * Get additional order data for export - increment id, shosha_business_code
     *
     * @param $orderId
     * @return array|bool
     */
    public function getOrderAdditionalById($orderId)
    {
        if (!$orderId) {
            return false;
        }

        $select = $this->connectionSales->select()
            ->from($this->connectionSales->getTableName('sales_order'),['increment_id', 'shosha_business_code'])
            ->where('entity_id = ?',$orderId);

        return $this->connectionSales->fetchRow($select);
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
            $trackData = $this->convertDateTimeColumnsToConfigTimezone(self::SHIPMENTTRACKTYPE,$trackData);
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

        if ($warehouse == self::WAREHOUSE_BIZEX) {
            $carrierCode = \Riki\ShippingCarrier\Model\Carrier\Bizex::CARRIER_CODE;
            $sortOrder = \Magento\Framework\Api\SortOrder::SORT_ASC;

            $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);

            if (!$trackData) {
                $carrierCode = \Riki\ShippingCarrier\Model\Carrier\Yupack::CARRIER_CODE;
                $sortOrder = \Magento\Framework\Api\SortOrder::SORT_DESC;
                $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);
            }
            if (!$trackData) {
                $carrierCode = \Riki\ShippingCarrier\Model\Carrier\Sagawa::CARRIER_CODE;
                $sortOrder = \Magento\Framework\Api\SortOrder::SORT_DESC;
                $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);
            }
        } else if ($warehouse == self::WAREHOUSE_TOYO) {
            $sortOrder = \Magento\Framework\Api\SortOrder::SORT_DESC;
            $carrierCode = \Riki\ShippingCarrier\Model\Carrier\Kinki::CARRIER_CODE;

            $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);

            if (!$trackData) {
                $carrierCode = \Riki\ShippingCarrier\Model\Carrier\Tokai::CARRIER_CODE;
                $trackData = $this->getShipmentTrackDataByCarrierCode($shipmentId, $carrierCode, $sortOrder);
            }
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
        $getShipmentTrack->where('parent_id = ?', $shipmentId);

        if ($carrierCode) {
            $getShipmentTrack->where('carrier_code = ?', $carrierCode);

            if ($sortOrder) {
                $getShipmentTrack->order('track_number '. $sortOrder);
            }
        } else {
            $getShipmentTrack->order('entity_id DESC');
        }

        $getShipmentTrack->limit(1);

        $trackData = $this->connectionSales->fetchRow($getShipmentTrack);

        return $trackData;
    }

    /**
     * Convert datetime columns to config timezone for shipment/shipment_track object
     *
     * @param $type
     * @param $object
     * @return mixed
     */
    public function convertDateTimeColumnsToConfigTimezone($type, $object)
    {
        $dateTimeColumns = [];
        if ($type == self::SHIPMENTTYPE) {
            $dateTimeColumns = $this->getShipmentDateTimeColumns();
        } else if($type == self::SHIPMENTTRACKTYPE) {
            $dateTimeColumns = $this->getShipmentTrackDateTimeColumns();
        } else if($type == self::ORDERITEMTYPE) {
            $dateTimeColumns = $this->getOrderItemDateTimeColumns();
        }

        if ($dateTimeColumns) {
            foreach ($dateTimeColumns as $cl) {
                if (!empty($object[$cl])) {
                    /*convert object date time columns to config timezone*/
                    $object[$cl] = $this->convertToConfigTimezone($object[$cl]);
                }
            }
        }

        return $object;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_shipment
     * @return mixed
     */
    public function getShipmentDateTimeColumns()
    {
        if (empty($this->_shipmentDateTimecolumns)) {
            $this->_shipmentDateTimecolumns = $this->_dateTimeColumnsHelper->getShipmentDateTimeColumns();
        }

        return $this->_shipmentDateTimecolumns;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_shipment
     * @return mixed
     */
    public function getShipmentTrackDateTimeColumns()
    {
        if (empty($this->_shipmentTrackDateTimecolumns)) {
            $this->_shipmentTrackDateTimecolumns = $this->_dateTimeColumnsHelper->getShipmentTrackDateTimeColumns();
        }

        return $this->_shipmentTrackDateTimecolumns;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order_item
     * @return mixed
     */
    public function getOrderItemDateTimeColumns()
    {
        if (empty($this->_orderItemDateTimecolumns)) {
            $this->_orderItemDateTimecolumns = $this->_dateTimeColumnsHelper->getOrderItemDateTimeColumns();
        }

        return $this->_orderItemDateTimecolumns;
    }
    /**
     * @return mixed
     */
    public function getRunOneTimePerMonth()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_RUN_ONE_TIME_PER_MONTH, $storeScope);
    }

    /**
     * can shipment export to BI - invoice
     *
     * @param array $shipment
     * @return bool
     */
    public function canExportShipment(array $shipment)
    {
        /*check customer is Cedyna customer*/
        $isCedynaCustomer = $this->isCedynaCustomer($shipment['customer_id']);

        if (!$isCedynaCustomer) {
            return false;
        }

        /*check order is invoice order*/
        $isInvoicedOrder = $this->_orderHelper->isInvoicedOrderById($shipment['order_id']);

        if (!$isInvoicedOrder) {
            return false;
        }

        return true;
    }

    /**
     * is cedyna customer
     *
     * @param $customerId
     * @return bool
     */
    public function isCedynaCustomer($customerId)
    {
        if (!isset($this->cedynaCustomer[$customerId])) {
            $this->cedynaCustomer[$customerId] = $this->_shoshaHelper->isCedynaCustomer($customerId);
        }

        return $this->cedynaCustomer[$customerId];
    }

    /**
     * is invoiced order
     *
     * @param $orderId
     * @return bool
     */
    public function isInvoicedOrder($orderId)
    {
        if (!isset($this->invoicedOrder[$orderId])) {
            $this->invoicedOrder[$orderId] = $this->_orderHelper->isInvoicedOrderById($orderId);
        }

        return $this->invoicedOrder[$orderId];
    }
}
