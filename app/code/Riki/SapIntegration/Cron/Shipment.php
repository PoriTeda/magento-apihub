<?php

namespace Riki\SapIntegration\Cron;

use Riki\SapIntegration\Model\Api\Shipment as ShipmentApi;

class Shipment
{
    /**
     * @var \Riki\SapIntegration\Model\Api\Shipment
     */
    protected $_sapApi;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory
     */
    protected $_shipmentFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $_shipmentCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\ItemFactory
     */
    protected $_shipmentItemFactory;

    /**
     * @var \Riki\SapIntegration\Logger\ShipmentLogger
     */
    protected $_logger;

    /**
     * @var \Riki\Catalog\Helper\Data
     */
    protected $_catalogHelper;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $_orderHelper;

    /**
     * @var \Riki\Customer\Helper\Data
     */
    protected $_customerHelper;

    /**
     * @var \Riki\SapIntegration\Helper\Data
     */
    protected $_sapHelper;

    /**
     * @var \Riki\TmpRma\Model\Config\Source\Rma\ReturnedWarehouse
     */
    protected $_rmaWarehouse;

    /**
     * @var int
     */
    protected $_successRow = 0;

    /**
     * @var int
     */
    protected $_failedRow = 0;

    /**
     * @var array
     */
    protected $_orderItems;

    /**
     * @var array
     */
    protected $_orders;

    /**
     * @var array
     */
    protected $_customers;

    /**
     * @var array
     */
    protected $_warehouses;

    /**
     * @var \Magento\Framework\App\Config
     */
    protected $_config;

    /**
     * @var array
     */
    public static $order = ['substitution'];

    /**
     * @var array
     */
    public static $orderItemFields = ['tax_riki', 'price_incl_tax', 'row_total'];

    /**
     * Shipment constructor.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory $shipmentFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\ItemFactory $shipmentItemFactory
     * @param \Riki\Catalog\Helper\Data $catalogHelper
     * @param ShipmentApi $sapApi
     * @param \Riki\SapIntegration\Logger\ShipmentLogger $logger
     * @param \Riki\SapIntegration\Helper\Data $sapHelper
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Riki\Customer\Helper\Data $customerHelper
     * @param \Riki\TmpRma\Model\Config\Source\Rma\ReturnedWarehouse $returnedWarehouse
     * @param \Magento\Framework\App\Config $config
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\ItemFactory $shipmentItemFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $collectionFactory,
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Riki\SapIntegration\Model\Api\Shipment $sapApi,
        \Riki\SapIntegration\Logger\ShipmentLogger $logger,
        \Riki\SapIntegration\Helper\Data $sapHelper,
        \Riki\Sales\Helper\Order $orderHelper,
        \Riki\Customer\Helper\Data $customerHelper,
        \Riki\TmpRma\Model\Config\Source\Rma\ReturnedWarehouse $returnedWarehouse,
        \Magento\Framework\App\Config $config
    ) {
        $this->_shipmentFactory = $shipmentFactory;
        $this->_shipmentItemFactory = $shipmentItemFactory;
        $this->_catalogHelper = $catalogHelper;
        $this->_sapApi = $sapApi;
        $this->_logger = $logger;
        $this->_orderHelper = $orderHelper;
        $this->_sapHelper = $sapHelper;
        $this->_customerHelper = $customerHelper;
        $this->_shipmentCollectionFactory = $collectionFactory;
        $this->_rmaWarehouse = $returnedWarehouse;
        $this->_config = $config;
    }

    /**
     * Mapping from SAP field to Magento field
     *
     * @return array
     */
    protected function _getMapping()
    {
        $mappings = [
            'SapCustomerId' => 'sap_customer_id',
            'Warehouse' => 'warehouse',
            'DeliveryDate' => 'shipped_out_date',
            'ShipmentNumber' => 'increment_id',
            'FreeOfCharge' => 'free_of_charge',
            'SAPReasonCode' => 'reason_code',
            'SKU' => 'sku',
            'Quantity' => 'qty',
            'UnitEcom' => 'unit_case',
            'GpsPrice' => 'gps_price_ec',
            'SalesPrice' => 'price_incl_tax',
            'SalesOrganization' => 'sales_organization',
            'DistributionChannel' => 'distribution_channel',
            'FreeItem' => 'free_item',
            'MaterialType' => 'material_type',
            'BookingItemWbs' => 'booking_wbs',
            'TaxAmount' => 'tax_riki',
            'CommissionAmount' => 'commission_amount',
        ];
        return $mappings;
    }

    /**
     * Database will be split, should separate data getting instead of join table directly
     *
     * @param array $shipments
     * @return $this
     */
    public function _prepareRelationData($shipments)
    {
        $orderItemIds = $orderIds = [];
        foreach ($shipments as $shipment) {
            $orderItemIds[] = $shipment['order_item_id'];
            $orderIds[] = $shipment['order_id'];
        }
        $this->_orderItems = $this->_orderHelper->getOrderItemByIds($orderItemIds);
        $this->_orders = $this->_orderHelper->getOrderByIds($orderIds);
        $this->_warehouses = $this->_rmaWarehouse->getSAPCodes('store_code');
        return $this;
    }

    /**
     * Execute cron export shipment items data
     *
     * @return $this
     */
    public function execute()
    {
        if (!$this->_sapHelper->isShipmentEnable()) {
            return $this;
        }
        $this->_logger->info(__('Starting export shipment to SAP'));
        $this->_sapApi->setBatchType(ShipmentApi::BATCH_SHIPMENT);
        $this->exportShipmentData(ShipmentApi::BATCH_SHIPMENT);
        $this->exportShipmentData(ShipmentApi::BATCH_RMA);
        $this->_sapApi->sendExportedToSftp();
        $this->_logger->info(__('Total %1 SHIPMENT was exported successful to SAP', $this->_successRow));
        $this->_logger->info(__('Total %1 SHIPMENT was failed to export to SAP', $this->_failedRow));
    }

    /**
     * @param $subFilter
     * @return $this
     */
    public function exportShipmentData($subFilter)
    {
        try {
            /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Item $resourceModel */
            $resourceModel = $this->_shipmentItemFactory->create();
            $sqlSelect = $resourceModel->getConnection()->select()->from(
                ['main_table' => $resourceModel->getMainTable()],
                [
                    'product_id', 'sku', 'order_item_id', 'parent_id', 'qty', 'booking_wbs', 'free_of_charge',
                    'gps_price_ec', 'material_type', 'sales_organization', 'sap_interface_excluded', 'commission_amount'
                ]
            );
            $sqlSelect->join(
                ['shipment' => $resourceModel->getTable('sales_shipment')],
                'main_table.parent_id = shipment.entity_id',
                ['warehouse', 'delivery_date', 'increment_id', 'order_id', 'shipped_out_date', 'shipment_foc' => 'free_of_charge']
            );
            $sqlSelect->joinLeft(
                ['track' => $resourceModel->getTable('sales_shipment_track')],
                'shipment.entity_id = track.parent_id',
                ['carrier_code']
            );
            $sqlSelect->joinLeft(
                ['order' => $resourceModel->getTable('sales_order')],
                'shipment.order_id = order.entity_id',
                ['order.substitution', 'order.charge_type']
            );

            $statusToExport = [ShipmentApi::WAITING_FOR_EXPORT, ShipmentApi::FAILED_TO_EXPORT];
            $sqlSelect->where('shipment.is_exported_sap IN(?)', $statusToExport);
            $sqlSelect->where('main_table.sap_interface_excluded = ?', 1);
            if ($subFilter == ShipmentApi::BATCH_RMA) {
                $sqlSelect->where('order.substitution = ?', 1);
            } else {
                $sqlSelect->where('order.substitution is null OR order.substitution <> ?', 1);
            }
            $shipments = $resourceModel->getConnection()->fetchAll($sqlSelect);
            if (!sizeof($shipments)) {
                return $this;
            }
            $this->_prepareRelationData($shipments);
            $exportData = [];
            foreach ($shipments as $index => $shipment) {
                // Check allow material type
                $materialType = $shipment['material_type'];
                if (!in_array(strtoupper($materialType), ShipmentApi::MATERIAL_TYPE_ALLOWED)) {
                    continue;
                }
                // Filter bundle product not export item
                $orders = $this->_orderItems[$shipment['order_item_id']];
                if ($orders->getData('parent_item_id')) {
                    continue;
                }
                $exportData[] = $shipment;
            }
            $this->_exportSAP($exportData, $subFilter);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * Get field value from magento
     *
     * @param array $shipment
     * @param string $mageField
     * @return mixed
     */
    protected function _getFieldValue($shipment, $mageField)
    {
        if ($mageField == 'free_item') {
            $shipmentFoc = $shipment['shipment_foc'] == 1 ? 'X' : '0';
            return $shipmentFoc;
        }
        if ($mageField == 'free_of_charge') {
            $freeOfCharge = $shipment['free_of_charge'] == 1 ? 'X' : '';
            return $freeOfCharge;
        }
        if ($mageField == "unit_case") {
            return ShipmentApi::UNIT_ECOM_DEFAULT;
        }
        if ($mageField == "price_incl_tax") {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $this->_orderItems[$shipment['order_item_id']];
            return $orderItem->getData('row_total');
        }
        if ($mageField == 'reason_code') {
            if ($shipment['charge_type'] == 2) {
                $getValueSap = $this->_config->getValue(ShipmentApi::PATH_SAP_REASON_CODE_REPLACEMENT_ORDER);
                $reasonCode = !empty($getValueSap) ? $getValueSap : (ShipmentApi::SAP_REASON_CODE_DEFAULT);
                return $reasonCode;
            } else {
                return '';
            }
        }
        if ($mageField == 'sap_customer_id') {
            if (isset($this->_orders[$shipment['order_id']])) {
                $order = $this->_orders[$shipment['order_id']];
                $order->setData('carrier_code', $shipment['carrier_code']);
                return $this->_sapHelper->sapCustomerId($order);
            }
        }
        if ($mageField == 'distribution_channel') {
            $order = $this->_orders[$shipment['order_id']];
            $distributionChannel = $order->getData('customer_membership');
            if (!empty($distributionChannel)) {
                $arrDistributionChannel = explode(",", $distributionChannel);
                if (in_array(ShipmentApi::AMB_SALES_ID, $arrDistributionChannel)) {
                    return ShipmentApi::AMB_DISTRIBUTION_CHANNEL;
                } else {
                    return ShipmentApi::AMB_OTHER_DISTRIBUTION_CHANNEL;
                }
            } else {
                return ShipmentApi::AMB_OTHER_DISTRIBUTION_CHANNEL;
            }
        }
        if (in_array($mageField, self::$orderItemFields) && isset($this->_orderItems[$shipment['order_item_id']])) {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $this->_orderItems[$shipment['order_item_id']];
            return $orderItem->getData($mageField);
        }
        if ($mageField == 'warehouse' && $shipment[$mageField] && isset($this->_warehouses[$shipment[$mageField]])) {
            return $this->_warehouses[$shipment[$mageField]];
        }
        if (isset($shipment[$mageField])) {
            $value = $shipment[$mageField];
            switch ($mageField) {
                case 'shipped_out_date':
                    return date('Ymd', strtotime($value));
                default:
                    return $value;
            }
        }
        return null;
    }

    /**
     * Export shipments to SAP
     *
     * @param array $exportData
     * @param string $type
     * @return bool
     */
    protected function _exportSAP($exportData, $type)
    {
        if (!is_array($exportData) || !sizeof($exportData)) {
            return false;
        }
        $params = [];
        $params[] = new \SoapVar($type, XSD_STRING, null, null, 'ShipmentBatchType');
        foreach ($exportData as $shipment) {
            $mageShipmentField = [];
            foreach ($this->_getMapping() as $sapField => $mageField) {
                $value = $this->_getFieldValue($shipment, $mageField);
                $mageShipmentField[] = new \SoapVar($value, XSD_STRING, null, null, $sapField);
            }
            $mageShipment = new \SoapVar($mageShipmentField, SOAP_ENC_OBJECT, null, null, 'MagentoShipment');
            $params[] = new \SoapVar($mageShipment, SOAP_ENC_ARRAY);
        }
        $soapVars = new \SoapVar($params, SOAP_ENC_OBJECT);
        //$response = $this->_sapApi->exportToSAP($soapVars);
        $filename = 'sap_shipment_'.date('YmdHis').'_'.strtolower($type).'.xml';
        $response = $this->_sapApi->exportToXML($soapVars, $filename);
        if (!$response['error']) {
            $this->_successRow += $this->_afterExportSAP($exportData, ShipmentApi::EXPORTED_TO_SAP);
        } else {
            $this->_failedRow += $this->_afterExportSAP($exportData, ShipmentApi::FAILED_TO_EXPORT);
        }
    }

    /**
     * Set flag exported after sending successful or failed
     *
     * @param array $exportData
     * @param int $status
     * @return int
     */
    protected function _afterExportSAP($exportData, $status)
    {
        $entityIds = array_map(function($shipment) {
            return $shipment['parent_id'];
        }, $exportData);
        $collection = $this->_shipmentCollectionFactory->create();
        $collection->addAttributeToFilter('entity_id', ['in' => $entityIds]);
        if ($total = $collection->getSize()) {
            foreach ($collection as $item) {
                $item->setData('is_exported_sap', $status);
                $item->setData('export_sap_date', date('Y-m-d H:i:s'));
                $collection->getResource()->save($item);
            }
        }
        return $total;
    }
}