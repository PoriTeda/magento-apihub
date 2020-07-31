<?php
namespace Riki\SapIntegration\Cron\Exporter;

use Riki\SapIntegration\Api\ConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Orders
{
    /**
     * @var int
     */
    protected $batchLimit;

    /**
     * @var array
     */
    protected $batchData;

    /**
     * @var array
     */
    protected $batchIds;

    /**
     * @var int
     */
    protected $batchCount;

    /**
     * @var mixed
     */
    protected $wsdl;

    /**
     * @var string
     */
    protected $directory;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var \Riki\SapIntegration\Webapi\TransferFactory
     */
    protected $transferFactory;

    /**
     * @var \Riki\SapIntegration\Webapi\Transfer
     */
    protected $transferClient;

    /**
     * @var \Magento\Framework\Setup\SampleData\FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var \Riki\SapIntegration\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Sales\Api\ShipmentItemRepositoryInterface
     */
    protected $shipmentItemRepository;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /*flag to count item commission amount and deduct for shipment item*/
    protected $_itemCommissionAmount;

    /*create logger via setlogger function*/
    protected $_logger;

    protected $_soapApi;

    /**
     * list backup file
     *
     * @var array
     */
    protected $backupFile = [];

    /**
     * Orders constructor.
     *
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Setup\SampleData\FixtureManager $fixtureManager
     * @param \Riki\SapIntegration\Webapi\TransferFactory $transferFactory
     * @param \Riki\SapIntegration\Helper\Data $dataHelper
     * @param \Magento\Sales\Api\ShipmentItemRepositoryInterface $shipmentItemRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Riki\Sales\Helper\Order $orderHelper
     */
    public function __construct(
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Setup\SampleData\FixtureManager $fixtureManager,
        \Riki\SapIntegration\Webapi\TransferFactory $transferFactory,
        \Riki\SapIntegration\Helper\Data $dataHelper,
        \Magento\Sales\Api\ShipmentItemRepositoryInterface $shipmentItemRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Riki\SapIntegration\Model\Api\Shipment $soapApi,
        \Riki\Sales\Helper\Order $orderHelper
    ) {
        $this->datetimeHelper = $datetimeHelper;
        $this->filesystem = $filesystem;
        $this->fixtureManager = $fixtureManager;
        $this->transferFactory = $transferFactory;
        $this->dataHelper = $dataHelper;
        $this->shipmentItemRepository = $shipmentItemRepository;
        $this->searchHelper = $searchHelper;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->orderHelper = $orderHelper;
        $this->_soapApi = $soapApi;

        $this->init();
    }

    /**
     * Set logger
     *
     * @param $logger
     */
    public function setLogger($logger)
    {
        if (!$this->_logger) {
            $this->_logger = $logger;
        }
    }

    /**
     * Add message to log file
     *
     * @param $message
     */
    public function addLogMessage($message)
    {
        if ($this->_logger) {
            $this->_logger->info($message);
        }
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init()
    {
        $batchLimit = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->exportShipment()
            ->batchLimit();
        $this->batchLimit = $batchLimit ?: 10;
        $this->wsdl = $this->fixtureManager->getFixture('Riki_SapIntegration::fixtures/wsdl/Order_RRQ.wsdl');
        $this->transferClient = $this->transferFactory->create($this->wsdl, [
            'soap_version' => SOAP_1_1
        ]);
        $this->clean();
    }

    /**
     * Clean resource
     *
     * @return void
     */
    public function clean()
    {
        if ($this->fileName) {
            $this->exportToXml();
        }

        /*reset exported Data*/
        $this->resetExportedData();

        $localDirectory = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->exportShipment()
            ->local();
        $localDirectory = $localDirectory ? $localDirectory : 'Riki_SapIntegration/Cron/Shipment';
        $localDirectory = $localDirectory . DIRECTORY_SEPARATOR . $this->datetimeHelper->getToday()->format('Y-m-d');
        $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->create($localDirectory);
        $this->directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->getAbsolutePath($localDirectory);
        $this->fileName = 'ORDERS_' . $this->datetimeHelper->getToday()->format('Y-m-d_H-i-s') . '.xml';
    }

    /**
     *
     */
    public function isExceeded(){
        return $this->batchCount >= $this->batchLimit;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     *
     * @return string|null
     */
    public function export(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $this->prepareShipmentExportData($shipment);

        if ($this->isExceeded()) {
            return $this->exportToXml();
        }

        return null;
    }

    /**
     * Prepare data before export to SAP
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return $this
     */
    public function prepareShipmentExportData(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $shipmentItems = $this->searchHelper
            ->getByMaterialType(['FERT', 'HALB', 'UNBW', 'DIEN'], 'in')
            ->getByParentId($shipment->getId())
            ->getAll()
            ->execute($this->shipmentItemRepository);

        /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
        foreach ($shipmentItems as $shipmentItem) {

            /*get related order item data*/
            $orderItem = $this->dataHelper->getOrderItem($shipmentItem);

            /*get related order data*/
            $order = $this->dataHelper->getOrder($shipment);

            if (!$orderItem || !$order) {
                continue;
            }

            /*store commission amount of this item for global variable*/
            if (!isset($this->_itemCommissionAmount[$orderItem->getId()])) {
                $this->_itemCommissionAmount[$orderItem->getId()] = intval($orderItem->getData('commission_amount'));
            }

            /*free of charge columns, get from original order*/
            $freeOfCharge = $order->getData('free_of_charge') ? 1 : 0;

            /*order charge type { 1: normal, 2: free of charge - replacement, 3: free of charge sample} */
            $orderChargeType = $order->getData('charge_type');

            /*flag to check this item is free attachment, default value is 1 if original order is free of charge*/
            $freeItem = 1;

            /*booking item wbs, get from original order, default value is free_samples_wbs column from original data if this order is free of charge*/
            $bookingItemWbs = $order->getData('free_samples_wbs');

            /*not free of charge order*/
            if (!$freeOfCharge) {
                /*free of charge - replacement*/
                if ($orderChargeType == \Riki\Sales\Model\Config\Source\OrderType::ORDER_TYPE_REPLACEMENT) {
                    /*free item is always 0 for this case*/
                    $freeItem = 0;
                    /*empty wbs for this case*/
                    $bookingItemWbs = false;
                } else {
                    /*check this item is free item - free product attachment*/
                    $freeItem = $this->orderHelper->isFreeItem($orderItem) ? 1 : 0;
                    /*get booking wbs from shipment item*/
                    $bookingItemWbs = $this->orderHelper->getOrderItemWbsForSap($orderItem);
                }
            }

            /*convert wbs for SAP exported*/
            $bookingItemWbs = $this->dataHelper->convertWbsForSapExported($bookingItemWbs);

            if ($orderItem->getProductType() != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE &&
                $shipmentItem->getData('sap_interface_excluded') == 1
            ) {
                continue;
            }

            if ($orderItem->getProductType() != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                /*push shipment item data to export array*/
                $this->batchData[] = $this->getShipmentExportSapData($shipment, $shipmentItem, $orderItem, $freeOfCharge, $freeItem, $bookingItemWbs, $shipmentItem->getData('qty'));
            } else {
                /*get bundle item shipment type*/
                $bundleShipSeparately = $this->orderHelper->isBundleItemShipSeparately($orderItem);

                if ($bundleShipSeparately) {
                    /*if bundle shipment type is separately, need to get children item from sales order item because default magento do not store children item for sales shipment item for this case*/
                    $childrenItem = $this->orderHelper->getChildrenItemById($orderItem->getId());

                    /*shipment qty for this item*/
                    $shipmentQty = $shipmentItem->getData('qty');

                    if ($childrenItem) {
                        foreach ($childrenItem as $childItem) {
                            /*push shipment item data to export array*/
                            if($this->orderHelper->checkSapInterfaceExcluded($childItem->getData('product_id')))
                                continue;
                            $this->batchData[] = $this->getShipmentExportSapData(
                                $shipment, $shipmentItem, $childItem, $freeOfCharge, $freeItem, $bookingItemWbs,
                                $this->orderHelper->getOrderItemQtyForShipmentQty($childItem, $shipmentQty)
                            );
                        }
                    }
                }
            }
        }

        $this->batchIds[$shipment->getId()] = 1;
        $this->batchCount++;

        return $this;
    }

    /**
     * Export to XML
     *
     * @return string|null
     */
    public function exportToXml()
    {
        if ($this->batchData) {
            $params = [];
            $params[] = new \SoapVar('ORDERS', XSD_STRING, null, null, 'ShipmentBatchType');
            foreach ($this->batchData as $shipmentData) {
                $soapShipment = [];
                foreach ($shipmentData as $field => $value) {
                    $soapShipment[] = new \SoapVar($value, XSD_STRING, null, null, $field);
                }
                $soapShipmentVar = new \SoapVar($soapShipment, SOAP_ENC_OBJECT, null, null, 'MagentoShipment');
                $params[] = new \SoapVar($soapShipmentVar, SOAP_ENC_ARRAY);
            }

            /*return xml request, then plugin will push it to api*/
            return $this->convertSoapVarToXml(new \SoapVar($params, SOAP_ENC_OBJECT));
        }

        return null;
    }

    /**
     * Send request to SAP
     *
     * @return bool
     */
    public function exportShipmentToSap()
    {
        $result = true;

        $xmlData = $this->exportToXml();

        if (!is_null($xmlData)) {
            if ($xmlData) {

                $this->addLogMessage('Send request to SAP');
                /*export xml request to sap*/
                $exportToSap = $this->_soapApi->exportToSapByXmlRequest($xmlData);

                /*cannot export to SAP, tracking error id and revert data*/
                if (!empty($exportToSap) && $exportToSap['error'] == true) {
                    $this->addLogMessage('Send request to SAP failed');
                    $result = false;
                } else {
                    $this->addLogMessage('Send request to SAP success');
                }

                /*after export to SAP create backup data - xml file*/
                $this->createBackupFile($xmlData, $result);

                /*reset exported data*/
                $this->resetExportedData();
            } else {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Create backup file after run export to SAP
     *
     * @param $xmlData
     * @param $exportStatus
     */
    public function createBackupFile($xmlData, $exportStatus)
    {
        /*export to SAP failed*/
        if (!$exportStatus) {
            return;
        }

        $this->createBackupXml($xmlData);
    }

    /**
     * Create backup file after export to SAP success
     *
     * @param $xmlRequest
     */
    public function createBackupXml($xmlRequest)
    {
        try {
            $xml = new \SimpleXMLElement($xmlRequest);
            if ($xml->saveXML($this->getExportFile())) {
                $this->addLogMessage('Create backup file success.');
                $this->addBackupFile($this->getFileName());
            } else {
                $this->addLogMessage('Create backup file failed.');
            }
        } catch (\Exception $e) {
            $this->addLogMessage($e->getMessage());
        }
    }

    /**
     * add backup file to list
     *
     * @param $backupFile
     */
    public function addBackupFile($backupFile)
    {
        array_push($this->backupFile, $backupFile);
    }

    /**
     * get backup file
     *
     * @return string
     */
    public function getBackupFile()
    {
        return $this->backupFile;
    }

    /**
     * Reset export data to ensure this data does not send to SAP twice
     */
    public function resetExportedData()
    {
        /*reset count data*/
        $this->batchCount = 0;
        /*reset export data*/
        $this->batchData = [];
        /*reset list( array) export id*/
        $this->batchIds = [];
    }

    /**
     * Convert SOAP vars to xml
     *
     * @param \SoapVar $soapVar
     * @return string
     */
    protected function convertSoapVarToXml(\SoapVar $soapVar)
    {
        $this->transferClient->MI_DEVWR0035542_MagentoShipment($soapVar);
        return $this->transferClient->getXmlRequest();
    }

    /**
     * Get exported file
     *
     * @return string
     */
    public function getExportFile()
    {
        return $this->directory . DIRECTORY_SEPARATOR . $this->fileName;
    }

    /**
     * Get file name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Get ids in current batch
     *
     * @return array
     */
    public function getBatchIds()
    {
        return $this->batchIds;
    }

    /**
     * Shipment data will be exported to SAP
     *
     * @param $shipment
     * @param $shipmentItem
     * @param $orderItem
     * @param $freeOfCharge
     * @param $freeItem
     * @param $bookingItemWbs
     * @param $itemQty
     * @return array
     */
    public function getShipmentExportSapData($shipment, $shipmentItem, $orderItem, $freeOfCharge, $freeItem, $bookingItemWbs, $itemQty)
    {
        $data = [];
        $data['SapCustomerId'] = $this->dataHelper->getCustomerSapCode($shipment);
        $data['Warehouse'] = $this->dataHelper->getWh($shipment);
        $data['DeliveryDate'] = !empty($shipment->getData('shipped_out_date')) ? $this->datetimeHelper->formatDatetime($shipment->getData('shipped_out_date'), 'Ymd') : false;
        $data['ShipmentNumber'] = $shipment->getData('increment_id');
        $data['FreeOfCharge'] = $freeOfCharge;
        $data['SAPReasonCode'] = $this->dataHelper->getReasonCode($shipment);

        $data['SKU'] = $orderItem->getData('sku');
        $data['Quantity'] = $itemQty;

        $data['UnitEcom'] = $this->dataHelper->getUnitEcom($shipment);
        $productId = $shipmentItem->getData('product_id');
        $storeId = $shipment->getData('store_id');
        if($productId){
            $data['GpsPrice'] = $this->orderHelper->getProductGpsPriceById($productId,$storeId);
        }

        /*shipment item, after discount, excl tax*/
        $salesPrice = $this->orderHelper->getOrderItemBaseTotalAmount($orderItem, $itemQty);

        $data['SalesPrice'] = $salesPrice;

        $data['SalesOrganization'] = $shipmentItem->getData('sales_organization');
        $data['DistributionChannel'] = $this->dataHelper->getDistributeChannel($shipmentItem, $orderItem);
        $data['FreeItem'] = $freeItem;
        $data['MaterialType'] = $shipmentItem->getData('material_type');
        $data['BookingItemWbs'] = $bookingItemWbs;
        $data['TaxAmount'] = $this->orderHelper->getTaxAmountForEachOrderItemByQty($orderItem, $itemQty);
        $data['CommissionAmount'] = $this->_getShipmentItemCommissionAmount($salesPrice, $orderItem, $itemQty);
        $data['TaxCode'] = $this->dataHelper->getTaxCode($shipment, $orderItem);
        return $data;
    }

    /**
     * Get shipment item commission amount
     *
     * @param $shipmentTotal
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param $itemQty
     * @return float|int
     */
    private function _getShipmentItemCommissionAmount(
        $shipmentTotal,
        \Magento\Sales\Model\Order\Item $orderItem,
        $itemQty
    ) {
        /*bundle product*/
        if (!empty($orderItem->getParentItemId())) {
            return $this->orderHelper->getCommissionAmountForBundleChildrenItem($orderItem, $itemQty);
        }

        /*item id will be deduct commission amount*/
        $deductItemId = $orderItem->getId();

        /*get item total commission amount from global variable*/
        $itemCommissionAmount = $this->_itemCommissionAmount[$deductItemId];

        if ($itemCommissionAmount > 0) {
            /*recalculate commission amount for shipment item*/
            $shipmentItemCommissionAmount = $this->orderHelper->getCommissionAmountForShipmentItem(
                $shipmentTotal,
                $orderItem
            );

            if ($shipmentItemCommissionAmount >= $itemCommissionAmount) {
                /*commission amount of shipment item is remaining value from order item's commission amount*/
                $shipmentItemCommissionAmount = $itemCommissionAmount;

                /*set commission amount = 0 for this item */
                $this->_itemCommissionAmount[$deductItemId] = 0;
            } else {
                /*deduct order item commission amount by shipment item's commission amount*/
                $this->_itemCommissionAmount[$deductItemId] -= $shipmentItemCommissionAmount;
            }
            return $shipmentItemCommissionAmount;
        }

        return 0;
    }
}
