<?php
namespace Riki\MasterDataHistory\Observer\Product;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class ProductSaveAfter extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-product';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistoryproduct';
    protected $_header = [];
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * ProductSaveAfter constructor.
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->_product = $product;
        $this->_connection = $resourceConnection->getConnection();
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();
        if($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)){
            $dirFile = $configFolder;
        }else{
            $dirFile = self::DEFAULT_PATH_FOLDER;
        }
        $dirLocal = $this->createFileLocal($dirFile);
        if($dirLocal){
            $productData = [];
            //add more column Data
            $addMoreData = [
                'user' => $this->getCurrentUserAdmin(),
                'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
                'action' => $this->getActionName()
            ];
            $this->_prepareHeaderProduct();
            foreach ($this->_header as $attributeCode) {
                if (!is_array($product->getData($attributeCode))) {
                    $productData[] = $product->getData($attributeCode);
                } else {
                    $productData[] = '';
                }
            }
            $header[] = array_merge($this->_header, array_keys($addMoreData));
            $dataExport[] = array_merge($productData,array_values($addMoreData));
            $prepareData = array_merge($header,$dataExport);
            $nameCsv = $this->_datetime->date()->getTimestamp() . '-product.csv';
            $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
        }
        if($this->getActionName() == 'Add'){
            if (isset($this->_request->getParam('product')['category_ids'])) {
                $this->createCsvCategoryProduct($product->getId(), $dirLocal);
            }
        }
    }
    /**
     * @return $this
     */
    protected function _prepareHeaderProduct(){
        foreach ($this->_product->getAttributes() as $attribute) {
            $this->_header[] = $attribute->getAttributeCode();
        }
        return $this;
    }

    /**
     * @param $productId
     * @param $dirLocal
     */
    public function createCsvCategoryProduct($productId, $dirLocal){
        $select = $this->_connection->select();
        $select->from('catalog_category_product', '*')
            ->where('product_id = ?', $productId);
        $result = $this->_connection->fetchAll($select);
        if($result){
            $header[] = [
                'category_id',
                'product_id',
                'position',
                'user',
                'time',
                'action'
            ];
            foreach ($result as $value){
                $data[] = [
                    $value['category_id'],
                    $value['product_id'],
                    $value['position'],
                    $this->getCurrentUserAdmin(),
                    $this->_datetime->date()->format('Y-m-d H:i:s'),
                    $this->getActionName()
                ];
            }
            $prepareData = array_merge($header,$data);
            $nameCsv = $this->_datetime->date()->getTimestamp() . '-category-product.csv';
            $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
        }
    }
}