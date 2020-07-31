<?php
namespace Riki\MasterDataHistory\Observer\Product;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class ProductAttributeUpdateBefore extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-product';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistoryproduct';
    protected $_header = [];
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    public function __construct(
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_product = $product;
        $this->_productCollectionFactory = $productCollectionFactory;
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)){
            $dirFile = $configFolder;
        }else{
            $dirFile = self::DEFAULT_PATH_FOLDER;
        }
        $dirLocal = $this->createFileLocal($dirFile);
        if($dirLocal){
            $prepareData = $productData = [];
            $productIds = $observer->getProductIds();

            //add more column Data
            $addMoreData = [
                'user' => $this->getCurrentUserAdmin(),
                'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
                'action' => 'Update'
            ];

            $this->_prepareHeaderProduct();
            $productCollect = $this->_productCollectionFactory->create();
            $productCollect->addFieldToFilter('entity_id',['in' => $productIds]);
            foreach($productCollect->getItems() as $product){
                $productData = [];
                foreach ($this->_header as $attributeCode) {
                    if (!is_array($product->getData($attributeCode))) {
                        $productData[] = $product->getData($attributeCode);
                    } else {
                        $productData[] = '';
                    }
                }
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

}