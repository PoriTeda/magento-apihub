<?php
namespace Riki\SapIntegration\Cron\Exporter;

use Riki\SapIntegration\Api\ConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Returns
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
     * @var \Magento\Sales\Api\ShipmentItemRepositoryInterface
     */
    protected $rmaItemRepository;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /*create logger via setlogger function*/
    protected $_logger;

    /**
     * Returns constructor.
     *
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Setup\SampleData\FixtureManager $fixtureManager
     * @param \Riki\SapIntegration\Webapi\TransferFactory $transferFactory
     * @param \Riki\SapIntegration\Helper\Data $dataHelper
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Riki\Sales\Helper\Order
     */
    public function __construct(
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Setup\SampleData\FixtureManager $fixtureManager,
        \Riki\SapIntegration\Webapi\TransferFactory $transferFactory,
        \Riki\SapIntegration\Helper\Data $dataHelper,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Riki\Sales\Helper\Order $orderHelper
    ) {
        $this->datetimeHelper = $datetimeHelper;
        $this->filesystem = $filesystem;
        $this->fixtureManager = $fixtureManager;
        $this->transferFactory = $transferFactory;
        $this->dataHelper = $dataHelper;
        $this->rmaItemRepository = $rmaItemRepository;
        $this->searchHelper = $searchHelper;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->orderHelper = $orderHelper;

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
            ->exportRma()
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

        /*reset exported data*/
        $this->resetExportedData();

        $localDirectory = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->sapIntegration()
            ->exportRma()
            ->local();
        $localDirectory = $localDirectory ? $localDirectory : 'Riki_SapIntegration/Cron/Shipment';
        $localDirectory = $localDirectory . DIRECTORY_SEPARATOR . $this->datetimeHelper->getToday()->format('Y-m-d');
        $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->create($localDirectory);
        $this->directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->getAbsolutePath($localDirectory);
        $this->fileName = 'RETURNS_' . $this->datetimeHelper->getToday()->format('Y-m-d_H-i-s') . '.xml';
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return string|null
     */
    public function export(\Magento\Rma\Model\Rma $rma)
    {
        $data = [];
        $rmaItems = $this->searchHelper
            ->getBySapInterfaceExcluded(0)
            ->getByMaterialType(['FERT', 'HALB', 'UNBW', 'DIEN'], 'in')
            ->getByRmaEntityId($rma->getId())
            ->getAll()
            ->execute($this->rmaItemRepository);
        /** @var \Magento\Rma\Model\Item $rmaItem */
        foreach ($rmaItems as $rmaItem) {
            /*get related order item data*/
            $orderItem = $this->dataHelper->getOrderItem($rmaItem);

            /*get related order data*/
            $order = $this->dataHelper->getOrder($rma);

            // phpcs:ignore MEQP1.Performance.InefficientMethods,MEQP1.Performance.Loop
            $shipment = $order->getShipmentsCollection()->getFirstItem();

            if (!$orderItem || !$order) {
                continue;
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
                    /*free of charge is always 0 for this case*/
                    $freeOfCharge = 0;
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

            /*return sales price - excl tax*/
            $itemSalesPrice = floatval($rmaItem->getData('return_amount_excl_tax')) + floatval($rmaItem->getData('return_amount_adj_excl_tax'));

            $data['SapCustomerId'] = $this->dataHelper->getCustomerSapCode($rma);
            $data['Warehouse'] = $this->dataHelper->getWh($rma);
            $data['DeliveryDate'] = !empty($rma->getData('approval_date')) ? $this->datetimeHelper->formatDatetime($rma->getData('approval_date'), 'Ymd') : false;
            $data['ShipmentNumber'] = $rma->getData('increment_id');
            $data['FreeOfCharge'] = $freeOfCharge;
            $data['SAPReasonCode'] = $this->dataHelper->getReasonCode($rma);
            $data['SKU'] = $rmaItem->getData('product_sku');
            $data['Quantity'] = $rmaItem->getData('qty_returned');
            $data['UnitEcom'] = $this->dataHelper->getUnitEcom($rma);
            $data['GpsPrice'] = $this->orderHelper->getItemGpsPriceById($rmaItem->getOrderItemId());
            $data['SalesPrice'] = $itemSalesPrice;
            $data['SalesOrganization'] = $rmaItem->getData('sales_organization');
            $data['DistributionChannel'] = $this->dataHelper->getDistributeChannel($rmaItem, $orderItem);
            $data['FreeItem'] = $freeItem;
            $data['MaterialType'] = $rmaItem->getData('material_type');
            $data['BookingItemWbs'] = $bookingItemWbs;
            $data['TaxAmount'] = $this->getTaxAmountForReturnItem($rmaItem, $orderItem);
            $data['CommissionAmount'] = (int) $rmaItem->getData('commission_amount');
            $data['TaxCode'] = $this->dataHelper->getTaxCode($shipment, $orderItem);
            $this->batchData[] = $data;
        }
        $this->batchIds[$rma->getId()] = 1;
        $this->batchCount++;

        if ($this->batchCount >= $this->batchLimit) {
            return $this->exportToXml();
        }

        return null;
    }

    /**
     * get tax amount for return item
     *      after deduct commission tax
     *
     * @param $rmaItem
     * @param $orderItem
     * @return float
     */
    public function getTaxAmountForReturnItem($rmaItem, $orderItem)
    {
        /*return commission amount*/
        $returnCommissionAmount = floatval($rmaItem->getData('commission_amount'));

        if ($returnCommissionAmount > 0) {

            /*return amount excl tax, after discount*/
            $returnAmountExclTax = floatval($rmaItem->getData('return_amount_excl_tax')) + floatval($rmaItem->getData('return_amount_adj_excl_tax'));

            /*order item tax percent*/
            $taxPerCent = $orderItem->getTaxPercent();

            if (!empty($orderItem->getParentItemId())) {
                $orderParentItem = $this->orderHelper->getOrderItemById($orderItem->getParentItemId());
                if ($orderParentItem && $orderParentItem->getTaxPercent()) {
                    $taxPerCent = $orderParentItem->getTaxPercent();
                }
            }

            if ($taxPerCent > 0) {
                /*round down  */
                return floor(
                    ($returnAmountExclTax - $returnCommissionAmount) * $taxPerCent / 100
                );
            }
        }

        /*item tax amount*/
        return floatval($rmaItem->getData('return_tax_amount')) + floatval($rmaItem->getData('return_tax_amount_adj'));
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
            $params[] = new \SoapVar('RETURNS', XSD_STRING, null, null, 'ShipmentBatchType');
            foreach ($this->batchData as $shipmentData) {
                $soapShipment = [];
                foreach ($shipmentData as $field => $value) {
                    $soapShipment[] = new \SoapVar($value, XSD_STRING, null, null, $field);
                }
                $soapShipmentVar = new \SoapVar($soapShipment, SOAP_ENC_OBJECT, null, null, 'MagentoShipment');
                $params[] = new \SoapVar($soapShipmentVar, SOAP_ENC_ARRAY);
            }

            /*convert soap var to xml request*/
            return $this->convertSoapVarToXml(new \SoapVar($params, SOAP_ENC_OBJECT));
        }

        return null;
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
            } else {
                $this->addLogMessage('Create backup file failed.');
            }
        } catch (\Exception $e) {
            $this->addLogMessage($e->getMessage());
        }
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
}
