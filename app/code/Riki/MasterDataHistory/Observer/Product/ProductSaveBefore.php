<?php
namespace Riki\MasterDataHistory\Observer\Product;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class ProductSaveBefore extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-product';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistoryproduct';
    /**
     * @var ProductFactory
     */
    protected $_product;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    /**
     * @var int
     */
    protected $_timeStamp;
    /**
     * @var request
     */
    protected $_request;
    /* @var \Magento\CatalogInventory\Api\StockStateInterface */
    protected $stockStateRepository;
    /**
     * @var DateTime
     */
    protected $_datetime;

    /**
     * ProductSaveBefore constructor.
     * @param ProductFactory $productFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState
    )
    {
        $this->_product = $productFactory;
        $this->_connection = $resourceConnection->getConnection();
        $this->_request = $request;
        $this->stockStateRepository = $stockState;
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->getActionName() == 'Update') {
            $products = $this->_request->getParam('product');
            $this->_initTimeStamp();
            $product = $observer->getProduct();
            if ($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)) {
                $dirFile = $configFolder;
            } else {
                $dirFile = self::DEFAULT_PATH_FOLDER;
            }
            $dirLocal = $this->createFileLocal($dirFile);
            if ($dirLocal) {
                $productCateAfterSave = $product->getCategoryIds();
                $productCateBeforeSave = $this->getInfoCateProductBeforeSave($product->getId());
                if (array_diff($productCateBeforeSave, $productCateAfterSave) || array_diff($productCateAfterSave, $productCateBeforeSave)) {
                    $this->createCsvCategoryProduct($product->getCategoryIds(), $product->getId(), $dirLocal);
                }
                // Export csv if stock have anychange

                if(isset($products['quantity_and_stock_status']['qty'])){
                    $stockAfterChange = (int)$products['quantity_and_stock_status']['qty'];
                    $stockBeforeChange = (int)$this->stockStateRepository->getStockQty($product->getId(),$product->getStore()->getWebsiteId());
                    if($stockBeforeChange != $stockAfterChange){
                        $adjustStock = $stockBeforeChange - $stockAfterChange;
                        $this->exportStock($dirLocal,$product->getSku(),$adjustStock);
                    }
                }
            }
        }
    }
    /**
     * @param array $categoryIds
     * @param $productId
     * @param $dirLocal
     */
    public function createCsvCategoryProduct($categoryIds = [],$productId, $dirLocal){
        if ($categoryIds) {
            $header[] = [
                'category_id',
                'product_id',
                'position',
                'user',
                'time',
                'action'
            ];
            foreach ($categoryIds as $categoryId) {
                $data[] = [
                    $categoryId,
                    $productId,
                    1,
                    $this->getCurrentUserAdmin(),
                    $this->_datetime->date()->format('Y-m-d H:i:s'),
                    $this->getActionName()
                ];
            }
            $prepareData = array_merge($header, $data);
            $nameCsv = $this->_timeStamp . '-category-product.csv';
            $this->_csv->saveData($dirLocal . DS . $nameCsv, $prepareData);
        }
    }

    /**
     * @param int $productId
     * @return array
     */
    public function getInfoCateProductBeforeSave($productId){
        $productBeforeSave = $this->_product->create()->load($productId);
        return  $productBeforeSave->getCategoryIds();
    }
    public function _initTimeStamp(){
      $this->_timeStamp = $this->_datetime->date()->getTimestamp() ;
    }

    public function exportStock($dirLocal,$sku,$adjusted){
        $header[] = [
            'SKU_code',
            'the number of adjustment',
            'the Administrator’s ID',
            'adjusted date',
        ];
        $data[] = [
            $sku,
            $adjusted,
            $this->getCurrentUserAdmin(),
            $this->_datetime->date()->format('Y-m-d H:i:s'),
        ];
        $prepareData = array_merge($header, $data);
        $nameCsv = $this->_timeStamp . '-adjustment-stock.csv';
        $this->_csv->saveData($dirLocal . DS . $nameCsv, $prepareData);
    }
}