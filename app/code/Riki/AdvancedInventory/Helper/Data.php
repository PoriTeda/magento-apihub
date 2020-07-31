<?php

namespace Riki\AdvancedInventory\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepositoryInterface;
use Riki\Preorder\Helper\Data as PreOrderHelperData;
use Wyomind\AdvancedInventory\Model\Item as AdvancedInventoryModelItem;
use Wyomind\AdvancedInventory\Model\ResourceModel\Item\CollectionFactory as AdvancedInventoryItemCollectionFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Encryption\EncryptorInterface;

class Data extends AbstractHelper
{
    const RIKI_IMPORT_STOCK_COMMON_ENABLE = "importstock/common/enable";
    const RIKI_IMPORT_STOCK_COMMON_BIZEX_ENABLE = "importstock/common/enable_bizex";
    const RIKI_IMPORT_STOCK_PATTERN_PATTERN_INV1 = "importstock/pattern/pattern_inv1";
    const RIKI_IMPORT_STOCK_PATTERN_PATTERN_INV2 = "importstock/pattern/pattern_inv2";
    const RIKI_IMPORT_STOCK_SFTP_HOST = "setting_sftp/setup_ftp/ftp_id";
    const RIKI_IMPORT_STOCK_SFTP_PORT = "setting_sftp/setup_ftp/ftp_port";
    const RIKI_IMPORT_STOCK_SFTP_USER = "setting_sftp/setup_ftp/ftp_user";
    const RIKI_IMPORT_STOCK_SFTP_PASS = "setting_sftp/setup_ftp/ftp_pass";

    const RIKI_IMPORT_STOCK_LOCATION_IMPORT_INV1 = "importstock/location/import_inv1";
    const RIKI_IMPORT_STOCK_LOCATION_IMPORT_INV2 = "importstock/location/import_inv2";

    const RIKI_IMPORT_STOCK_EXPRESSION_EXPRESS_INV11 = "importstock/expression/express_inv11";
    const RIKI_IMPORT_STOCK_EXPRESSION_EXPRESS_INV12 = "importstock/expression/express_inv12";
    const RIKI_IMPORT_STOCK_EXPRESSION_EXPRESS_INV13 = "importstock/expression/express_inv13";

    const RIKI_IMPORT_EMAIL_RECEIVER = "importstock/email/receiver";
    const RIKI_IMPORT_EMAIL_TEMPLATE = "importstock/email/template";
    const RIKI_IMPORT_EMAIL_TEMPLATE_STOCK = "importstock/email/template_stock";

    /*Define Key For CSV data*/
    const RIKI_IMPORT_CSV_KEY_SKU = 0;
    const RIKI_IMPORT_CSV_KEY_WAREHOUSE_CODE = 0;
    const RIKI_IMPORT_CSV_KEY_QTY = 1;
    const RIKI_IMPORT_CSV_KEY_IS_IN_STOCK = 2;
    const RIKI_IMPORT_CSV_KEY_MANAGE_STOCK = 3;

    /*Define import status Code*/
    const RIKI_IMPORT_SUCCESS = 1;
    const RIKI_IMPORT_ERROR_UNKNOWN = -1;
    const RIKI_IMPORT_ERROR_INVALID_PRODUCT_SKU = -2;
    const RIKI_IMPORT_ERROR_INVALID_WAREHOUSE_CODE = -3;

    const ERROR_QTY_BUNDLE_CHILDREN = 99;


    /**
     * @var \Wyomind\AdvancedInventory\Model\ResourceModel\Stock\CollectionFactory
     */
    protected $_advancedInventoryStockCollectionFactory;

    /**
     * @var \Wyomind\AdvancedInventory\Model\StockFactory
     */
    protected $_advancedInventoryStockModel;

    /**
     * @var \Wyomind\AdvancedInventory\Model\ResourceModel\PointOfSale\CollectionFactory
     */
    protected $_pointOfSalesCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Riki\AdvancedInventory\Logger\LoggerInv1
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /* @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $productRepositoryInterface;

    /* @var \Wyomind\AdvancedInventory\Model\ResourceModel\Item\CollectionFactory */
    protected $_advancedInventoryItemCollectionFactory;

    /* @var \Wyomind\AdvancedInventory\Api\StockRepositeryInterface */
    protected $wyomindStockRepository;

    /* @var \Magento\Framework\Filesystem */
    protected $_fileSystem;

    /* @var \Magento\Framework\App\Filesystem\DirectoryList */
    protected $_directoryList;

    /**
     * Update stock status
     *
     * @var \Magento\CatalogInventory\Model\StockRegistry|null
     */
    protected $_stockRegistry = null;

    protected $_stockModel = null;

    protected $_coreHelper = null;

    protected $_journalHelper = null;

    protected $_readerCSV;

    protected $_preOrderHelper;

    protected $_advancedInventoryModelItem;

    protected $_stockModelFactory;

    protected $_advancedInventoryStockModelFactory;

    protected $_posFactory;

    protected $_assignationModel;

    protected $placesByOrder = [];

    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    public function __construct(
        \Wyomind\AdvancedInventory\Api\StockRepositeryInterface $wyomindStockRepositoryInterface,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem $filesystem,
        AdvancedInventoryItemCollectionFactory $advancedInventoryItemCollectionFactory,
        AdvancedInventoryModelItem $advancedInventoryModelItem,
        PreOrderHelperData $preOrderHelperData,
        ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Framework\App\Helper\Context $context,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Stock\CollectionFactory $advancedInventoryStockCollectionFactory,
        \Wyomind\AdvancedInventory\Model\Stock $advancedInventoryStockModel,
        \Wyomind\AdvancedInventory\Model\StockFactory $advancedInventoryStockFactory,
        \Wyomind\AdvancedInventory\Model\ResourceModel\PointOfSale\CollectionFactory $pointOfSaleCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\AdvancedInventory\Logger\LoggerInv1 $loggerInv1,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Wyomind\AdvancedInventory\Model\Stock $stockModel,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Wyomind\AdvancedInventory\Helper\Journal $journalHelper,
        \Magento\Framework\File\Csv $csvReader,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posFactory,
        \Riki\AdvancedInventory\Model\Assignation $assignationModel,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->wyomindStockRepository = $wyomindStockRepositoryInterface;
        $this->_directoryList = $directoryList;
        $this->_fileSystem = $filesystem;
        $this->_advancedInventoryStockModelFactory = $advancedInventoryStockFactory;
        $this->_advancedInventoryItemCollectionFactory = $advancedInventoryItemCollectionFactory;
        $this->_advancedInventoryModelItem = $advancedInventoryModelItem;
        $this->_preOrderHelper = $preOrderHelperData;
        $this->_advancedInventoryStockCollectionFactory = $advancedInventoryStockCollectionFactory;
        $this->_advancedInventoryStockModel = $advancedInventoryStockModel;
        $this->_pointOfSalesCollectionFactory = $pointOfSaleCollectionFactory;
        $this->_productFactory = $productFactory;
        $this->logger = $loggerInv1;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManagerInterface;
        $this->inlineTranslation = $inlineTranslation;
        $this->_stockRegistry = $stockRegistry;
        $this->_stockModel = $stockModel;
        $this->_coreHelper = $coreHelper;
        $this->_journalHelper = $journalHelper;
        $this->_readerCSV = $csvReader;
        $this->_posFactory = $posFactory;
        $this->_assignationModel = $assignationModel;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->_encryptor = $encryptor;
    }

    /**
     * Replace current logger by other logger
     *
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function getSystemConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }

    public function checkSftpConnection($sftp)
    {
        $host = $this->getSystemConfig(self::RIKI_IMPORT_STOCK_SFTP_HOST);
        $port = $this->getSystemConfig(self::RIKI_IMPORT_STOCK_SFTP_PORT);
        $username = $this->getSystemConfig(self::RIKI_IMPORT_STOCK_SFTP_USER);
        $pass = $this->getSystemConfig(self::RIKI_IMPORT_STOCK_SFTP_PASS);
        $pass = $this->_encryptor->decrypt($pass);
        try {
            $sftp->open(
                array(
                    'host' => $host . ':' . $port,
                    'username' => $username,
                    'password' => $pass,
                    'timeout' => 300
                )
            );
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            return false;
        }

        return true;
    }

    public function checkSftpLocation($sftp, $location)
    {
        $host = $this->getSystemConfig(self::RIKI_IMPORT_STOCK_SFTP_HOST);
        $port = $this->getSystemConfig(self::RIKI_IMPORT_STOCK_SFTP_PORT);
        $username = $this->getSystemConfig(self::RIKI_IMPORT_STOCK_SFTP_USER);
        $pass = $this->getSystemConfig(self::RIKI_IMPORT_STOCK_SFTP_PASS);
        $pass = $this->_encryptor->decrypt($pass);
        try {
            $sftp->open(
                array(
                    'host' => $host . ":" . $port,
                    'username' => $username,
                    'password' => $pass,
                    'timeout' => 300
                )
            );
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            return false;
        }
        $dirList = explode('/', $location);
        $i = 0;

        foreach ($dirList as $dir) {
            if ($dir != '') {
                try {
                    if (!$sftp->cd($dir)) {
                        return false;
                    }
                } catch (\Exception $e) {
                    $this->logger->info($e->getMessage());
                    return false;
                }
            }
            $i++;
        }
        return true;
    }

    public function getRemoteFolderName($location)
    {
        $dirList = explode('/', $location);
        return $dirList[count($dirList) - 1];
    }

    public function getCsvData($filename)
    {
        $datas = $this->_readerCSV->getData($filename);
        $data = array();
        if ($datas) {
            foreach ($datas as $_data) {
                $data[] = array_map('trim', $_data);
            }
        }
        return $data;
    }

    /**
     * @param $item
     * @param array $whData
     * @param $whCode
     * @return int
     */
    public function updateAdvancedInventory($item, $whData = array(), $whCode)
    {
        $productId = $this->getProductIdFromProductSku($item[self::RIKI_IMPORT_CSV_KEY_SKU]);
        $placeId = $whData[strtolower($whCode)];
        $advancedInventoryCollection = $this->_advancedInventoryStockCollectionFactory->create()
            ->addFieldToFilter('product_id', $productId)
            ->addFieldToFilter('place_id', $placeId);
        if ($advancedInventoryCollection->getSize() > 0) {
            $advancedInventoryId = $advancedInventoryCollection->getFirstItem()->getId();
            /** @var \Wyomind\AdvancedInventory\Model\Stock $advancedInventoryModel */
            $advancedInventoryModel = $this->_advancedInventoryStockModel->load($advancedInventoryId);
            try {
                $currentQty = (int)$advancedInventoryModel->getData('quantity_in_stock');
                $newQty = $currentQty + (int) $item[self::RIKI_IMPORT_CSV_KEY_QTY];
                $advancedInventoryModel->setData('quantity_in_stock', $newQty);

                if ($advancedInventoryModel->getData('backorder_allowed') > 0) {
                    $advancedInventoryModel->setData('backorder_allowed', 0);
                    $advancedInventoryModel->setData('backorder_limit', 0);
                    $advancedInventoryModel->setData('backorder_expire', null);
                }

                $advancedInventoryModel->setData('manage_stock', $item[self::RIKI_IMPORT_CSV_KEY_MANAGE_STOCK]);
                $advancedInventoryModel->save();

                $this->wyomindStockRepository->updateInventory($productId, $item[self::RIKI_IMPORT_CSV_KEY_IS_IN_STOCK]);
                return self::RIKI_IMPORT_SUCCESS;
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
                return self::RIKI_IMPORT_ERROR_UNKNOWN;
            }
        } else {
            if ($productId === null) {
                return self::RIKI_IMPORT_ERROR_INVALID_PRODUCT_SKU;
            } elseif ($placeId === null) {
                return self::RIKI_IMPORT_ERROR_INVALID_WAREHOUSE_CODE;
            } elseif ($productId && $placeId) {
                return $this->checkPreOrderProductAndAdd($item, $productId, $placeId);
            } else {
                return self::RIKI_IMPORT_ERROR_UNKNOWN;
            }
        }
    }

    public function getProductIdFromProductSku($productSku)
    {

        /** Apply filters here */
        try {
            return $this->productRepositoryInterface->get($productSku)->getId();
        } catch (\Exception $e) {
            // Sku is wrong or product not exits
            return null;
        }
    }

    public function getWarehouseIdFromWarehouseCode($warehouseCode)
    {
        /** @var \Wyomind\AdvancedInventory\Model\ResourceModel\PointOfSale\Collection $collection */
        $collection = $this->_pointOfSalesCollectionFactory->create()
            ->addFieldToFilter('store_code', $warehouseCode)
            ->setPageSize(1);
        $model = $collection->getFirstItem();

        return $model->getData('place_id');
    }

    /**
     * @return array
     */
    public function getWarehouseIdByCode()
    {
        $collection = $this->_pointOfSalesCollectionFactory->create();
        $whData = array();
        if ($collection->getSize()) {
            foreach ($collection as $_wh) {
                $key = strtolower($_wh->getStoreCode());
                $whData[$key] = $_wh->getPlaceId();
            }
        }
        return $whData;
    }

    /**
     * EMAIL AREA
     */

    /**
     * @return mixed
     */
    public function getSenderName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
    }

    /**
     * @return mixed
     */
    public function getSenderEmail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
    }

    /**
     * @return mixed
     */
    public function getEmailAlert()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $emailAlert = $this->scopeConfig->getValue(self::RIKI_IMPORT_EMAIL_RECEIVER, $storeScope);
        return explode(',', $emailAlert);
    }

    /**
     * @param $emailTemplateVariables
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables, $templateId = null)
    {

        $senderInfo = [
            'name' => $this->getSenderName(), 'email' => $this->getSenderEmail()
        ];
        if (!$templateId) {
            $templateId = $this->getSystemConfig(self::RIKI_IMPORT_EMAIL_TEMPLATE);
        }
        $template = $this->_transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($this->getEmailAlert());

        return $this;
    }

    /**
     * @param $emailTemplateVariables
     */
    public function sendMailResult($emailTemplateVariables)
    {
        $this->logger->info('Call to send email log');
        try {
            $this->inlineTranslation->suspend();
            $this->generateTemplate($emailTemplateVariables);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $this->logger->info('send email log success');
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            $this->_logger->critical($e);
        }
    }

    /**
     * @param $emailTemplateVariables
     */
    public function sendMailStockResult($emailTemplateVariables)
    {
        $templateId = $this->getSystemConfig(self::RIKI_IMPORT_EMAIL_TEMPLATE_STOCK);
        try {
            $this->inlineTranslation->suspend();
            $this->generateTemplate($emailTemplateVariables, $templateId);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            $this->_logger->critical($e);
        }
    }

    /**
     * @param $productSku
     * @return bool|\Magento\Catalog\Model\AbstractModel
     */
    public function loadProductBySku($productSku)
    {
        $product = $this->_productFactory->create();
        return $product->loadByAttribute('sku', $productSku);
    }

    /**
     * @param $item
     * @param $productId
     * @param $placeId
     * @return int
     */
    public function checkPreOrderProductAndAdd($item, $productId, $placeId)
    {
        try{
            $productObj = $this->productRepositoryInterface->getById($productId);
        }catch (\Exception $e){
            $this->_logger->info($e);
            return self::RIKI_IMPORT_ERROR_INVALID_PRODUCT_SKU;
        }

        $isPreOrder = $this->_preOrderHelper->getIsProductPreorder($productObj);
        if ($isPreOrder == true) {
            $modelItem = $this->_advancedInventoryModelItem;
            $modelItem->setData('product_id', $productId);
            $modelItem->setData('multistock_enabled', 1);
            try {
                $modelItem->save();
                $inventory = $this->_stockRegistry->getStockItem($productId);
                if ($inventory) {
                    $inventory->setBackorders(0);
                    $inventory->save();
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_logger->critical($e);
            } catch (\RuntimeException $e) {
                $this->_logger->critical($e);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }

        return $this->addNewStock($item, $productId, $placeId);
    }

    /**
     * @param $item
     * @param $productId
     * @param $placeId
     * @return int
     */
    public function addNewStock($item, $productId, $placeId)
    {
        $modelStock = $this->_advancedInventoryStockModelFactory->create();
        $itemCollection = $this->_advancedInventoryItemCollectionFactory->create()
            ->addFieldToFilter('product_id', $productId);
        if ($itemCollection->getSize() > 0) {
            $itemId = $itemCollection->getFirstItem()->getId();
            $modelStock->setData('product_id', $productId);
            $modelStock->setData('item_id', $itemId);
            $modelStock->setData('place_id', $placeId);
            $modelStock->setData('manage_stock', $item[self::RIKI_IMPORT_CSV_KEY_MANAGE_STOCK]);
            $modelStock->setData('quantity_in_stock', $item[self::RIKI_IMPORT_CSV_KEY_QTY]);
            $modelStock->setData('backorder_allowed', 0);
            $modelStock->setData('use_config_setting_for_backorders', 1);
            try {
                $modelStock->save();
                $this->wyomindStockRepository->updateInventory($productId, $item[self::RIKI_IMPORT_CSV_KEY_IS_IN_STOCK]);
                return self::RIKI_IMPORT_SUCCESS;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_logger->critical($e);
                return self::RIKI_IMPORT_ERROR_UNKNOWN;
            } catch (\RuntimeException $e) {
                $this->_logger->critical($e->getMessage());
                return self::RIKI_IMPORT_ERROR_UNKNOWN;
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                return self::RIKI_IMPORT_ERROR_UNKNOWN;
            }
        } else {
            // Case now product stock is manage by magento
            try {
                $multiStock = 1;
                $this->wyomindStockRepository->updateStock(
                    $productId, $multiStock, $placeId, $item[self::RIKI_IMPORT_CSV_KEY_MANAGE_STOCK], $item[self::RIKI_IMPORT_CSV_KEY_QTY]
                );

                $this->wyomindStockRepository->updateInventory($productId, $item[self::RIKI_IMPORT_CSV_KEY_IS_IN_STOCK]);
                return self::RIKI_IMPORT_SUCCESS;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_logger->critical($e);
                return self::RIKI_IMPORT_ERROR_UNKNOWN;
            } catch (\RuntimeException $e) {
                $this->_logger->critical($e->getMessage());
                return self::RIKI_IMPORT_ERROR_UNKNOWN;
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                return self::RIKI_IMPORT_ERROR_UNKNOWN;
            }
        }
    }

    /**
     * @param $needDate
     * @param $filenameLog
     */
    public function backupLog($needDate, $filenameLog)
    {
        /**
         * Read current log file and import to backup file in the same day of filename.
         */
        $backupFolder = '/log/ImportStockBackup/';
        $fileSystem = new File();
        $newFile = 'ImportStock_' . '_' . $needDate . '.log';
        $writer = $this->_fileSystem->getDirectoryWrite
        (
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
        $backupLog = $writer->openFile($backupFolder . $newFile, 'a+');
        $backupLog->lock();
        $varDir = $this->_directoryList->getPath
        (
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );
        $fileLog = $varDir . '/log/' . $filenameLog;
        if ($fileSystem->isExists($fileLog)) {
            //read current file and write to backup file
            $reader = $this->_fileSystem->getDirectoryRead
            (
                \Magento\Framework\App\Filesystem\DirectoryList::ROOT
            );
            $contentLog = $reader->openFile('/var/log/' . $filenameLog, 'r')->readAll();
            $backupLog->write($contentLog);
            $backupLog->close();
            $fileSystem->deleteFile($fileLog);
        }
    }

    /**
     * validate stock status for bundle product children
     *
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return bool
     */
    public function isInStockBundleItem(\Magento\Quote\Model\Quote\Item $quoteItem){

        // only check bundle product have over 2 items
        if(
            $quoteItem->getProductType() != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE ||
            !$quoteItem->getHasChildren() ||
            count($quoteItem->getChildren()) < 2
        )
            return true;

        $storeId = $quoteItem->getStoreId();

        $places = $this->_posFactory->create()->getPlacesByStoreId($storeId);

        $quoteItemToInStockPlaces = [];

        if($this->scopeConfig->getValue('advancedinventory/settings/multiple_assignation_enabled')){
            foreach($quoteItem->getChildren() as $child){
                foreach ($places as $place) {
                    $productId = $child->getProductId();
                    $childId = $child->getId();

                    $available = $this->_assignationModel->checkAvailability($productId, $place->getPlaceId(), $child->getQty(), false);

                    if ($available['status'] > 1) {

                        if (!isset($quoteItemToInStockPlaces[$childId]))
                            $quoteItemToInStockPlaces[$childId] = [];

                        $quoteItemToInStockPlaces[$childId][] = $place->getPlaceId();
                    }
                }
            }

            $intersected = array_shift($quoteItemToInStockPlaces);

            foreach($quoteItemToInStockPlaces as $childId   =>  $inStockPlaceIds){
                $intersected = array_intersect($intersected, $inStockPlaceIds);

                if(count($intersected) == 0)
                    return false;
            }
        }

        return true;
    }
}
