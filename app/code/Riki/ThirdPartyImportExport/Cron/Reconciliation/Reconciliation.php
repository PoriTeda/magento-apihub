<?php
namespace Riki\ThirdPartyImportExport\Cron\Reconciliation;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;
use Riki\ThirdPartyImportExport\Helper\ExportBi\ShipmentHelper;

class Reconciliation
{
    const DEFAULT_LOCAL_SAVE = 'var/reconciliation';
    const SHIPMENT_TYPE = [
        'sales' => '00',
        'return' => '10'
    ];
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Reconciliation\GlobalHelper
     */
    protected $_dataHelper;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\LoggerCSV
     */
    protected $_log;
    /**
     * @var DateTime
     */
    protected $_datetime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;
    /**
     * @var string
     */
    protected $_path;
    /**
     * @var string
     */
    protected $pathTmp;
    /**
     * @var File
     */
    protected $_file;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $collectionShipmentFactory;
    /**
     * @var
     */
    protected $collectionCustomerFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $collectionOrderFactory;
    /**
     * @var \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory
     */
    protected $collectionShoshaFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory
     */
    protected $collectionItemFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $collectionOrderItemFactory;

    /**
     * @var \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory
     */
    protected $collectionRmaFactory;

    /**
     * @var \Magento\Rma\Model\RmaFactory
     */
    protected $rmaFactory;

    /**
     * @var \Magento\Rma\Model\ResourceModel\Item\CollectionFactory
     */
    protected $collectionRmaItemFactory;

    /**
     * @var ShipmentHelper
     */
    protected $biExportShipmentHelper;

    /**
     * @var \Riki\Shipment\Model\ResourceModel\Status\Shipment\CollectionFactory
     */
    protected $shipmentHistory;
    /**
     * @var \Riki\Customer\Model\Config\Source\StoreCode
     */
    protected $_storeCode;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper
     */
    protected $bundleItemsHelper;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connectionSales;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * @var array
     */
    protected $idsOrder = [];
    protected $idsShipment = [];
    protected $idsOrderRma = [];
    protected $idsRma = [];

    /**
     * Reconciliation constructor.
     * @param \Riki\ThirdPartyImportExport\Helper\Reconciliation\GlobalHelper $dataHelper
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Riki\ThirdPartyImportExport\Logger\Reconciliation\LoggerCSV $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param File $file
     * @param DateTime $datetime
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionCustomerFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionOrderFactory
     * @param \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $collectionShoshaFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory $collectionItemFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $collectionOrderItemFactory
     * @param \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $collectionRmaFactory
     * @param \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $collectionRmaItemFactory
     * @param \Magento\Rma\Model\RmaFactory $rmaFactory
     * @param ShipmentHelper $biExportShipmentHelper
     * @param \Riki\Shipment\Model\ResourceModel\Status\Shipment\CollectionFactory $shipment
     * @param \Riki\Customer\Model\Config\Source\StoreCode $storeCode
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\Reconciliation\GlobalHelper $dataHelper,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Riki\ThirdPartyImportExport\Logger\Reconciliation\LoggerCSV $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Filesystem $filesystem,
        File $file,
        DateTime $datetime,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $collectionFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionCustomerFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionOrderFactory,
        \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $collectionShoshaFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory $collectionItemFactory,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $collectionOrderItemFactory,
        \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $collectionRmaFactory,
        \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $collectionRmaItemFactory,
        \Magento\Rma\Model\RmaFactory $rmaFactory,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\ShipmentHelper $biExportShipmentHelper,
        \Riki\Shipment\Model\ResourceModel\Status\Shipment\CollectionFactory $shipment,
        \Riki\Customer\Model\Config\Source\StoreCode $storeCode,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\BundleItemsHelper $bundleItemsHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
    
        if (defined('DS') === false) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_dataHelper = $dataHelper;
        $this->_directoryList = $directoryList;
        $this->_log = $logger;
        $this->_log->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_datetime = $datetime;
        $this->_timezone = $timezone;
        $this->_filesystem = $filesystem;
        $this->_file = $file;
        $this->collectionShipmentFactory = $collectionFactory;
        $this->collectionCustomerFactory = $collectionCustomerFactory;
        $this->collectionOrderFactory = $collectionOrderFactory;
        $this->collectionShoshaFactory = $collectionShoshaFactory;
        $this->collectionItemFactory = $collectionItemFactory;
        $this->collectionOrderItemFactory = $collectionOrderItemFactory;
        $this->collectionRmaFactory = $collectionRmaFactory;
        $this->collectionRmaItemFactory = $collectionRmaItemFactory;
        $this->rmaFactory = $rmaFactory;
        $this->biExportShipmentHelper = $biExportShipmentHelper;
        $this->shipmentHistory = $shipment;
        $this->_storeCode = $storeCode;
        $this->bundleItemsHelper = $bundleItemsHelper;
        $this->_connectionSales = $resourceConnection->getConnection('sales');
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    /**
     * @return bool
     */
    public function execute()
    {
        try {
            if (!$this->_dataHelper->isEnable()) {
                return false;
            }
            $this->_init();
            if ($this->idsShipment) {
                $data = $this->_prepareShipment();
                if ($data) {
                    $heaher[] = array_keys($data[0]);
                    //merge header and value
                    $arrayExport = array_merge($heaher, $data);
                    $this->exportCsv('shipment-item', $arrayExport);
                }
                //Update status export Reciliation
                $this->updateStatusExport($this->idsShipment);
            } else {
                $emptyData = $this->getColumnDataEmpty();
                $this->exportCsv('shipment-item', $emptyData);
            }

            //export rma
            if ($this->idsRma) {
                $dataRma = $this->_prepareRma();
                if ($dataRma) {
                    $heaherRma[] = array_keys($dataRma[0]);
                    //merge header and value
                    $arrayExport = array_merge($heaherRma, $dataRma);
                    $this->exportCsv('return-item', $arrayExport);
                }
            } else {
                $emptyData = $this->getColumnDataEmpty();
                $this->exportCsv('return-item', $emptyData);
            }
            $pathFtp = $this->_dataHelper->getSFTPPathExport();
            $pathFtpReport = $this->_dataHelper->getSFTPPathReportExport();
            $this->_dataHelper->MoveFileToFtp('', $this->pathTmp, $this->_path, $pathFtp, $this->_log, $pathFtpReport);
            //set last time to run
            $dateTime = new \DateTime('', new \DateTimeZone('UTC'));
            $this->_dataHelper->setLastRunToCron($dateTime->format("Y-m-d H:i:s"));
            $this->_dataHelper->sentMail('reconciliation', $this->_log);
        } catch (\Exception $e) {
            $this->_log->critical($e->getMessage());
        }
    }

    public function _init(){

        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $localCsv = $this->_dataHelper->getLocalPathExport();
        if (!$localCsv) {
            $createFileLocal[] = $baseDir . DS .self::DEFAULT_LOCAL_SAVE;
            $createFileLocal[] = $this->pathTmp = $baseDir . DS .self::DEFAULT_LOCAL_SAVE . '_tmp';
            $this->_path = self::DEFAULT_LOCAL_SAVE;
            $this->pathTmp = self::DEFAULT_LOCAL_SAVE . '_tmp';
        } else {
            if (trim($localCsv, -1) == DS) {
                $localCsv = str_replace(DS, '', $localCsv);
            }
            $createFileLocal[] = $baseDir . DS . $localCsv;
            $createFileLocal[] = $baseDir . DS . $localCsv . '_tmp';
            $this->_path = $localCsv;
            $this->pathTmp = $localCsv . '_tmp';
        }
        //delete file log exits before to write new log file
        $this->_dataHelper->backupLog('reconciliation', $this->_log);
        foreach ($createFileLocal as $path) {
            if (!$this->_file->isDirectory($path)) {
                if (!$this->_file->createDirectory($path)) {
                    $this->_log->info(__('Can not create dir file').$path);
                    return;
                }
            }
            if (!$this->_file->isWritable($path)) {
                $this->_log->info(__('The folder have to change permission to 755').$path);
                return ;
            }
        }
        /**
         * init shipment
         */
        $shipments = $this->getShipmentExport();
        if ($shipments != false) {
            /**
             * @var \Magento\Sales\Model\Order\Shipment $shipment
             */
            foreach ($shipments as $shipment) {
                if (!in_array($shipment->getData('order_id'), $this->idsOrder)) {
                    $this->idsOrder[] = $shipment->getData('order_id');
                }
                $this->idsShipment[] = $shipment->getId();
            }
            if ($this->idsShipment) {
                /**
                 * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\Collection $items
                 */
                $items = $this->collectionItemFactory->create();
                $items->addFieldToFilter('parent_id', ['in' => $this->idsShipment]);
                $items->addFieldToSelect(['sku']);
                if ($items->getSize()) {
                    /**
                     * @var \Magento\Sales\Model\Order\Item $item
                     */
                    foreach ($items->getItems() as $item) {
                        $this->productSkus[] = $item->getData('sku');
                        if ($childItems = $item->getChildrenItems()) {
                            foreach ($childItems as $child) {
                                $this->productSkus[] = $child->getSku();
                            }
                        }
                    }
                }
            }
        }
        /**
         * init Rma
         */
        $rmas = $this->getRmaExport();
        if ($rmas != false) {
            /**
            * @var \Magento\Sales\Model\Order\Shipment $shipment
            */
            foreach ($rmas as $rma) {
                if (!in_array($rma->getData('order_id'), $this->idsOrderRma)) {
                    $this->idsOrderRma[] = $rma->getData('order_id');
                }
                $this->idsRma[] = $rma->getId();
            }

            /**
             * @var \Magento\Rma\Model\ResourceModel\Item\Collection $items
             */
            $items = $this->collectionRmaItemFactory->create();
            $items->addFieldToFilter('rma_entity_id', ['in' => $this->idsRma]);
            $items->addFieldToSelect(['product_sku']);
            if ($items->getSize()) {
                /**
                 * @var \Magento\Rma\Model\Item $item
                 */
                foreach ($items->getItems() as $item) {
                    $this->productSkuRmas[] = $item->getData('product_sku');
                }
            }
        }
    }
    /**
     * @return array|void
     */
    public function getProductSku($skus){
        $resultProducts = [];
        $products = $this->_productCollectionFactory->create();
        $products->addAttributeToSelect(['description_invoice','unit_qty','sales_organization','ph4_description']);
        $products->addFieldToFilter('sku', ['in' => $skus]);

        if ($products->getSize()) {
            /**
             * @var \Magento\Catalog\Model\Product $product
             */
            foreach ($products->getItems() as $product) {
                $ph4Code = '';
                if ($product->getData('ph4_description')) {
                    $attr = $product->getResource()->getAttribute('ph4_description');
                    if ($attr->usesSource()) {
                        $ph4Code = $attr->getSource()->getOptionText($product->getData('ph4_description'));
                    }
                }
                $resultProducts[$product->getSku()] = [
                    'productID' => $product->getId(),
                    'description_invoice' => $product->getData('description_invoice'),
                    'unit_qty' => $product->getData('unit_qty'),
                    'PH4_CODE' => $ph4Code,
                    'sales_organization' => $product->getData('sales_organization')
                ];
            }
        }

        return $resultProducts;
    }
    /**
     * @return array|void
     */
    public function getOrderItem($idOrders = []){
        $result = [];
        /**
         * @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection $orderItems
         */
        $orderItems = $this->collectionOrderItemFactory->create();
        $orderItems->addAttributeToSelect('*');
        $orderItems->addFieldToFilter('order_id', ['in' => $idOrders]);
        if ($orderItems->getSize()) {
            /**
             * @var \Magento\Sales\Model\Order\Item $item
             */
            foreach ($orderItems->getItems() as $item) {
                $result[$item->getId()] = [
                    'price_incl_tax' => $item->getPriceInclTax(),
                    'price' => $item->getPrice(),
                    'commission_amount' => $item->getData('commission_amount'),
                    'tax_amount' => $item->getData('tax_amount'),
                ];
                if ($childItems = $item->getChildrenItems()) {
                    foreach ($childItems as $child) {
                        $result[$child->getId()] = [
                            'price_incl_tax' => $child->getPriceInclTax(),
                            'price' => $child->getPrice(),
                            'commission_amount' => $child->getData('commission_amount'),
                            'tax_amount' => $child->getData('tax_amount'),
                        ];
                    }
                }
            }
        }
        return $result;
    }
    /**
     * @return array
     */
    public function getInfoOrder($orderIds = []){
        $resultOrders = [];
        $orders = $this->collectionOrderFactory->create();
        $orders->addFieldToSelect(['shosha_business_code', 'entity_id','increment_id','shipping_amount','shipping_incl_tax']);
        $orders->addFieldToFilter('entity_id', ['in' => $orderIds]);
        if ($orders->getSize()) {
            /**
             * @var \Magento\Sales\Model\Order $order
             */
            foreach ($orders->getItems() as $order) {
                $resultOrders[$order->getId()] = [
                    'shosha_business_code' => $order->getData('shosha_business_code'),
                    'increment_id' => $order->getIncrementId(),
                    'shipping_amount' => $order->getShippingAmount(),
                    'shipping_incl_tax' => $order->getShippingInclTax(),
                ];
            }
        }
        return $resultOrders;
    }
    /**
     * @return array
     */
    public function getInfoShoShaCustomer() {
        $resultShoha = [];
        /**
         * @var  \Riki\Customer\Model\ResourceModel\Shosha\Collection $shoshas
         */
        $shoshas = $this->collectionShoshaFactory->create();
        if ($shoshas->getSize()) {
            /**
             * @var \Riki\Customer\Model\Shosha $shosha
             */
            foreach ($shoshas as $shosha) {
                $resultShoha[$shosha->getData('shosha_business_code')] = [
                    'shosha_first_code' => $shosha->getData('shosha_first_code'),
                    'shosha_cmp_kana' => $shosha->getData('shosha_cmp_kana'),
                    'shosha_address1_kana' => $shosha->getData('shosha_address1_kana'),
                    'shosha_address2_kana' => $shosha->getData('shosha_address2_kana'),
                    'shosha_second_code' => $shosha->getData('shosha_second_code'),
                    'shosha_cmp_kana' => $shosha->getData('shosha_cmp_kana'),
                    'shosha_code' => $shosha->getData('shosha_code'),
                    'shosha_commission' => $shosha->getData('shosha_commission'),
                ];
            }
        }
        return $resultShoha;
    }
    /**
     * @return bool|\Magento\Framework\DataObject[]
     */
    public function getShipmentExport(){
        /**
         * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $collection
         */
        $collection = $this->collectionShipmentFactory->create();
        $collection->addFieldToFilter('is_reconciliation_exported', '1');
        if ($collection->getSize()) {
            return $collection->getItems();
        }
        return false;
    }
    /**
     * @return bool|\Magento\Framework\DataObject[]
     */
    public function getRmaExport(){
        /**
         * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $collection
         */
        $collection = $this->collectionRmaFactory->create();
        $lastToRun = $this->_dataHelper->getLastRunToCron();
        $collection->getSelect()->where('return_approval_date is not null');
        if ($lastToRun) {
            $collection->getSelect()->where('return_approval_date > ?', $lastToRun);
        }
        if ($collection->getSize()) {
            return $collection->getItems();
        }
        return false;
    }
    /**
     * @return array
     */
    public function _prepareShipment(){
        $shipments = $this->getShipmentExport();
        $products = $this->getProductSku($this->productSkus);
        $orderItems = $this->getOrderItem($this->idsOrder);
        $prepareData = [];
        if ($shipments) {
            $orderInfo = $this->getInfoOrder($this->idsOrder);
            $shoshas = $this->getInfoShoShaCustomer();
            /**
             * @var \Magento\Sales\Model\Order\Shipment $shipment
             */
            foreach ($shipments as $shipment) {
                $this->_log->info(sprintf('The shipment number : %s was exported', $shipment->getIncrementId()));
                $arr1 = $arr2 = $txtShosha = [];
                $shoshaCode = $orderInfo[$shipment->getData('order_id')]['shosha_business_code'];
                $j = 1;
                $orderItemIDs = [];
                $shippedDate = '';
                /**
                 * @var \Riki\Shipment\Model\ResourceModel\Status\Shipment\Collection $collecShipmentHistory
                 */
                $collecShipmentHistory = $this->shipmentHistory->create();
                $collecShipmentHistory->addFieldToFilter('shipment_id', $shipment->getId());
                if ($collecShipmentHistory->getSize()) {
                    $shipmentHistory = $collecShipmentHistory->getFirstItem();
                    $shippedDate = $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($shipmentHistory->getData('shipment_date'), 2, 2));
                }
                /**
                 * It defined in Riki\Customer\Model\Config\Source\StoreCode 0003708471: ITOCHU DDM
                 * key 0 : 0003708471
                 * key 1 : ITOCHU DDM
                 */
                if (isset($shoshas[$shoshaCode]['shosha_first_code'])) {
                    $arr1 = $this->getTextShoshaStoreCode($shoshas[$shoshaCode]['shosha_first_code']);
                }
                if (isset($shoshas[$shoshaCode]['shosha_second_code'])) {
                    $arr2 = $this->getTextShoshaStoreCode($shoshas[$shoshaCode]['shosha_second_code']);
                }

                //Magento\Framework\App\RequestInterface/ShoshaCode::getOptionArray
                if (isset($shoshas[$shoshaCode]['shosha_code'])) {
                    $txtShosha = $this->getShoshaCode($shoshas[$shoshaCode]['shosha_code']);
                }
                $commission = '';
                if (isset($shoshas[$shoshaCode]['shosha_commission'])) {
                    $commission = $shoshas[$shoshaCode]['shosha_commission'] * 100;
                }
                //get maker booking date
                $makerBookingDate = $this->_datetime->date('Y/m/d', $this->_timezone->formatDateTime($shipment->getData('shipped_out_date'), 2, 2));
                /**
                 * get shipment item
                 * @var \Magento\Sales\Model\Order\Shipment\Item $item
                 */
                foreach ($shipment->getAllItems() as $item) {
                    $orderItem = $item->getOrderItem();
                    if(!$orderItem) {
                        continue;
                    }
                    $orderItemIDs[] = $item->getData('order_item_id');
                    $data = [
                        'shipment_type' => self::SHIPMENT_TYPE['sales'],
                        'maker_booking_date' => $makerBookingDate,
                        'order_number' => $orderInfo[$shipment->getOrderId('order_id')]['increment_id'],
                        'shosha_code' =>  ($txtShosha ? $txtShosha : ''),
                        'shosha_first_code' => ($arr1 ? $arr1[0] : ''),
                        'shosha_first_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_first_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_first_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_second_code' => ($arr2 ? $arr2[0] : ''),
                        'shosha_second_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_second_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_second_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_tertiary_code' => $shoshaCode,
                        'shosha_tertiary_customer_name' => '',
                        'shosha_tertiary_customer_address' => '',
                        'voucher_row_number' => $this->formatNumber($j++),
                        'product_sap_code' => $item->getSku(),
                        'product_name' => (isset($products[$item->getSku()]['description_invoice']) ? $products[$item->getSku()]['description_invoice'] : '' ) ,
                        'pack_size' => (isset($products[$item->getSku()]['unit_qty']) ? $products[$item->getSku()]['unit_qty'] : ''),
                        'quantity' => $item->getData('unit_qty'),
                        'unit' => $this->getUnitKeyProduct($item->getData('unit_case')),
                        'sap_price' => round($item->getPrice(), 2, PHP_ROUND_HALF_UP) * 100,
                        'selling_price' =>  ($item->getPrice() + $orderItem->getData('tax_riki')) * 100,
                        'billing_price' => round($item->getPrice(), 2, PHP_ROUND_HALF_UP) * intval($item->getQty()),
                        'billing_due_date' => $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($shipment->getData('shipped_out_date'), 2, 2)),
                        'billing_account' => $commission,
                        'rebate_amount' =>  intval($item->getData('commission_amount')),
                        'space' => (isset($products[$item->getSku()]['PH4_CODE']) ? $products[$item->getSku()]['PH4_CODE']  : '' ) . ' ' . (isset($products[$item->getSku()]['sales_organization']) ? $products[$item->getSku()]['sales_organization'] : ''),
                        'warehouse_input_shipped_out_date' => $shippedDate
                    ];
                    $prepareData[] = $data;
                    //if item is bundle
                    if ($childOrderItem = $orderItem->getChildrenItems()) {
                        //get price of child bundle
                        $arrPriceBundle = $this->getPriceChildOfBundle($orderItem);
                        /**
                         * @var \Magento\Sales\Model\Order\Item $child
                         */
                        foreach ($childOrderItem as $child) {
                            $data = [
                                'shipment_type' => self::SHIPMENT_TYPE['sales'],
                                'maker_booking_date' => $makerBookingDate,
                                'order_number' => $orderInfo[$shipment->getOrderId('order_id')]['increment_id'],
                                'shosha_code' => ($txtShosha ? $txtShosha : ''),
                                'shosha_first_code' => ($arr1 ? $arr1[0] : ''),
                                'shosha_first_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                                'shosha_first_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                                'shosha_first_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                                'shosha_second_code' => ($arr2 ? $arr2[0] : ''),
                                'shosha_second_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                                'shosha_second_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                                'shosha_second_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                                'shosha_tertiary_code' => $shoshaCode,
                                'shosha_tertiary_customer_name' => '',
                                'shosha_tertiary_customer_address' => '',
                                'voucher_row_number' => $this->formatNumber($j++),
                                'product_sap_code' => $child->getSku(),
                                'product_name' => (isset($products[$child->getSku()]['description_invoice']) ? $products[$child->getSku()]['description_invoice'] : '' ) ,
                                'pack_size' => (isset($products[$child->getSku()]['unit_qty']) ? $products[$child->getSku()]['unit_qty'] : ''),
                                'quantity' => $child->getData('unit_qty'),
                                'unit' => $this->getUnitKeyProduct($child->getData('unit_case')),
                                'sap_price' =>  $arrPriceBundle[$child->getId()]['sap_price'] * 100,
                                'selling_price' => $arrPriceBundle[$child->getId()]['selling_price'] * 100,
                                'billing_price' => $arrPriceBundle[$child->getId()]['sap_price'],
                                'billing_due_date' => $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($shipment->getData('shipped_out_date'), 2, 2)),
                                'billing_account' => $child->getData('commission_amount') * 100,
                                'rebate_amount' => intval($child->getData('commission_amount')) ,
                                'space' => (isset($products[$child->getSku()]['PH4_CODE']) ? $products[$child->getSku()]['PH4_CODE']  : '' ) . ' ' . (isset($products[$child->getSku()]['sales_organization']) ? $products[$child->getSku()]['sales_organization'] : ''),
                                'warehouse_input_shipped_out_date' => $shippedDate
                            ];
                            $prepareData[] = $data;
                        }
                    }
                }
                //shipment have wrapping
                    $data = [
                        'shipment_type' => self::SHIPMENT_TYPE['sales'],
                        'maker_booking_date' => $makerBookingDate,
                        'order_number' => $orderInfo[$shipment->getOrderId()]['increment_id'],
                        'shosha_code' => ($txtShosha ? $txtShosha : ''),
                        'shosha_first_code' => ($arr1 ? $arr1[0] : ''),
                        'shosha_first_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_first_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_first_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_second_code' => ($arr2 ? $arr2[0] : ''),
                        'shosha_second_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_second_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_second_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_tertiary_code' => $shoshaCode,
                        'shosha_tertiary_customer_name' => '',
                        'shosha_tertiary_customer_address' => '',
                        'voucher_row_number' => $this->formatNumber($j++),
                        'product_sap_code' => 'WRAP',
                        'product_name' => 'ﾗｯﾋﾟﾝｸﾞ',
                        'pack_size' => 1,
                        'quantity' => 1,
                        'unit' => 1,
                        'sap_price' => $shipment->getData('gw_price') * 100,
                        'selling_price' => ($shipment->getData('gw_price') + $shipment->getData('gw_tax_amount')) * 100,
                        'billing_price' => $shipment->getData('gw_price') + $shipment->getData('gw_tax_amount'),
                        'billing_due_date' => $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($shipment->getData('shipped_out_date'), 2, 2)),
                        'billing_account' => '0000',
                        'rebate_amount' => 0,
                        'space' => '',
                        'warehouse_input_shipped_out_date' => $shippedDate
                    ];
                    $prepareData[] = $data;
                //shipment have shipping fee
                    $data = [
                        'shipment_type' => self::SHIPMENT_TYPE['sales'],
                        'maker_booking_date' => $makerBookingDate,
                        'order_number' => $orderInfo[$shipment->getData('order_id')]['increment_id'],
                        'shosha_code' => ($txtShosha ? $txtShosha : ''),
                        'shosha_first_code' => ($arr1 ? $arr1[0] : ''),
                        'shosha_first_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_first_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_first_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_second_code' => ($arr2 ? $arr2[0]: ''),
                        'shosha_second_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_second_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_second_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_tertiary_code' => $shoshaCode,
                        'shosha_tertiary_customer_name' => '',
                        'shosha_tertiary_customer_address' => '',
                        'voucher_row_number' => $this->formatNumber($j++),
                        'product_sap_code' => 'SORYO',
                        'product_name' => 'ｿｳﾘｮｳ',
                        'pack_size' => 1,
                        'quantity' => 1,
                        'unit' => 1,
                        'sap_price' => $orderInfo[$shipment->getOrderId()]['shipping_amount'] * 100,
                        'selling_price' =>  $orderInfo[$shipment->getOrderId()]['shipping_incl_tax'] * 100,
                        'billing_price' =>$orderInfo[$shipment->getOrderId()]['shipping_incl_tax'],
                        'billing_due_date' => $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($shipment->getData('shipped_out_date'), 2, 2)),
                        'billing_account' => '0000',
                        'rebate_amount' => 0,
                        'space' => '',
                        'warehouse_input_shipped_out_date' => $shippedDate
                    ];
                    $prepareData[] = $data;

                //check shipment used point
                    $data = [
                        'shipment_type' => self::SHIPMENT_TYPE['sales'],
                        'maker_booking_date' => $makerBookingDate,
                        'order_number' => $orderInfo[$shipment->getData('order_id')]['increment_id'],
                        'shosha_code' => ($txtShosha ? $txtShosha : ''),
                        'shosha_first_code' => ($arr1 ? $arr1[0] : ''),
                        'shosha_first_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_first_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_first_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_second_code' => ($arr2 ? $arr2[0] : ''),
                        'shosha_second_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_second_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_second_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_tertiary_code' => $shoshaCode,
                        'shosha_tertiary_customer_name' => '',
                        'shosha_tertiary_customer_address' => '',
                        'voucher_row_number' => $this->formatNumber($j++),
                        'product_sap_code' => 'POINT',
                        'product_name' => 'ﾎﾟｲﾝﾄ',
                        'pack_size' => 1,
                        'quantity' => 1,
                        'unit' => 1,
                        'sap_price' => $shipment->getData('shopping_point_amount') * 100,
                        'selling_price' => $shipment->getData('shopping_point_amount') * 100,
                        'billing_price' => $shipment->getData('shopping_point_amount'),
                        'billing_due_date' => $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($shipment->getData('shipped_out_date'), 2, 2)),
                        'billing_account' => '0000',
                        'rebate_amount' => 0,
                        'space' => '',
                        'warehouse_input_shipped_out_date' => $shippedDate
                    ];
                    $prepareData[] =$data;

                //shipment have cart rule
                    $data = [
                        'shipment_type' => self::SHIPMENT_TYPE['sales'],
                        'maker_booking_date' => $makerBookingDate,
                        'order_number' => $orderInfo[$shipment->getData('order_id')]['increment_id'],
                        'shosha_code' => ($txtShosha ? $txtShosha : ''),
                        'shosha_first_code' => ($arr1 ? $arr1[0] : ''),
                        'shosha_first_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_first_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_first_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_second_code' => ($arr2 ? $arr2[0] : ''),
                        'shosha_second_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_second_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_second_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_tertiary_code' => $shoshaCode,
                        'shosha_tertiary_customer_name' => '',
                        'shosha_tertiary_customer_address' => '',
                        'voucher_row_number' => $this->formatNumber($j++),
                        'product_sap_code' => 'DISCOUNT',
                        'product_name' => 'ﾃﾞｨｽｶｳﾝﾄ',
                        'pack_size' => 1,
                        'quantity' => 1,
                        'unit' => 1,
                        'sap_price' => $shipment->getData('base_discount_amount') * 100,
                        'selling_price' => $shipment->getData('base_discount_amount') * 100,
                        'billing_price' => $shipment->getData('base_discount_amount'),
                        'billing_due_date' => $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($shipment->getData('shipped_out_date'), 2, 2)),
                        'billing_account' => '0000',
                        'rebate_amount' => 0,
                        'space' => '',
                        'warehouse_input_shipped_out_date' => $shippedDate
                    ];
                    $prepareData[] = $data;
            }
        }
        return $prepareData;
    }
    /**
     * @return array
     */
    public function _prepareRma(){
        $rmas = $this->getRmaExport();
        $products = $this->getProductSku($this->productSkuRmas);
        $orderItems = $this->getOrderItem($this->idsOrderRma);
        $prepareData = [];
        if ($rmas != false) {
            $orderInfo = $this->getInfoOrder($this->idsOrderRma);
            $shoshas = $this->getInfoShoShaCustomer();
            $taxShipmentFee = $this->biExportShipmentHelper->getShippingTaxRate();
            /**
             * @var \Magento\Rma\Model\Rma $rma
             */
            foreach ($rmas as $rma) {
                $this->_log->info(sprintf('The Return number : %s was exported', $rma->getIncrementId()));
                $arr1 = $arr2 = $txtShosha = [];
                $shoshaCode = $orderInfo[$rma->getData('order_id')]['shosha_business_code'];
                $j = 1;
                /**
                 * It defined in Riki\Customer\Model\Config\Source\StoreCode 0003708471: ITOCHU DDM
                 * key 0 : 0003708471
                 * key 1 : ITOCHU DDM
                 */
                if (isset($shoshas[$shoshaCode]['shosha_first_code'])) {
                    $arr1 = $this->getTextShoshaStoreCode($shoshas[$shoshaCode]['shosha_first_code']);
                }
                if (isset($shoshas[$shoshaCode]['shosha_second_code'])) {
                    $arr2 = $this->getTextShoshaStoreCode($shoshas[$shoshaCode]['shosha_second_code']);
                }
                //Magento\Framework\App\RequestInterface/ShoshaCode::getOptionArray
                if (isset($shoshas[$shoshaCode]['shosha_code'])) {
                    $txtShosha = $this->getShoshaCode($shoshas[$shoshaCode]['shosha_code']);
                }
                $commission = '';
                if (isset($shoshas[$shoshaCode]['shosha_commission'])) {
                    $commission = $shoshas[$shoshaCode]['shosha_commission'] * 100;
                }
                //shipment have rma
                /**
                 * @var \Magento\Rma\Model\Item $item
                 */
                foreach ($rma->getItemsForDisplay(true)->getItems() as $item) {
                    $billingPrice = 0;
                    $billingPrice = ($item->getData('return_amount') + $item->getData('return_amount_adj')) - $orderItems[$item->getData('order_item_id')]['tax_amount'];
                    $data = [
                        'shipment_type' => self::SHIPMENT_TYPE['return'],
                        'maker_booking_date' => (($rma->getData('return_approval_date')) ? $this->_datetime->date('Y/m/d', $this->_timezone->formatDateTime($rma->getData('approval_date'), 2, 2)) : ''),
                        'order_number' => $rma->getData('order_increment_id'),
                        'shosha_code' => ($txtShosha ? $txtShosha : ''),
                        'shosha_first_code' => ($arr1 ? $arr1[0] : ''),
                        'shosha_first_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_first_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_first_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_second_code' => ($arr2 ? $arr2[0] : ''),
                        'shosha_second_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                        'shosha_second_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                        'shosha_second_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                        'shosha_tertiary_code' => $shoshaCode,
                        'shosha_tertiary_customer_name' => '',
                        'shosha_tertiary_customer_address' => '',
                        'voucher_row_number' => $this->formatNumber($j++),
                        'product_sap_code' => $item->getData('product_sku'),
                        'product_name' =>(isset($products[$item->getData('product_sku')]['description_invoice']) ? $products[$item->getData('product_sku')]['description_invoice'] : '') ,
                        'pack_size' => (isset($products[$item->getData('product_sku')]['unit_qty']) ? $products[$item->getData('product_sku')]['unit_qty'] : ''),
                        'quantity' => (int)$item->getData('qty_returned'),
                        'unit' => $this->getUnitKeyProduct($item->getData('unit_case')),
                        'sap_price' => round(($item->getData('return_amount') + $item->getData('return_amount_adj') - $orderItems[$item->getData('order_item_id')]['tax_amount']) / $item->getData('qty_returned'), 2, PHP_ROUND_HALF_UP) * 100,
                        'selling_price' => round(($item->getData('return_amount') + $item->getData('return_amount_adj')) / $item->getData('qty_returned'), 2, PHP_ROUND_HALF_UP) * 100,
                        'billing_price' => $billingPrice,
                        'billing_due_date' => (($rma->getData('return_approval_date')) ? $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($rma->getData('approval_date'), 2, 2)) : ''),
                        'billing_account' => $commission,
                        'rebate_amount' => intval($item->getData('commission_amount')),
                        'space' => (isset($products[$item->getData('product_sku')]['PH4_CODE']) ? $products[$item->getData('product_sku')]['PH4_CODE']  : '')  . ' ' . (isset($products[$item->getData('product_sku')]['sales_organization']) ? $products[$item->getData('product_sku')]['sales_organization']  : ''),
                        'warehouse_input_shipped_out_date' => ''
                    ];
                    $prepareData[] = $data;
                }
                $data = [
                    'shipment_type' => self::SHIPMENT_TYPE['return'],
                    'maker_booking_date' => (($rma->getData('return_approval_date')) ? $this->_datetime->date('Y/m/d', $this->_timezone->formatDateTime($rma->getData('approval_date'), 2, 2)) : ''),
                    'order_number' => $rma->getData('order_increment_id'),
                    'shosha_code' => ($txtShosha ? $txtShosha : ''),
                    'shosha_first_code' => ($arr1 ? $arr1[0] : ''),
                    'shosha_first_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                    'shosha_first_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                    'shosha_first_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                    'shosha_second_code' => ($arr2 ? $arr2[0] : ''),
                    'shosha_second_cmp_kana' => (isset($shoshas[$shoshaCode]['shosha_cmp_kana']) ? $shoshas[$shoshaCode]['shosha_cmp_kana'] : ''),
                    'shosha_second_address1_kana' => (isset($shoshas[$shoshaCode]['shosha_address1_kana']) ? $shoshas[$shoshaCode]['shosha_address1_kana'] : ''),
                    'shosha_second_address2_kana' => (isset($shoshas[$shoshaCode]['shosha_address2_kana']) ? $shoshas[$shoshaCode]['shosha_address2_kana'] : ''),
                    'shosha_tertiary_code' => $shoshaCode,
                    'shosha_tertiary_customer_name' => '',
                    'shosha_tertiary_customer_address' => '',
                    'voucher_row_number' => $this->formatNumber($j++),
                    'product_sap_code' => 'REFUND',
                    'product_name' => 'ｿﾉﾀﾍﾝｷﾝ',
                    'pack_size' => 1,
                    'quantity' => 1,
                    'unit' => 1,
                    'sap_price' => round(($rma->getData('return_shipping_fee') / (1 + ($taxShipmentFee / 100))), 0, PHP_ROUND_HALF_UP) * 100,
                    'selling_price' => $rma->getData('return_shipping_fee') * 100,
                    'billing_price' => $rma->getData('return_shipping_fee'),
                    'billing_due_date' => (($rma->getData('return_approval_date')) ? $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($rma->getData('approval_date'), 2, 2)) : ''),
                    'billing_account' => '0000',
                    'rebate_amount' => 0,
                    'space' => '',
                    'warehouse_input_shipped_out_date' => ''
                ];
                $prepareData[] = $data;
            }
        }
        return $prepareData;
    }
    /**
     * @param $unitCase
     * @return int
     */
    public function getUnitKeyProduct($unitCase){
        if ($unitCase == 'CS') {
            return 1;
        } elseif ($unitCase = 'EA') {
            return 3;
        }
        return 1;
    }
    /**
     * @param $data
     */
    public function exportCsv($name, $data) {
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->_csv = new \Magento\Framework\File\Csv(new File());
        //create Name csv
        $nameCsv = $name .'-'. $this->_timezone->date()->format('YmdHis').'.csv';
        //Create csv
        if (!$this->_file->isExists($baseDir.DS.$this->pathTmp.DS.$nameCsv)) {
            $this->_csv->saveData($baseDir.DS.$this->pathTmp.DS.$nameCsv, $data);
        }
    }
    /**
     * @param $num
     * @return string
     */
    public function getTextShoshaStoreCode($num){
        $array = $this->_storeCode->getOptionArray();
        if (isset($array[$num])) {
            $array = explode(':', preg_replace('/\s+/', '', $array[$num]));
            return $array;
        }
        return '';
    }

    /**
     * @param $num
     * @return string
     */
    public function getShoshaCode($num){
        $array = \Riki\Customer\Model\Shosha\ShoshaCode::getOptionArray();
        if (isset($array[$num])) {
            return $array[$num];
        }
        return '';
    }
    /**
     * @param $num
     * @return string
     */
    public function formatNumber($num){
        if ($num < 10) {
            return '0' . $num;
        }
        return $num;
    }

    /**
     * @return array
     */
    private function getColumnDataEmpty(){
        return [0 =>[
                    'shipment_type',
                    'maker_booking_date',
                    'order_number',
                    'shosha_code',
                    'shosha_first_code',
                    'shosha_first_cmp_kana',
                    'shosha_first_address1_kana',
                    'shosha_first_address2_kana' ,
                    'shosha_second_code',
                    'shosha_second_cmp_kana' ,
                    'shosha_second_address1_kana',
                    'shosha_second_address2_kana',
                    'shosha_tertiary_code',
                    'shosha_tertiary_customer_name',
                    'shosha_tertiary_customer_address',
                    'voucher_row_number',
                    'product_sap_code',
                    'product_name',
                    'pack_size',
                    'quantity',
                    'unit',
                    'sap_price',
                    'selling_price',
                    'billing_price',
                    'billing_due_date',
                    'billing_account',
                    'rebate_amount',
                    'space',
                    'warehouse_input_shipped_out_date'
                ]
        ];
    }

    /* get shipment item
    * @var \Magento\Sales\Model\Order\Shipment\Item $item
    */
    private function getPriceChildOfBundle($orderItem){
        $result = [];
        $taxRiki = $orderItem->getData('tax_riki');
        $k = $keyArrHight = 0;
        $data = [];
        $totalTaxBundle = 0;
        /**
         * @var \Magento\Sales\Model\Order\Item $child
         */
        foreach ($orderItem->getChildrenItems() as $child) {
            $productOptions = $child->getData('product_options');
            $attribute['bundle_selection_attributes'] = $productOptions['bundle_selection_attributes'];
            $childrenItemRowTotal = $this->getRowTotalForBundleChildrenItem($attribute);
            $taxPriceBundle = $this->bundleItemsHelper->getTaxAmountForBundleChildrenItem($taxRiki, $orderItem->getPrice(), $childrenItemRowTotal);
            $totalTaxBundle += $taxPriceBundle;
            //tax
            //get $key array have price highest
            $data[$k] = [
                'id' => $child->getId(),
                'sap_price' => $childrenItemRowTotal,
                'selling_price' => $childrenItemRowTotal + $taxPriceBundle,
                'childrenItemRowTotal' => $childrenItemRowTotal
            ];
            //get key highest price
            if ($k > 0) {
                $l = $k;
                if ($data[$l]['sap_price'] > $data[$l - 1]['sap_price']) {
                    $keyArrHight = $l;
                }
            }
            $k++;
        }

        if ($taxRiki > $totalTaxBundle) {
            $data[$keyArrHight]['selling_price'] = $data[$keyArrHight]['selling_price'] + ($taxRiki - $totalTaxBundle);
        }

        foreach ($data as $key => $value) {
            $result[$value['id']] = $value;
        }
        return $result;
    }

    /**
     * Get row total for bundle children item
     *
     * @param $item
     * @return float|int
     */
    public function getRowTotalForBundleChildrenItem($productOption)
    {
        /*get product option*/
            /*get bundle option*/
            $bundleOption = $this->unserializeOption($productOption['bundle_selection_attributes']);
        if ($bundleOption) {
            if (!empty($bundleOption['price'])) {
                return $bundleOption['price'];
            }
        }
        return 0;
    }
    /**
     * un serialize Option
     *
     * @param $option
     * @return bool|mixed
     */
    public function unserializeOption($option)
    {
        try {
            return $this->serializer->unserialize($option);
        } catch (\Exception $e) {
            $this->_log->critical($e->getMessage());
        }
        return false;
    }

    /**
     * @param array $shipmentId
     */
    public function updateStatusExport($shipmentId = []){
        //2 is fisnished export reconciliation
        $this->_connectionSales->update(
            'sales_shipment',
            ['is_reconciliation_exported' => 2],
            'entity_id in ('.implode(",", $shipmentId).')'
        );
    }
}
