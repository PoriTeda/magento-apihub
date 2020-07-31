<?php
namespace Riki\MasterDataHistory\Observer\ImportProduct;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;

class ImportInsertUpdateProduct extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-product';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistoryproduct';
    protected $_productIdUpdate = [];
    protected $_productIdInsert = [];
    protected $_productCollection;
    protected $_header = [];
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * ImportInsertUpdateProduct constructor.
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProduct
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProduct,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_product = $product;
        $this->_productCollection = $collectionProduct;
        parent::__construct($directoryList, $file, $csv, $timezone, $authSession, $scopeConfig, $request);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $productIds = [];
        if($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)){
            $dirFile = $configFolder;
        }else{
            $dirFile = self::DEFAULT_PATH_FOLDER;
        }
        $dirLocal = $this->createFileLocal($dirFile);
        $productMethod = $observer->getProductmethod();
        foreach($productMethod as $key => $productArr){
            if($key == 'update'){
                foreach($productArr as $product){
                    $this->_productIdUpdate[] = $product['entity_id'];
                }

            }else{
                foreach($productArr as $sku => $product){
                    $skus[] = $sku;
                }
                $this->_productIdInsert = $this->getListProductIdBySkus($skus);
            }
        }
        $productIds = array_merge($this->_productIdInsert,$this->_productIdUpdate);
        $collection = $this->_productCollection->create();
        $collection->addFieldToSelect('*')
            ->addFieldToFilter('entity_id', ['in' => $productIds]);
        if($collection->getSize()){
            $this->_prepareHeaderProduct();
            /**
             * @var \Magento\Catalog\Model\Product $product
             */
            foreach($collection->getItems() as $product){
                $productData = [];
                foreach ($this->_header as $attributeCode) {
                    if (!is_array($product->getData($attributeCode))) {
                        $productData[] = $product->getData($attributeCode);
                    }else {
                        $productData[] = '';
                    }
                }
                $addMoreData = [
                    'user' => $this->getCurrentUserAdmin(),
                    'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
                    'action' => $this->getMethodAction($product->getId())
                ];
                $dataExport[] = array_merge($productData,array_values($addMoreData));
            }
            $header[] = array_merge($this->_header, array_keys($addMoreData));
            $prepareData = array_merge($header,$dataExport);
            $nameCsv = $this->_datetime->date()->getTimestamp() . '-product.csv';
            $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
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
     * @param $productSku
     * @return string
     */
    public function getMethodAction($productId){
        if(in_array($productId, $this->_productIdInsert)){
            return 'Add';
        }
        if(in_array($productId, $this->_productIdUpdate)){
            return 'Update';
        }
    }
    /**
     * @param array $skus
     * @return array
     */
    public function getListProductIdBySkus($skus = []){
        $productIds = [];
        $result = $this->_product->getResource()->getProductsIdsBySkus($skus);

        foreach ($result as $productId){
            $productIds[] = $productId;
        }
        return $productIds;
    }
}