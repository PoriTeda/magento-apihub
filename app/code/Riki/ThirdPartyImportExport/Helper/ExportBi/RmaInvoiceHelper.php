<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;

class RmaInvoiceHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    const CONFIG_RUN_ONE_TIME_PER_MONTH = 'di_data_export_setup/data_cron_rma_invoice/one_time_per_month';

    const RWG_DUMMY_DATA = 'RWG_';
    const ORDERTYPE = 'order';
    const ORDERITEMTYPE = 'order_item';
    const RMATYPE = 'return';

    const RMATABLE = 'magento_rma';
    const RMAPREFIX = 'magento_rma.';
    const ORDERTABLE = 'sales_order';
    const ORDERPREFIX = self::RMAPREFIX.'order_';
    const CUSTOMERTABLE = 'customer';
    const CUSTOMERPREFIX = self::RMAPREFIX.'customer_';
    const ADDRESSTABLE = 'sales_order_address';
    const ADDRESSPREFIX = self::RMAPREFIX.'billing_address_';
    const ADDRESSTYPE = 'billing';
    const REASONTABLE = 'riki_rma_reason';
    const REASONPREFIX = self::RMAPREFIX.'reason_';
    const CARRIERCODETABLE = 'magento_rma_shipping_label';
    const CARRIERCODEPREFIX = self::RMAPREFIX.'shipping_label_';

    const RMAITEMTABLE = 'magento_rma_item_entity';
    const RMAITEMPREFIX = 'magento_rma_item.';
    const RMAPREFIXFORITEM = self::RMAITEMPREFIX.'rma_';
    const ORDERITEMTABLE = 'sales_order_item';
    const ORDERITEMPREFIX = self::RMAITEMPREFIX.'order_item_';

    const TAX_IDENTIFIER_SHIPPING_FEE_CODE = 'Shipping Fee Rate';
    const TAX_IDENTIFIER_SHIPPING_FEE_DEFAULT_STATE = 0;
    const TAX_IDENTIFIER_SHIPPING_FEE_DEFAULT_POSTCODE = '*';
    const RMA_RETURN_SHIPPING_FEE_TAX_RATE_FIELD = 'return_shipping_fee_tax_rate';

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var \Magento\Rma\Api\CommentRepositoryInterface
     */
    protected $_rmaStatusHistoryRepository;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customerModel;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;
    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSaleFactory
     */
    protected $_pointOfSale;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper
     */
    protected $_bundleItemsHelper;
    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $_orderHelper;
    /**
     * @var \Riki\Customer\Helper\ShoshaHelper
     */
    protected $_shoshaHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Model\ResourceModel\Rma\Shipping
     */
    protected $_shippingResource;

    protected $_defaultConnection;
    protected $_salesConnection;

    protected $_rmaColumns;
    protected $_rmaItemColumns;
    protected $_customerColumns;
    protected $_orderColumns;
    protected $_orderItemColumns;
    protected $_addressColumns;
    protected $_reasonColumns;
    protected $_shippingLabelColumns;

    /*array of rma item entity id which is generated bundle option*/
    protected $_exportBundleForItem = [];

    /*flag array to store Cedyna customer value */
    protected $cedynaCustomer = [];

    /*flag array to store invoice order value */
    protected $invoicedOrder = [];

    /* list shipment has exported success*/
    protected $successList = [];

    /* list shipment do not need to export*/
    protected $noNeedExportList = [];

    /**
     * @var InvoiceSaleShipmentHelper
     */
    protected $invoiceSaleShipmentHelper;

    /**
     * @var \Riki\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var \Riki\Sales\Model\ResourceModel\Order\Shipment
     */
    protected $shipmentResource;

    /**
     * RmaInvoiceHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Rma\Api\CommentRepositoryInterface $rmaStatusHistoryRepository
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSale
     * @param GlobalHelper\SftpExportHelper $sftpHelper
     * @param GlobalHelper\FileExportHelper $fileHelper
     * @param GlobalHelper\ConfigExportHelper $configHelper
     * @param GlobalHelper\EmailExportHelper $emailHelper
     * @param GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper
     * @param GlobalHelper\BundleItemsHelper $bundleItemsHelper
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Riki\Customer\Helper\ShoshaHelper $shoshaHelper
     * @param \Riki\ThirdPartyImportExport\Model\ResourceModel\Rma\Shipping $shippingResource
     * @param InvoiceSaleShipmentHelper $invoiceSaleShipmentHelper
     * @param \Riki\Tax\Helper\Data $taxHelper
     * @param \Riki\Sales\Model\ResourceModel\Order\Shipment $shipmentResource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Rma\Api\CommentRepositoryInterface $rmaStatusHistoryRepository,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSale,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Riki\Sales\Helper\Order $orderHelper,
        \Riki\Customer\Helper\ShoshaHelper $shoshaHelper,
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Rma\Shipping $shippingResource,
        InvoiceSaleShipmentHelper $invoiceSaleShipmentHelper,
        \Riki\Tax\Helper\Data $taxHelper,
        \Riki\Sales\Model\ResourceModel\Order\Shipment $shipmentResource
    ) {
        parent::__construct($context, $dateTime, $timezone, $resourceConfig, $sftpHelper, $fileHelper, $configHelper, $emailHelper, $dateTimeColumnsHelper, $connectionHelper);
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderRepository = $orderRepository;
        $this->_rmaStatusHistoryRepository = $rmaStatusHistoryRepository;
        $this->_customerModel = $customerModel;
        $this->_customerRepository = $customerRepository;
        $this->_pointOfSale = $pointOfSale;
        $this->_bundleItemsHelper = $bundleItemsHelper;
        $this->_orderHelper = $orderHelper;
        $this->_shoshaHelper = $shoshaHelper;
        $this->_shippingResource = $shippingResource;

        $this->_defaultConnection = $connectionHelper->getDefaultConnection();
        $this->_salesConnection = $connectionHelper->getSalesConnection();
        $this->invoiceSaleShipmentHelper = $invoiceSaleShipmentHelper;
        $this->taxHelper = $taxHelper;
        $this->shipmentResource = $shipmentResource;
    }

    /**
     * export process
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

            /*send email notify*/
            $this->sentNotificationEmail();
        }
    }

    /**
     * export main process
     */
    public function export()
    {
        $rmaExport = [];

        $rmaItemExport = [];

        array_push($rmaExport, $this->getRmaColumns());

        array_push($rmaItemExport, $this->getRmaItemColumns());

        $exportDate = $this->_timezone->date()->format('YmdHis');

        $exportData = $this->getExportData($rmaExport, $rmaItemExport);

        /*export file name*/
        $exportFileName = 'invoicereturnsheader-' . $exportDate . '.csv';

        /*create local file - header*/
        $this->createLocalFile([
            $exportFileName => $exportData['rmaData']
        ]);
        /*create local file - empty detail for empty header*/
        if (empty($this->successList)) {
            $exportDetailFileName = 'invoicereturnsdetail-' . $exportDate . '.csv';
            $this->createLocalFile([
                $exportDetailFileName => $rmaItemExport
            ]);
        }

        /*update flag_export_invoice_sales_shipment column to avoid export again*/
        $this->updateFlagExport();
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
     * @return array
     */
    public function getRmaColumns()
    {
        return array_merge(
            $this->getTableColumns(self::RMATABLE, self::RMAPREFIX),
            $this->getTableColumns(self::ORDERTABLE, self::ORDERPREFIX),
            $this->getTableColumns(self::CUSTOMERTABLE, self::CUSTOMERPREFIX),
            [self::RMAPREFIX.'comments',self::RMAPREFIX.'warehouse_name'],
            $this->getTableColumns(self::ADDRESSTABLE, self::ADDRESSPREFIX),
            $this->getTableColumns(self::REASONTABLE, self::REASONPREFIX),
            $this->getTableColumns(self::CARRIERCODETABLE, self::CARRIERCODEPREFIX)
        );
    }

    /**
     * @return array
     */
    public function getRmaItemColumns()
    {
        return array_merge(
            $this->getTableColumns(self::RMAITEMTABLE, self::RMAITEMPREFIX),
            $this->getTableColumns(self::RMATABLE, self::RMAPREFIXFORITEM),
            $this->getTableColumns(self::ORDERITEMTABLE, self::ORDERITEMPREFIX)
        );
    }

    /**
     * @param $tableName
     * @param $prefix
     * @return array
     */
    public function getTableColumns($tableName, $prefix)
    {
        if( $tableName == self::CUSTOMERTABLE ){
            $columns = [];
            $customerColumns = $this->_customerModel->getAttributes();
            foreach ($customerColumns as $value){
                $columns[] = $value->getName();
            }
            $this->_customerColumns = $columns;
        } else {
            switch ($tableName){
                case self::RMATABLE:
                    $columns = array_keys($this->_defaultConnection->describeTable($tableName));
                    $columns[] = self::RMA_RETURN_SHIPPING_FEE_TAX_RATE_FIELD;
                    $this->_rmaColumns = $columns;
                    break;
                case self::ORDERTABLE:
                    $columns = array_keys($this->_salesConnection->describeTable($tableName));
                    $this->_orderColumns = $columns;
                    break;
                case self::ADDRESSTABLE:
                    $columns = array_keys($this->_salesConnection->describeTable($tableName));
                    $this->_addressColumns = $columns;
                    break;
                case self::RMAITEMTABLE:
                    $columns = array_keys($this->_defaultConnection->describeTable($tableName));
                    $this->_rmaItemColumns = $columns;
                    break;
                case self::ORDERITEMTABLE:
                    $columns = array_keys($this->_salesConnection->describeTable($tableName));
                    $columns[] = InvoiceSaleShipmentHelper::SHIPMENT_EXCEPTIONAL_TAX_FLAG_COLUMN;
                    $this->_orderItemColumns = $columns;
                    break;
                case self::REASONTABLE:
                    $columns = array_keys($this->_defaultConnection->describeTable($tableName));
                    /*remove column id for reason table - duplicate with rma.reason_id*/
                    array_shift($columns);
                    $this->_reasonColumns = $columns;
                    break;
                case self::CARRIERCODETABLE:
                    $columns = ['carrier_code', 'carrier_title', 'track_number'];
                    $this->_shippingLabelColumns = $columns;
                    break;
            }
        }

        return $this->addColumnPrefix($columns, $prefix);
    }

    /**
     * @param $tableName
     * @return mixed
     */
    public function getTableColumnsByName($tableName)
    {
        switch ($tableName) {
            case self::RMATABLE:
                return $this->_rmaColumns;
                break;
            case self::CUSTOMERTABLE:
                return $this->_customerColumns;
                break;
            case self::ORDERTABLE:
                return $this->_orderColumns;
                break;
            case self::ADDRESSTABLE:
                return $this->_addressColumns;
                break;
            case self::RMAITEMTABLE:
                return $this->_rmaItemColumns;
                break;
            case self::ORDERITEMTABLE:
                return $this->_orderItemColumns;
                break;
            case self::REASONTABLE:
                return $this->_reasonColumns;
                break;
            case self::CARRIERCODETABLE:
                return $this->_shippingLabelColumns;
                break;
        }
    }

    /**
     * add Column Prefix
     *
     * @param $columns
     * @param $prefix
     * @return array
     */
    public function addColumnPrefix($columns, $prefix)
    {
        foreach ($columns as &$value){
            $value = $prefix.$value;
        }
        return $columns;
    }

    /**
     * get Export Data
     *
     * @return array
     */
    public function getExportData($rmaExport, $rmaItemExport)
    {
        /*get salse connection*/
        $salesConnection = $this->_salesConnection;

        /*get default connection*/
        $defaultConnection = $this->_defaultConnection;

        $rmaTable = $defaultConnection->getTableName(self::RMATABLE);

        $rmaItemTable = $defaultConnection->getTableName(self::RMAITEMTABLE);

        $reasonTable = $defaultConnection->getTableName(self::REASONTABLE);

        /*magento rma shipping label table*/
        $carrierTable = $defaultConnection->getTableName(self::CARRIERCODETABLE);

        $orderTable = $salesConnection->getTableName(self::ORDERTABLE);

        $orderItemTable = $salesConnection->getTableName(self::ORDERITEMTABLE);

        $addressTable = $salesConnection->getTableName(self::ADDRESSTABLE);

        /*get RMA data*/
        $getRmaQuery = $defaultConnection->select()->from($rmaTable)->where(
            'is_cedyna_exported = 0'
        )->where(
            'return_status = ?', ReturnStatusInterface::COMPLETED
        );

        $rmaData = $defaultConnection->query($getRmaQuery);

        $i = 0;

        $shippingFeeTaxRate = $this->invoiceSaleShipmentHelper->getShippingTaxRate();

        while ($rmaRow = $rmaData->fetch()) {
            $canExportShipment = $this->canExportShipment($rmaRow);
            /*only export rma for cedyna customer and order payment method is invoice*/
            if ($canExportShipment) {
                /*store success export rma id*/
                array_push($this->successList, $rmaRow['entity_id']);

                $rowData = $this->repairTableData(
                    $rmaTable,
                    self::RMAPREFIX,
                    /*convert datetime column to config timezone for rma record which will be exported*/
                    $this->convertDateTimeColumnsToConfigTimezone(self::RMATYPE, $rmaRow)
                );
                /* add rma shipping fee tax rate */
                $rowData[self::RMAPREFIX.self::RMA_RETURN_SHIPPING_FEE_TAX_RATE_FIELD] = $shippingFeeTaxRate;

                $getOrderQuery = $salesConnection->select()->from($orderTable)->where(
                    $orderTable . '.entity_id = ?', $rmaRow['order_id']
                )->limitPage(1, 1)->limit(1);

                /*get related order*/
                $orderData = $this->repairTableData(
                    $orderTable,
                    self::ORDERPREFIX,
                    /*convert datetime column to config timezone for order record which will be exported*/
                    $this->convertDateTimeColumnsToConfigTimezone(self::ORDERTYPE, $salesConnection->fetchRow($getOrderQuery))
                );

                $orderData['magento_rma.order__assignation'] = $this->_bundleItemsHelper->convertDetailOptionsToJsonFormat($orderData['magento_rma.order__assignation']);
                $orderData['magento_rma.order__shipping_fee_by_address'] = $this->_bundleItemsHelper->convertDetailOptionsToJsonFormat($orderData['magento_rma.order__shipping_fee_by_address']);

                /*get customer data*/
                $customerData = $this->repairTableData(
                    self::CUSTOMERTABLE, self::CUSTOMERPREFIX, $this->_customerModel->load($rmaRow['customer_id'])
                );

                /*get rma comment and warehouse name*/
                $commentAndWarehouseName = [
                    self::RMAPREFIX.'comments' => $this->getRmaComment($rmaRow['entity_id']),
                    self::RMAPREFIX.'warehouse_name' => $this->getReturnedWarehouse($rmaRow['returned_warehouse'])
                ];

                /*get related order billing address*/
                $getAddressQuery = $salesConnection->select()->from($addressTable)->where(
                    $addressTable . '.parent_id = :parent_id AND address_type = :address_type'
                )->limitPage(1, 1)->limit(1);

                /*get order billing address*/
                $addressData = $this->repairTableData(
                    $addressTable, self::ADDRESSPREFIX, $salesConnection->fetchRow(
                        $getAddressQuery, [':parent_id' => $rmaRow['order_id'], ':address_type' => self::ADDRESSTYPE]
                    )
                );

                /*get rma reason query*/
                $getReasonQuery = $defaultConnection->select()->from($reasonTable)->where(
                    $reasonTable . '.id = ?', $rmaRow['reason_id']
                )->limitPage(1, 1)->limit(1);

                /*get rma reason data*/
                $reasonData = $this->repairTableData(
                    $reasonTable, self::REASONPREFIX, $defaultConnection->fetchRow($getReasonQuery)
                );

                /*rma carrier data*/
                $carrierData = $this->repairTableData(
                    $carrierTable, self::CARRIERCODEPREFIX, $this->_shippingResource->getCarrierByRmaId($rmaRow['entity_id'], $this->_shippingLabelColumns)
                );

                /*rma export data*/
                array_push(
                    $rmaExport,
                    array_merge(
                        $rowData,
                        $orderData,
                        $customerData,
                        $commentAndWarehouseName,
                        $addressData,
                        $reasonData,
                        $carrierData
                    )
                );

                /*---------RMA ITEM DATA------*/

                /*export item data - add header*/
                $exportItemData = $rmaItemExport;

                if ($rmaRow['is_without_goods'] == \Riki\Rma\Model\Rma::TYPE_WITHOUT_GOODS) {
                    $detailForRmaWithoutGoods = $this->generateReturnWithoutGoodDetail($rmaRow);
                    /*add rma item data to export data*/
                    array_push($exportItemData, $detailForRmaWithoutGoods);
                } else {
                    $getRmaItemQuery = $defaultConnection->select()->from($rmaItemTable)->where(
                        $rmaItemTable . '.rma_entity_id = ?', $rmaRow['entity_id']
                    );

                    $rmaItemData = $defaultConnection->query($getRmaItemQuery);

                    while ($itemRow = $rmaItemData->fetch()) {

                        $itemRowData = $this->repairTableData($rmaItemTable, self::RMAITEMPREFIX, $itemRow);

                        /*related rma, old data above*/
                        $relatedRma = $this->repairTableData(
                            $rmaTable,
                            self::RMAPREFIXFORITEM,
                            /*convert datetime column to config timezone for rma record which will be exported*/
                            $this->convertDateTimeColumnsToConfigTimezone(self::RMATYPE,$rmaRow)
                        );

                        /*get related order item*/
                        $getOrderItemQuery = $salesConnection->select()->from($orderItemTable)->where(
                            $orderItemTable . '.item_id = ?', $itemRow['order_item_id']
                        )->limitPage(1, 1)->limit(1);

                        /*get related order item data*/
                        $orderItemData = $salesConnection->fetchRow( $getOrderItemQuery);

                        if ($orderItemData && $orderItemData['parent_item_id']) {

                            /*generate bundle record for export data*/
                            $parentItemRow = $this->generateReturnBundleItem(
                                $rmaRow, $orderItemData['parent_item_id'], $itemRow['entity_id']
                            );

                            if ($parentItemRow) {
                                array_push($exportItemData, $parentItemRow);
                            }
                        }

                        /*convert sales order item datetime columns to config timezone*/
                        $orderItemData = $this->convertDateTimeColumnsToConfigTimezone(self::ORDERITEMTYPE, $orderItemData);

                        /* calculate Exceptional tax flag*/
                        $orderItemData[
                        InvoiceSaleShipmentHelper::SHIPMENT_EXCEPTIONAL_TAX_FLAG_COLUMN
                        ] = $this->taxHelper->getTaxExceptionalFlag(
                            $orderItemData['tax_percent'],
                            $this->shipmentResource->getShippedOutDateByOrderItemId($orderItemData['item_id'])
                        );

                        $orderItemData = $this->repairTableData($orderItemTable, self::ORDERITEMPREFIX, $orderItemData);

                        /*re calculate data for bundle children item*/
                        $orderItemData = $this->_bundleItemsHelper->reCalculateOrderItem($orderItemData);

                        $itemRowData['magento_rma_item.product_options'] = $this->_bundleItemsHelper->convertDetailOptionsToJsonFormat($itemRowData['magento_rma_item.product_options']);
                        $orderItemData['magento_rma_item.order_item_product_options'] = $this->_bundleItemsHelper->convertDetailOptionsToJsonFormat($orderItemData['magento_rma_item.order_item_product_options']);

                        /*add rma item to export data*/
                        array_push($exportItemData, array_merge(
                            $itemRowData, $relatedRma, $orderItemData
                        ));
                    }
                }

                $exportDate = $this->_timezone->date()->modify('+'.$i.' seconds')->format('YmdHis');
                $exportFileName = 'invoicereturnsdetail-'. $exportDate . '.csv';

                /*create local file*/
                $this->createLocalFile([
                    $exportFileName => $exportItemData
                ]);

                $i++;
            } else {
                /*store success export rma id*/
                array_push($this->noNeedExportList, $rmaRow['entity_id']);
            }
        }

        return [
            'rmaData' => $rmaExport,
            'rmaItemData'=> $rmaItemExport
        ];
    }

    /**
     * repair Table Data
     *
     * @param $tableName
     * @param $prefix
     * @param $rowData
     * @return array
     */
    public function repairTableData( $tableName, $prefix, $rowData)
    {
        $columns = $this->getTableColumnsByName($tableName);

        if ($prefix == self::REASONPREFIX) {
            /*duplicate magento_rma.reason_id from main table to magento_rma.reason_ id from reason table*/
            $prefix .= '_';
        } else if ($prefix == self::ORDERPREFIX) {
            /*duplicate magento_rma.order_increment_id from main table to magento_rma.order_ increment_id from order table*/
            $prefix .= '_';
        } else if ($prefix == self::RMAPREFIXFORITEM) {
            /*duplicate magento_rma_item.rma_entity_id from main table to magento_rma_item.rma_ entity_id from magento_rma table*/
            $prefix .= '_';
        }

        $rs = [];
        foreach ($columns as $cl) {
            $rs[ $prefix. $cl ] = !empty($rowData) && isset($rowData[$cl]) ? $rowData[$cl] : null;
        }
        return $rs;
    }

    /**
     * get Rma Comment
     *
     * @param $rmaId
     * @return bool|string
     */
    public function getRmaComment($rmaId)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter(
            'rma_entity_id', $rmaId
        )->create();
        $history = $this->_rmaStatusHistoryRepository->getList($criteria);
        if($history->getTotalCount()) {
            $comment = [];
            foreach ($history as $value){
                if( !empty($value->getComment()) ){
                    array_push($comment, $value->getComment());
                }
            }
            if( !empty($comment) ){
                return implode('<br/>', $comment);
            }
        }
        return false;
    }

    /**
     * get Returned Warehouse
     *
     * @param $placeId
     * @return bool | string
     */
    public function getReturnedWarehouse($placeId)
    {
        $factory = $this->_pointOfSale->create();
        $factory->load($placeId);
        if($factory->getId()){
            return $factory->getStoreCode();
        } else {
            return false;
        }
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
     * Generate export data for detail of return without good
     *
     * @param $rma
     * @return array
     */
    public function generateReturnWithoutGoodDetail($rma)
    {
        /*rma table*/
        $rmaTable = $this->_defaultConnection->getTableName(self::RMATABLE);

        /*rma item table*/
        $rmaItemTable = $this->_defaultConnection->getTableName(self::RMAITEMTABLE);

        /*order item table*/
        $orderItemTable = $this->_salesConnection->getTableName(self::ORDERITEMTABLE);

        $rmaWithoutGoodItem = $this->generateReturnWithoutGoodItem($rma);

        /*related rma, old data above*/
        $relatedRma = $this->repairTableData($rmaTable, self::RMAPREFIXFORITEM, $rma);
        /*get related order item data*/
        $orderItemData = $this->repairTableData($orderItemTable, self::ORDERITEMPREFIX, []);

        /*prepare rma item detail data*/
        $rmaItem = $this->repairTableData($rmaItemTable, self::RMAITEMPREFIX, $rmaWithoutGoodItem);

        return array_merge($rmaItem, $relatedRma, $orderItemData);
    }

    /**
     * generate return without good item
     *
     * @param $rma
     * @return array
     */
    public function generateReturnWithoutGoodItem($rma)
    {
        $defaultDecimalValue = '0.0000';
        return [
            'entity_id' => self::RWG_DUMMY_DATA.$rma['entity_id'],
            'rma_entity_id' => $rma['entity_id'],
            'is_qty_decimal' => 0,
            'qty_requested' => $defaultDecimalValue,
            'qty_authorized' => $defaultDecimalValue,
            'qty_approved' => $defaultDecimalValue,
            'status' => 'approved',
            'order_item_id' => '',
            'product_name' => self::RWG_DUMMY_DATA.'product',
            'qty_returned' => $defaultDecimalValue,
            'product_sku' => self::RWG_DUMMY_DATA.'product_sku',
            'product_admin_name' => '',
            'product_admin_sku' => '',
            'product_options' => '',
            'unit_case' => '',
            'return_amount' => $defaultDecimalValue,
            'return_amount_adj' => $defaultDecimalValue,
            'return_wrapping_fee' => $defaultDecimalValue,
            'return_wrapping_fee_adj' => $defaultDecimalValue,
            'free_of_charge' => '',
            'booking_wbs' => '',
            'foc_wbs' => '',
            'gps_price_ec' => '',
            'material_type' => '',
            'sales_organization' => '',
            'sap_interface_excluded' => 0,
            'return_amount_excl_tax' => $defaultDecimalValue,
            'return_tax_amount' => $defaultDecimalValue,
            'return_amount_adj_excl_tax' => $defaultDecimalValue,
            'return_tax_amount_adj' => $defaultDecimalValue,
            'commission_amount' => $defaultDecimalValue
        ];
    }

    /**
     * After export RMA, change is_cedyna_exported to 1
     */
    public function updateFlagExport()
    {
        /*update data to make system know this record is exported to BI or CEDYNA*/
        $table = $this->_defaultConnection->getTableName(self::RMATABLE);

        if (!empty($this->successList)) {
            try {
                $this->_defaultConnection->update(
                    $table,
                    ['is_cedyna_exported' => 1],
                    ['entity_id in (?)' => $this->successList]
                );
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }

        if (!empty($this->noNeedExportList)) {
            try {
                $this->_defaultConnection->update(
                    $table,
                    ['is_cedyna_exported' => 2],
                    ['entity_id in (?)' => $this->noNeedExportList]
                );
            } catch (\Exception $e) {
                $this->_logger->info($e->getMessage());
            }
        }
    }

    /**
     * Convert datetime columns to config timezone for order/order item/rma object
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
            $dateTimeColumns = $this->getOrderDateTimeColumns();
        } else if($type == self::RMATYPE) {
            $dateTimeColumns = $this->getRmatDateTimeColumns();
        }

        if ($dateTimeColumns) {
            foreach ($dateTimeColumns as $cl) {
                if (!empty($object[$cl])) {
                    $object[$cl] = $this->convertToConfigTimezone($object[$cl]);
                }
            }
        }

        return $object;
    }

    /**
     * Generate bundle item for return item
     *
     * @param $rma
     * @param $orderItemId
     * @param $rmaItemId
     * @return array|bool
     */
    public function generateReturnBundleItem($rma, $orderItemId, $rmaItemId)
    {
        if (in_array($rmaItemId, $this->_exportBundleForItem)) {
            return false;
        }

        /*rma table*/
        $rmaTable = $this->_defaultConnection->getTableName(self::RMATABLE);

        /*rma item table*/
        $rmaItemTable = $this->_defaultConnection->getTableName(self::RMAITEMTABLE);

        /*order item table*/
        $orderItemTable = $this->_salesConnection->getTableName(self::ORDERITEMTABLE);

        /*get order item data*/
        $orderItemData = $this->getOrderItemById($orderItemId);

        if ($orderItemData) {

            /*bundle return qty*/
            $qtyReturned = 0;

            /*bundle return amount*/
            $returnAmount = 0;

            /*bundle return amount adj*/
            $returnAmountAdj = 0;

            /*bundle return wrapping fee*/
            $returnWrappingFee = 0;

            /*bundle return wrapping fee adj*/
            $returnWrappingFeeAdj = 0;

            /*bundle return amount excl tax*/
            $returnAmountExclTax = 0;

            /*bundle return tax amount*/
            $returnTaxAmount = 0;

            /*bundle return amount adj excl tax*/
            $returnAmountAdjExclTax = 0;

            /*bundle return tax amount adj*/
            $returnTaxAmountAdj = 0;

            /*bundle return commission amount*/
            $returnCommissionAmount = 0;

            /*list of children item*/
            $childrenItem = $this->getChildrenItemsByParentId($orderItemId);

            foreach ($childrenItem as $childItem) {
                /*get return data for children item*/
                $rmaChildrenItem = $this->getRmaChildrenItemByOrderItemId($rma['entity_id'], $childItem['item_id']);

                if ($rmaChildrenItem) {

                    /*flag this rma item has been generate bundle data*/
                    array_push($this->_exportBundleForItem, $rmaChildrenItem['entity_id']);

                    /*generate return qty for bundle item*/
                    $qtyReturned = $this->getReturnQtyForBundleItem($orderItemData['qty_ordered'], $childItem['qty_ordered'], $rmaChildrenItem['qty_returned']);

                    /*total return amount from children*/
                    $returnAmount += $rmaChildrenItem['return_amount'];

                    /*total return amount adj from children*/
                    $returnAmountAdj += $rmaChildrenItem['return_amount_adj'];

                    /*total return wrapping fee from children*/
                    $returnWrappingFee += $rmaChildrenItem['return_wrapping_fee'];

                    /*total return wrapping fee adj from children*/
                    $returnWrappingFeeAdj += $rmaChildrenItem['return_wrapping_fee_adj'];

                    /*total bundle return amount excl tax from children*/
                    $returnAmountExclTax += $rmaChildrenItem['return_amount_excl_tax'];

                    /*total bundle return tax amount from children*/
                    $returnTaxAmount += $rmaChildrenItem['return_tax_amount'];

                    /*total bundle return amount adj excl tax from children*/
                    $returnAmountAdjExclTax += $rmaChildrenItem['return_amount_adj_excl_tax'];

                    /*total bundle return tax amount adj from children*/
                    $returnTaxAmountAdj += $rmaChildrenItem['return_tax_amount_adj'];

                    /*total bundle commission amount from children*/
                    $returnCommissionAmount += $rmaChildrenItem['commission_amount'];
                }
            }

            /*get correct format for returned_qty, return amount, return amount adj, return wrapping fee, return wrapping fee adj*/
            $qtyReturned = number_format($qtyReturned, 4, '.', '');
            $returnAmount = number_format($returnAmount, 4, '.', '');
            $returnAmountAdj = number_format($returnAmountAdj, 4, '.', '');
            $returnWrappingFee = number_format($returnWrappingFee, 4, '.', '');
            $returnWrappingFeeAdj = number_format($returnWrappingFeeAdj, 4, '.', '');
            $returnAmountExclTax = number_format($returnAmountExclTax, 4, '.', '');
            $returnTaxAmount = number_format($returnTaxAmount, 4, '.', '');
            $returnAmountAdjExclTax = number_format($returnAmountAdjExclTax, 4, '.', '');
            $returnTaxAmountAdj = number_format($returnTaxAmountAdj, 4, '.', '');
            $returnCommissionAmount = number_format($returnCommissionAmount, 4, '.', '');

            /*get product data*/
            $productData = $this->_orderHelper->getProductDataForOrderItem($orderItemData);

            /*gps price for bundle rma item*/
            $gpsPriceEc = '';
            /*material type for bundle rma item*/
            $materialType = '';
            /*sap interface excluded for bundle rma item*/
            $sapInterfaceExcluded = 0;

            if ($productData) {

                if ($productData->getData('gps_price_ec')) {
                    $gpsPriceEc = $productData->getData('gps_price_ec');
                }

                if ($productData->getData('sales_organization')) {
                    if (is_array($productData->getData('sales_organization'))) {
                        $materialType = implode(',',$productData->getData('sales_organization'));
                    } else {
                        $materialType = $productData->getData('sales_organization');
                    }
                }

                if ($productData->getData('sap_interface_excluded')) {
                    $sapInterfaceExcluded = $productData->getData('sap_interface_excluded');
                }
            }

            /*generate bundle data*/
            $rmaBundleItem =  [
                'entity_id' => '',
                'rma_entity_id' => $rma['entity_id'],
                'is_qty_decimal' => 0,
                'qty_requested' => $qtyReturned,
                'qty_authorized' => $qtyReturned,
                'qty_approved' => $qtyReturned,
                'status' => 'approved',
                'order_item_id' => $orderItemId,
                'product_name' => $orderItemData['name'] ? $orderItemData['name'] : '',
                'qty_returned' => $qtyReturned,
                'product_sku' => $orderItemData['sku'] ? $orderItemData['sku'] : '',
                'product_admin_name' => $orderItemData['name'] ? $orderItemData['name'] : '',
                'product_admin_sku' => $orderItemData['sku'] ? $orderItemData['sku'] : '',
                'product_options' => $orderItemData['product_options'] ? $orderItemData['product_options'] : '',
                'unit_case' => $orderItemData['unit_case'] ? $orderItemData['unit_case'] : '',
                'return_amount' => $returnAmount,
                'return_amount_adj' => $returnAmountAdj,
                'return_wrapping_fee' => $returnWrappingFee,
                'return_wrapping_fee_adj' => $returnWrappingFeeAdj,
                'free_of_charge' => $orderItemData['free_of_charge'] ? $orderItemData['free_of_charge'] : '',
                'booking_wbs' => $orderItemData['booking_wbs'] ? $orderItemData['booking_wbs'] : '',
                'foc_wbs' => $orderItemData['foc_wbs'] ? $orderItemData['foc_wbs'] : '',
                'gps_price_ec' => $gpsPriceEc,
                'material_type' => $materialType,
                'sales_organization' => $orderItemData['sales_organization'] ? $orderItemData['sales_organization'] : '',
                'sap_interface_excluded' => $sapInterfaceExcluded,
                'return_amount_excl_tax' => $returnAmountExclTax,
                'return_tax_amount' => $returnTaxAmount,
                'return_amount_adj_excl_tax' => $returnAmountAdjExclTax,
                'return_tax_amount_adj' => $returnTaxAmountAdj,
                'commission_amount' => $returnCommissionAmount
            ];

            /*get related order item data*/
            $orderItemData = $this->repairTableData(
                $orderItemTable, self::ORDERITEMPREFIX, $orderItemData
            );

            /*prepare rma item detail data*/
            $rmaBundleItem = $this->repairTableData($rmaItemTable, self::RMAITEMPREFIX, $rmaBundleItem);

            /*related rma, old data above*/
            $relatedRma = $this->repairTableData($rmaTable, self::RMAPREFIXFORITEM, $rma);

            return array_merge($rmaBundleItem, $relatedRma, $orderItemData);
        }

        return false;
    }

    /**
     * Get order item by id
     *
     * @param $orderItemId
     * @return array
     */
    public function getOrderItemById($orderItemId)
    {
        /*order item table*/
        $orderItemTable = $this->_salesConnection->getTableName(self::ORDERITEMTABLE);

        /*get order item*/
        $getOrderItemQuery = $this->_salesConnection->select()->from(
            $orderItemTable
        )->where(
            $orderItemTable . '.item_id = ?', $orderItemId
        );

        return $this->_salesConnection->fetchRow( $getOrderItemQuery);
    }

    /**
     * get list of children item by parent item id
     *
     * @param $orderItemId
     * @return array
     */
    public function getChildrenItemsByParentId($orderItemId)
    {
        /*order item table*/
        $orderItemTable = $this->_salesConnection->getTableName(self::ORDERITEMTABLE);

        /*get order item*/
        $getOrderItemQuery = $this->_salesConnection->select()->from(
            $orderItemTable
        )->where(
            $orderItemTable . '.parent_item_id = ?', $orderItemId
        );

        return $this->_salesConnection->fetchAll($getOrderItemQuery);
    }

    /**
     * Get rma children item id by order item id and rma id
     *
     * @param $rmaId
     * @param $childrenItemId
     * @return array
     */
    public function getRmaChildrenItemByOrderItemId($rmaId, $childrenItemId)
    {
        /*rma item table*/
        $rmaItemTable = $this->_defaultConnection->getTableName(self::RMAITEMTABLE);

        /*get order item*/
        $getOrderItemQuery = $this->_defaultConnection->select('item_id')->from(
            $rmaItemTable
        )->where(
            $rmaItemTable . '.rma_entity_id = ?', $rmaId
        )->where(
            $rmaItemTable . '.order_item_id = ?', $childrenItemId
        );

        return $this->_defaultConnection->fetchRow($getOrderItemQuery);
    }

    /**
     * Get return Qty for bundle item
     *
     * @param $parentItemQty
     * @param $childItemQty
     * @param $rmaItemQty
     * @return float|int
     */
    public function getReturnQtyForBundleItem($parentItemQty, $childItemQty, $rmaItemQty)
    {
        if ($rmaItemQty && $rmaItemQty > 0) {
            return floor($parentItemQty * $rmaItemQty / $childItemQty);
        }

        return 0;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_shipment
     * @return mixed
     */
    public function getRmatDateTimeColumns()
    {
        return $this->_dateTimeColumnsHelper->getRmaDateTimeColumns();
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order
     * @return mixed
     */
    public function getOrderDateTimeColumns()
    {
        return $this->_dateTimeColumnsHelper->getOrderDateTimeColumns();
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table sales_order_item
     * @return mixed
     */
    public function getOrderItemDateTimeColumns()
    {
        return $this->_dateTimeColumnsHelper->getOrderItemDateTimeColumns();
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
