<?php
namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

use Riki\ThirdPartyImportExport\Helper\RedisErrorEntity;

class RmaHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
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
    const PRODUCT_RMA_PREFIX = self::RMAITEMPREFIX.'product_rma_';

    const EXPORT_HEADER_FILE_NAME = 'returnsheader';
    const EXPORT_DETAIL_FILE_NAME = 'returnsdetail';
    const SHIPMENT_EXPORTING_FLG = 'shipment_exporting_flg';

    protected $shipmentExportingFlgAttrId;

    /**
     * @var \Magento\Rma\Api\CommentRepositoryInterface
     */
    protected $_rmaStatusHistoryRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customerModel;
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
     * @var \Riki\ThirdPartyImportExport\Model\ResourceModel\Rma\Shipping
     */
    protected $_shippingResource;

    protected $_rmaColumns;
    protected $_rmaItemColumns;
    protected $_customerColumns;
    protected $_orderColumns;
    protected $_orderItemColumns;
    protected $_addressColumns;
    protected $_reasonColumns;
    protected $_shippingLabelColumns;

    protected $_defaultConnection;
    protected $_salesConnection;

    /*list columns which data type is datetime or timestamp, table magento_rma*/
    protected $_rmaDateTimecolumns;

    /*list columns which data type is datetime or timestamp, table sales_order*/
    protected $_orderDateTimeColumns;
    /*list columns which data type is datetime or timestamp, table sales_order_item*/
    protected $_orderItemDateTimeColumns;

    /*array of rma item entity id which is generated bundle option*/
    protected $_exportBundleForItem = [];

    protected $redisErrorList = [];

    protected $originalHeaderData = [];

    protected $originalDetailData = [];

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $eavAttribute;

    /**
     * RmaHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Rma\Api\CommentRepositoryInterface $commentRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     * @param GlobalHelper\SftpExportHelper $sftpHelper
     * @param GlobalHelper\FileExportHelper $fileHelper
     * @param GlobalHelper\ConfigExportHelper $configHelper
     * @param GlobalHelper\EmailExportHelper $emailHelper
     * @param GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper
     * @param GlobalHelper\BundleItemsHelper $bundleItemsHelper
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Riki\ThirdPartyImportExport\Model\ResourceModel\Rma\Shipping $shippingResource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Rma\Api\CommentRepositoryInterface $commentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Model\Customer $customerModel,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Riki\Sales\Helper\Order $orderHelper,
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Rma\Shipping $shippingResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
    ) {
        parent::__construct($context, $dateTime, $timezone, $resourceConfig, $sftpHelper, $fileHelper, $configHelper, $emailHelper, $dateTimeColumnsHelper, $connectionHelper);
        $this->_rmaStatusHistoryRepository = $commentRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_customerModel = $customerModel;
        $this->_pointOfSale = $pointOfSaleFactory;
        $this->_bundleItemsHelper = $bundleItemsHelper;
        $this->_orderHelper = $orderHelper;
        $this->_shippingResource = $shippingResource;

        /*get default connection*/
        $this->_defaultConnection = $connectionHelper->getDefaultConnection();

        /*get sale connection*/
        $this->_salesConnection = $connectionHelper->getSalesConnection();
        $this->eavAttribute = $eavAttribute;
    }

    /**
     * Export process
     *
     * @param $type
     */
    public function exportProcess()
    {
        /*rma export data*/
        $rmaExport = [];

        /*push rma header column to export data*/
        array_push($rmaExport, $this->getRmaColumns());

        /*rma item - export columns*/
        $rmaItemColumns = $this->getRmaItemColumns();

        /*export process*/
        $this->exportData($rmaExport, $rmaItemColumns);

        /* set last time to run */
        $this->setLastRunToCron();

        /*move export folder to ftp*/
        $this->_sftpHelper->moveFileToFtp($this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->getReportPathExport());

        /*send email notify*/
        $this->sentNotificationEmail();
    }

    /**
     * get column for RMA header file
     *
     * @return array
     */
    public function getRmaColumns()
    {
        return array_merge(
            $this->getTableColumns(self::RMATABLE, self::RMAPREFIX),
            [self::RMAPREFIX.'shipment_increment_id'],
            $this->getTableColumns(self::ORDERTABLE, self::ORDERPREFIX),
            $this->getTableColumns(self::CUSTOMERTABLE, self::CUSTOMERPREFIX),
            [self::RMAPREFIX.'comments',self::RMAPREFIX.'warehouse_name'],
            $this->getTableColumns(self::ADDRESSTABLE, self::ADDRESSPREFIX),
            $this->getTableColumns(self::REASONTABLE, self::REASONPREFIX),
            $this->getTableColumns(self::CARRIERCODETABLE, self::CARRIERCODEPREFIX)
        );
    }

    /**
     * get column for RMA detail file
     *
     * @return array
     */
    public function getRmaItemColumns()
    {
        return array_merge(
            $this->getTableColumns(self::RMAITEMTABLE, self::RMAITEMPREFIX),
            $this->getTableColumns(self::RMATABLE, self::RMAPREFIXFORITEM),
            $this->getTableColumns(self::ORDERITEMTABLE, self::ORDERITEMPREFIX),
            [self::PRODUCT_RMA_PREFIX . self::SHIPMENT_EXPORTING_FLG]
        );
    }

    /**
     * @param $tableName
     * @param $prefix
     * @return array
     */
    public function getTableColumns($tableName, $prefix){
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
     * @param $columns
     * @param $prefix
     * @return array
     */
    public function addColumnPrefix($columns, $prefix)
    {
        foreach ($columns as &$value) {
            $value = $prefix.$value;
        }
        return $columns;
    }

    /**
     * @return array
     */
    public function exportData($rmaExport, $rmaItemColumns)
    {
        /*rma item data will be export*/
        $rmaItemExport = [];

        /*add rma item header*/
        array_push($rmaItemExport, $rmaItemColumns);

        /*get salse connection*/
        $salesConnection = $this->_salesConnection;

        /*get default connection*/
        $defaultConnection = $this->_defaultConnection;

        /*rma table*/
        $rmaTable = $defaultConnection->getTableName(self::RMATABLE);

        /*rma item table*/
        $rmaItemTable = $defaultConnection->getTableName(self::RMAITEMTABLE);

        /*reason table*/
        $reasonTable = $defaultConnection->getTableName(self::REASONTABLE);

        /*magento rma shipping label table*/
        $carrierTable = $defaultConnection->getTableName(self::CARRIERCODETABLE);

        /*order table*/
        $orderTable = $salesConnection->getTableName(self::ORDERTABLE);

        /*order item table*/
        $orderItemTable = $salesConnection->getTableName(self::ORDERITEMTABLE);

        /*order address table*/
        $addressTable = $salesConnection->getTableName(self::ADDRESSTABLE);

        /*get RMA data*/
        $getRmaQuery = $this->getRmaQuery($defaultConnection, $rmaTable);

        $rmaData = $defaultConnection->query($getRmaQuery);

        /*list rma id exported - will change is_bi_exported = 1*/
        $exportList = [];

        while ($rmaRow = $rmaData->fetch()) {

            try{
                /*add rma entity id to exported list*/
                array_push($exportList, $rmaRow['entity_id']);

                $this->originalHeaderData = $rmaExport;
                $this->originalDetailData = $rmaItemExport;

                $rowData = $this->repairTableData($rmaTable, self::RMAPREFIX, $rmaRow);

                $getOrderQuery = $salesConnection->select()->from($orderTable)->where(
                    $orderTable . '.entity_id = ?', $rmaRow['order_id']
                )->limitPage(1, 1)->limit(1);

                $shipmentIncrementIds = $this->getShipmentIncrementIdsByOrderId($rmaRow['order_id']);
                $rowData[self::RMAPREFIX.'shipment_increment_id'] = $shipmentIncrementIds;

                /*get related order*/
                $orderData = $this->repairTableData(
                    $orderTable, self::ORDERPREFIX, $salesConnection->fetchRow($getOrderQuery)
                );

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
                array_push($rmaExport, array_merge(
                    $rowData, $orderData, $customerData, $commentAndWarehouseName, $addressData, $reasonData, $carrierData
                ));

                /*---------RMA ITEM DATA------*/
                $getRmaItemQuery = $defaultConnection->select()->from($rmaItemTable)->where(
                    $rmaItemTable . '.rma_entity_id = ?', $rmaRow['entity_id']
                );

                $rmaItemData = $defaultConnection->query($getRmaItemQuery);

                while ($itemRow = $rmaItemData->fetch()) {

                    /*get related order item*/
                    $getOrderItemQuery = $salesConnection->select()->from($orderItemTable)->where(
                        $orderItemTable . '.item_id = ?', $itemRow['order_item_id']
                    )->limitPage(1, 1)->limit(1);

                    /*get order item data*/
                    $orderItemData = $salesConnection->fetchRow( $getOrderItemQuery);

                    if ($orderItemData && $orderItemData['parent_item_id']) {

                        /*generate bundle record for export data*/
                        $parentItemRow = $this->generateReturnBundleItem(
                            $rmaRow, $orderItemData['parent_item_id'], $itemRow['entity_id']
                        );
                        if ($parentItemRow) {
                            $parentItemRow = $this->addShipmentExportingFlgValue($parentItemRow);
                            array_push($rmaItemExport, $parentItemRow);
                        }
                    }

                    /*get related order item data*/
                    $orderItemData = $this->repairTableData(
                        $orderItemTable, self::ORDERITEMPREFIX, $orderItemData
                    );

                    /*prepare rma item detail data*/
                    $itemRowData = $this->repairTableData($rmaItemTable, self::RMAITEMPREFIX, $itemRow);

                    /*related rma, old data above*/
                    $relatedRma = $this->repairTableData($rmaTable, self::RMAPREFIXFORITEM, $rmaRow);

                    $orderItemData = $this->addShipmentExportingFlgValue($orderItemData);
                    /*add rma item data to export data*/
                    array_push($rmaItemExport,
                        array_merge($itemRowData, $relatedRma, $orderItemData)
                    );
                }
            } catch (\Exception $exception){
                $this->_logger->critical($exception);
                $this->redisErrorList[] = $rmaRow['entity_id'];
                $rmaExport = $this->originalHeaderData;
                $rmaItemExport = $this->originalDetailData;
            }
        }

        /*get export date via config timezone*/
        $exportDate = $this->_timezone->date()->format('YmdHis');

        /*rma header file name*/
        $rmaHeaderFileName = self::EXPORT_HEADER_FILE_NAME . '-' .$exportDate . '.csv';

        /*rma detail file name*/
        $detailFileName = self::EXPORT_DETAIL_FILE_NAME . '-' .$exportDate . '.csv';

        /*export rma detail*/
        $this->createLocalFile([
            $rmaHeaderFileName => $rmaExport,
            $detailFileName => $rmaItemExport
        ]);
        // Do not update Rma with redis error
        $exportList = array_diff($exportList, $this->redisErrorList);
        /*change is_bi_exported = 1 for all rma which is exported success*/
        $this->updateRmaAfterExportBi($exportList);
    }

    /**
     * @param $tableName
     * @param $prefix
     * @param $rowData
     * @return array
     */
    public function repairTableData($tableName, $prefix, $rowData)
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
        } else if ($prefix == self::ORDERITEMPREFIX) {
            /*re calculate data for bundle children item*/
            $rowData = $this->_bundleItemsHelper->reCalculateOrderItem($rowData);
        }

        if(in_array($prefix, [self::ORDERITEMPREFIX, self::RMAITEMPREFIX])) {
            // Ensure no more serialize string in product_options
            $productOptions = $this->_bundleItemsHelper->convertDetailOptionsToJsonFormat($rowData['product_options']);
            $rowData['product_options'] = $productOptions;
        }

        $rs = [];

        /*generate table data again to make sure we do not miss any columns for export file*/
        foreach ($columns as $cl) {
            if (!empty($rowData) && isset($rowData[$cl])) {

                if (!empty($rowData[$cl]) && $this->isDateTimeColumns($tableName, $cl)) {
                    $rs[ $prefix. $cl ] = $this->convertToConfigTimezone($rowData[$cl]);
                } else {
                    $rs[ $prefix. $cl ] = $rowData[$cl];
                }
            } else {
                $rs[ $prefix. $cl ] = null;
            }
        }

        return $rs;
    }

    /**
     * @param $rmaId
     * @return bool|string
     */
    public function getRmaComment($rmaId)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter(
            'rma_entity_id', $rmaId
        )->create();
        $history = $this->_rmaStatusHistoryRepository->getList($criteria);
        if ($history->getTotalCount()) {
            $comment = [];
            foreach ($history as $value) {
                if (!empty($value->getComment())) {
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
     * @param $placeId
     * @return bool | string
     */
    public function getReturnedWarehouse($placeId)
    {
        $factory = $this->_pointOfSale->create();
        $factory->load($placeId);
        if ($factory->getId()) {
            return $factory->getStoreCode();
        } else {
            return false;
        }
    }

    /**
     * Get rma data query
     *
     * @param $defaultConnection
     * @param $rmaTable
     * @return mixed
     */
    public function getRmaQuery($defaultConnection, $rmaTable)
    {
        return $defaultConnection->select()
            ->from($rmaTable)
            /*get complete rma*/
            ->where($rmaTable . '.status = ?', \Magento\Rma\Model\Rma\Source\Status::STATE_PROCESSED_CLOSED)
            /*did not export to bi*/
            ->where($rmaTable . '.is_bi_exported = 0');
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
     *      table magento_rma
     * @return mixed
     */
    public function getRmaDateTimeColumns()
    {
        if (empty($this->_rmaDateTimecolumns)) {
            $this->_rmaDateTimecolumns = $this->_dateTimeColumnsHelper->getRmaDateTimeColumns();
        }

        return $this->_rmaDateTimecolumns;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table magento_rma
     * @return mixed
     */
    public function getOrderDateTimeColumns()
    {
        if (empty($this->_orderDateTimeColumns)) {
            $this->_orderDateTimeColumns = $this->_dateTimeColumnsHelper->getOrderDateTimeColumns();
        }
        return $this->_orderDateTimeColumns;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table magento_rma
     * @return mixed
     */
    public function getOrderItemDateTimeColumns()
    {
        if (empty($this->_orderItemDateTimeColumns)) {
            $this->_orderItemDateTimeColumns = $this->_dateTimeColumnsHelper->getOrderItemDateTimeColumns();
        }
        return $this->_orderItemDateTimeColumns;
    }

    /**
     * check columns with data type is datetime or timestamp
     *
     * @param $tableName
     * @param $cl
     * @return bool
     */
    public function isDateTimeColumns($tableName, $cl)
    {
        $dateTimeColumns = [];

        if ($tableName == self::RMATABLE) {
            /*get rma date time columns*/
            $dateTimeColumns = $this->getRmaDateTimeColumns();
        } else if ($tableName == self::ORDERTABLE) {
            /*get order date time columns*/
            $dateTimeColumns = $this->getOrderDateTimeColumns();

        } else if ($tableName == self::ORDERITEMTABLE) {
            $dateTimeColumns = $this->getOrderItemDateTimeColumns();
        }

        if ($dateTimeColumns && in_array($cl, $dateTimeColumns)) {
            return true;
        }

        return false;
    }

    /**
     * After export RMA, change is_bi_exported to 1
     *
     * @param $rmaList
     */
    public function updateRmaAfterExportBi($rmaList)
    {
        /*change is_bi_exported to 1, make sure do not export it again*/
        if (!empty( $rmaList)) {
            $table = $this->_defaultConnection->getTableName(self::RMATABLE);
            $bind = [ 'is_bi_exported' => 1 ];
            $where = ['entity_id IN (?)' => $rmaList];
            $this->_defaultConnection->update($table, $bind, $where);
        }
    }


    /**
     * Get shipment increment id by order id
     * @param $orderId
     * @return string
     */
    public function getShipmentIncrementIdsByOrderId($orderId)
    {
        $connection = $this->_salesConnection;
        $shipmentTblName = $connection->getTableName('sales_shipment');
        $bind = ['order_id' => $orderId];
        $select = $connection->select()->from($shipmentTblName, 'increment_id')
            ->where('order_id = :order_id');
        $shipmentIncrementIds = $connection->fetchCol($select, $bind);
        $shipmentIncrementIds = implode(';', $shipmentIncrementIds);
        return $shipmentIncrementIds;
    }

    /**
     * Get shipment_exporting_flg value
     * @param $productId
     * @return null|int
     */
    private function getShipmentExportingFlg($productId)
    {
        $shipmentExportingFlgValue = '';
        $shipmentExportingFlgAttrId = $this->getShipmentExportingFlgAttrId();
        if ($shipmentExportingFlgAttrId) {
            $selectQuery = $this->_defaultConnection->select()
                ->from('catalog_product_entity_int', ['value'])
                ->where('entity_id = ?', $productId)
                ->where('attribute_id = ?', $shipmentExportingFlgAttrId);
            $shipmentExportingFlgValue = $this->_defaultConnection->fetchOne($selectQuery);
        }
        return $shipmentExportingFlgValue;
    }

    /**
     * Get shipment_exporting_flg attribute id
     * @return int
     */
    private function getShipmentExportingFlgAttrId()
    {
        if (!$this->shipmentExportingFlgAttrId) {
            $attributeId = $this->eavAttribute->getIdByCode(
                'catalog_product',
                self::SHIPMENT_EXPORTING_FLG
            );
            $this->shipmentExportingFlgAttrId = $attributeId;
        }
        return $this->shipmentExportingFlgAttrId;
    }

    /**
     * Add shipment_export_flg value
     * @param $orderItemData
     * @return array
     */
    private function addShipmentExportingFlgValue($orderItemData)
    {
        if (isset($orderItemData[self::ORDERITEMPREFIX . 'product_id'])) {
            $productId = $orderItemData[self::ORDERITEMPREFIX . 'product_id'];
            $shipmentExportingFlg = $this->getShipmentExportingFlg($productId);
            $orderItemData[self::PRODUCT_RMA_PREFIX.self::SHIPMENT_EXPORTING_FLG] = $shipmentExportingFlg;
        }
        return $orderItemData;
    }
}
