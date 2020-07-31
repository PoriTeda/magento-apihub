<?php
namespace Riki\MasterDataHistory\Plugin;

class Save {
    const DEFAULT_PATH_FOLDER = 'var/history-product';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistoryproduct';
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Riki\MasterDataHistory\Observer\MasterDataHistoryObserver
     */
    protected $_dataHistory;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csv;
    /**
     * @var \Magento\CatalogInventory\Model\StockRegistry|null
     */
    protected $_stockRegistry = null;
    /**
     * @var null|\Wyomind\AdvancedInventory\Model\Stock
     */
    protected $_stockModel = null;
    /**
     * @var DateTime
     */
    protected $_datetime;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\MasterDataHistory\Observer\MasterDataHistoryObserver $dataHistoryObserver,
        \Magento\Framework\File\Csv $csv,
        \Wyomind\AdvancedInventory\Model\Stock $stockModel,
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone
    ) {
        $this->_productFactory = $productFactory;
        $this->_dataHistory = $dataHistoryObserver;
        $this->_stockRegistry = $stockRegistry;
        $this->_csv = $csv;
        $this->_stockModel = $stockModel;
        $this->_datetime = $timezone;
    }
    public function beforeExecute(\Wyomind\AdvancedInventory\Controller\Adminhtml\Stocks $subject) {
        $data = json_decode($subject->getRequest()->getPost('data'));
        $storeId = $subject->getRequest()->getParam('store_id');
        $isAdmin = $subject->getRequest()->getParam('is_admin');
        foreach ($data as $productId => $productData) {
            $stock = $this->_stockModel->getStockSettings($productId, false, array_keys((array)$productData->pos_wh));

            // get qty
            if ($productData->multistock) {
                $qty = 0;
                $substract = 0;

                foreach ($productData->pos_wh as $posId => $pos) {
                    if ($storeId || !$isAdmin) {
                        $posQty = "getQuantity" . $posId;
                        $substract += $stock->$posQty();
                    }

                    $qty += $pos->qty;
                }
                if ($storeId || !$isAdmin) {
                    $qty = $stock->getQty() - $substract + $qty;
                }
            } else {
                $qty = $productData->qty;
            }
            // Update backorders status
            $inventory = $this->_stockRegistry->getStockItem($productId);
            // Update qty
            if ($inventory->getQty() != $qty) {
                if ($configFolder = $this->_dataHistory->getConfig(self::CONFIG_PATH_FOLDER)) {
                    $dirFile = $configFolder;
                } else {
                    $dirFile = self::DEFAULT_PATH_FOLDER;
                }
                $dirLocal = $this->_dataHistory->createFileLocal($dirFile);
                $adjustStock = $inventory->getQty() - $qty;
                $product = $this->_productFactory->create()->load($productId);
                if($product->getId()){
                    $this->exportStock($dirLocal,$product->getSku(),$adjustStock);
                }
            }
        }
        return [];
    }

    /**
     * @param $dirLocal
     * @param $productSku
     * @param $adjustStock
     */
    public function exportStock($dirLocal,$productSku,$adjustStock){
        $timeStamp = $this->_datetime->date()->getTimestamp() ;
        $header[] = [
            'SKU_code',
            'the number of adjustment',
            'the Administrator’s ID',
            'adjusted date',
        ];
        $data[] = [
            $productSku,
            $adjustStock,
            $this->_dataHistory->getCurrentUserAdmin(),
            $this->_datetime->date()->format('Y-m-d H:i:s'),
        ];
        $prepareData = array_merge($header, $data);
        $nameCsv = $timeStamp . '-adjustment-stock.csv';
        $this->_csv->saveData($dirLocal . DS . $nameCsv, $prepareData);
    }
}