<?php

namespace Riki\SapIntegration\Cron;

use Magento\Rma\Model\ResourceModel\Rma\CollectionFactory;
use Riki\SapIntegration\Model\Api\Shipment as ShipmentApi;

class Rma
{
    /**
     * @var \Magento\Rma\Model\ResourceModel\Item\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var CollectionFactory
     */
    protected $_rmaCollectionFactory;

    /**
     * @var \Riki\SapIntegration\Model\Api\Shipment
     */
    protected $_sapApi;

    /**
     * @var \Magento\Rma\Model\ResourceModel\RmaFactory
     */
    protected $_rmaFactory;

    /**
     * @var \Riki\SapIntegration\Logger\RmaLogger
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
     * @var array
     */
    protected $_warehouses;

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
    public static $orderItemFields = ['tax_amount', 'commission_amount'];

    /**
     * Rma constructor.
     *
     * @param \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $collectionFactory
     * @param \Magento\Rma\Model\ResourceModel\RmaFactory $rmaFactory
     * @param \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory
     * @param \Riki\Catalog\Helper\Data $catalogHelper
     * @param ShipmentApi $sapApi
     * @param \Riki\SapIntegration\Logger\RmaLogger $logger
     * @param \Riki\SapIntegration\Helper\Data $sapHelper
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Riki\Customer\Helper\Data $customerHelper
     * @param \Riki\TmpRma\Model\Config\Source\Rma\ReturnedWarehouse $returnedWarehouse
     */
    public function __construct(
        \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $collectionFactory,
        \Magento\Rma\Model\ResourceModel\RmaFactory $rmaFactory,
        \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory,
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Riki\SapIntegration\Model\Api\Shipment $sapApi,
        \Riki\SapIntegration\Logger\RmaLogger $logger,
        \Riki\SapIntegration\Helper\Data $sapHelper,
        \Riki\Sales\Helper\Order $orderHelper,
        \Riki\Customer\Helper\Data $customerHelper,
        \Riki\TmpRma\Model\Config\Source\Rma\ReturnedWarehouse $returnedWarehouse
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_rmaFactory = $rmaFactory;
        $this->_catalogHelper = $catalogHelper;
        $this->_sapApi = $sapApi;
        $this->_logger = $logger;
        $this->_orderHelper = $orderHelper;
        $this->_sapHelper = $sapHelper;
        $this->_customerHelper = $customerHelper;
        $this->_rmaWarehouse = $returnedWarehouse;
        $this->_rmaCollectionFactory = $rmaCollectionFactory;
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
            'Warehouse' => 'returned_warehouse',
            'DeliveryDate' => 'return_approval_date',
            'ShipmentNumber' => 'increment_id',
            'FreeOfCharge' => 'free_of_charge',
            'SAPReasonCode' => 'reason_code',
            'SKU' => 'product_sku',
            'Quantity' => 'qty_requested',
            'UnitEcom' => 'unit_case',
            'GpsPrice' => 'gps_price_ec',
            'SalesPrice' => 'sales_price',
            'SalesOrganization' => 'sales_organization',
            'DistributionChannel' => 'distribution_channel',
            'FreeItem' => 'free_of_charge',
            'MaterialType' => 'material_type',
            'BookingItemWbs' => 'booking_wbs',
            'TaxAmount' => 'tax_amount',
            'CommissionAmount' => 'commission_amount'
        ];
        return $mappings;
    }

    /**
     * Database will be split, should separate data getting instead of join table directly
     *
     * @param array $rmaItems
     * @return $this
     */
    public function _prepareRelationData($rmaItems)
    {
        $orderItemIds = $orderIds = [];
        foreach ($rmaItems as $rma) {
            $orderItemIds[] = $rma['order_item_id'];
            $orderIds[] = $rma['order_id'];
        }
        $this->_orderItems = $this->_orderHelper->getOrderItemByIds($orderItemIds);
        $this->_orders = $this->_orderHelper->getOrderByIds($orderIds);
        $this->_warehouses = $this->_rmaWarehouse->getSAPCodes();
        return $this;
    }

    /**
     * Execute cron export shipment items data
     *
     * @return $this
     */
    public function execute()
    {
        try {
            if (!$this->_sapHelper->isRmaEnable()) {
                return $this;
            }
            $this->_sapApi->setBatchType(ShipmentApi::BATCH_RMA);
            $this->_logger->info(__('Starting export RMA to SAP'));

            /** @var \Magento\Rma\Model\ResourceModel\Item\Collection $collection */
            $collection = $this->_collectionFactory->create();
            $collection->addAttributeToSelect('*');

            $resourceModel = $collection->getResource();

            $collection->getSelect()->join(
                ['rma' => $resourceModel->getTable('magento_rma')],
                'e.rma_entity_id = rma.entity_id',
                ['increment_id', 'order_id', 'returned_warehouse', 'return_approval_date']
            );

            $collection->getSelect()->joinLeft(
                ['reason' => $resourceModel->getTable('riki_rma_reason')],
                'rma.reason_id = reason.id',
                ['reason_code' => 'reason.sap_code']
            );

            $collection->getSelect()->joinLeft(
                ['tracking' => $resourceModel->getTable('magento_rma_shipping_label')],
                'tracking.rma_entity_id = rma.entity_id',
                ['carrier_code']
            );

            $statusToExport = [ShipmentApi::WAITING_FOR_EXPORT, ShipmentApi::FAILED_TO_EXPORT];
            $collection->getSelect()->where('rma.is_exported_sap IN(?)', $statusToExport);
            $collection->getSelect()->where('e.sap_interface_excluded = ?', 1);
            if (!$collection->getSize()) {
                $this->_logger->info(__('No RMA item found to export SAP'));
                return $this;
            }

            $rmaItems = $collection->getData();
            $this->_prepareRelationData($rmaItems);
            $exportData = [];
            $this->_sapApi->setBatchType(ShipmentApi::BATCH_RMA);
            foreach ($rmaItems as $index => $rma) {
                if (!isset($this->_orderItems[$rma['order_item_id']])) {
                    continue;
                }

                // Export material type allowed
                $materialType = $rma['material_type'];
                if (!in_array(strtoupper($materialType), ShipmentApi::MATERIAL_TYPE_ALLOWED)) {
                    continue;
                }

                // Do not export bundle product item
                $orders = $this->_orderItems[$rma['order_item_id']];
                if ($orders->getData('parent_item_id')) {
                    continue;
                }
                $exportData[] = $rma;
            }
            $this->_exportSAP($exportData);
            $this->_sapApi->sendExportedToSftp();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        $this->_logger->info(__('Total %1 RMA was exported successful to SAP', $this->_successRow));
        $this->_logger->info(__('Total %1 RMA was failed to export to SAP', $this->_failedRow));
        return $this;
    }

    /**
     * Get field value from magento
     *
     * @param array $rmaItem
     * @param string $mageField
     * @return mixed
     */
    protected function _getFieldValue($rmaItem, $mageField)
    {
        if ($mageField == 'sap_customer_id') {
            if (isset($this->_orders[$rmaItem['order_id']])) {
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->_orders[$rmaItem['order_id']];
                $order->setData('carrier_code', $rmaItem['carrier_code']);
                return $this->_sapHelper->sapCustomerId($order);
            }
        }
        if ($mageField == 'unit_case') {
            return ShipmentApi::UNIT_ECOM_DEFAULT;
        }
        if ($mageField == 'sales_price') {
            return $rmaItem['return_amount'] + $rmaItem['return_amount_adj'];
        }
        if ($mageField == 'returned_warehouse' && $rmaItem[$mageField] && isset($this->_warehouses[$rmaItem[$mageField]])) {
            return $this->_warehouses[$rmaItem[$mageField]];
        }
        if ($mageField == 'free_of_charge') {
            $freeOfCharge = ($rmaItem['free_of_charge'] == 1) ? 'X' : '';
            return $freeOfCharge;
        }
        if ($mageField == 'distribution_channel') {
            $order = $this->_orders[$rmaItem['order_id']];
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
        if (in_array($mageField, self::$orderItemFields) && isset($this->_orderItems[$rmaItem['order_item_id']])) {
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $this->_orderItems[$rmaItem['order_item_id']];
            return $orderItem->getData($mageField);
        }
        if (isset($rmaItem[$mageField])) {
            $value = $rmaItem[$mageField];
            switch ($mageField) {
                case 'warehouse':
                    return $this->_sapHelper->getWareHouse($value);
                case 'return_approval_date':
                    return date('Ymd', strtotime($value));
                default:
                    return $value;
            }
        }
        return null;
    }

    /**
     * Export RMA to SAP
     *
     * @param array $exportData
     * @return boolean
     */
    protected function _exportSAP($exportData)
    {
        if (!is_array($exportData) || !sizeof($exportData)) {
            return false;
        }
        $params = [];
        $params[] = new \SoapVar(ShipmentApi::BATCH_RMA, XSD_STRING, null, null, 'ShipmentBatchType');
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
        $filename = 'sap_rma_'.date('YmdHis').'.xml';
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
        $entityIds = array_map(function ($rmaItem) {
            return $rmaItem['rma_entity_id'];
        }, $exportData);
        $collection = $this->_rmaCollectionFactory->create();
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
