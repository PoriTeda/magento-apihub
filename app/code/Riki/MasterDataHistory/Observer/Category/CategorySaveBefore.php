<?php
namespace Riki\MasterDataHistory\Observer\Category;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class CategorySaveBefore extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-product';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistorycategory';
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $_jsonDecoder;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * CategorySaveBefore constructor.
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Serialize\Serializer\Json $decoder
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Serialize\Serializer\Json $decoder,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->_categoryFactory = $categoryFactory;
        $this->_jsonDecoder = $decoder;
        $this->_connection = $resourceConnection->getConnection();
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($this->_request->getParam('controllerModule') != 'Magento_ImportExport'){
            $action = $this->getActionName();
            /**
             * @var \Magento\Catalog\Model\Category $category
             */
            $category = $observer->getCategory();
            if($configFolder = $this->getConfig(self::CONFIG_PATH_FOLDER)){
                $dirFile = $configFolder;
            }else{
                $dirFile = self::DEFAULT_PATH_FOLDER;
            }
            $dirLocal = $this->createFileLocal($dirFile);
            if($dirLocal){
                // table catalog_category_product changed ,created file csv
                if($action == 'Update'){
                    $productCateAfterSave = null;
                    if (!empty($this->_request->getParam('vm_category_products'))) {
                        $productCateAfterSave = array_keys($this->_jsonDecoder->unserialize($this->_request->getParam('vm_category_products')));
                    }
                    $productCateBeforeSave = $this->getInfoCategoryProductBeforeSave($category);
                    //if all product unassign of category ,will be create csv delete
                    if(!$productCateAfterSave){
                        $data = [
                            'user' => $this->getCurrentUserAdmin(),
                            'time' => $this->_datetime->date()->format('Y-m-d H:i:s'),
                            'action' => 'Delete'
                        ];
                        $header[] =  array_keys($data);
                        $dataExport[] = array_values($data);
                        $prepareData = array_merge($header,$dataExport);
                        $nameCsv = $this->_datetime->date()->getTimestamp() . '-category-product.csv';
                        $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
                    }else{
                        if(array_diff($productCateAfterSave,$productCateBeforeSave) || array_diff($productCateBeforeSave, $productCateAfterSave)){
                            //Create file csv catalog_category_product
                            $this->createCsvCategoryProduct($category->getId(), $dirLocal);
                        }
                    }

                }
            }
        }
    }

    /**
     * @param $categoryId
     * @param $dirLocal
     */
    public function createCsvCategoryProduct($categoryId, $dirLocal){
        $productIds = $this->_jsonDecoder->unserialize($this->_request->getParam('vm_category_products'));
            $header[] = [
                'category_id',
                'product_id',
                'position',
                'user',
                'time',
                'action'
            ];
            foreach ($productIds as $productId => $position){
                $data[] = [
                    $categoryId,
                    $productId,
                    $position,
                    $this->getCurrentUserAdmin(),
                    $this->_datetime->date()->format('Y-m-d H:i:s'),
                    $this->getActionName()
                ];
            }
            $prepareData = array_merge($header,$data);
            $nameCsv = $this->_datetime->date()->getTimestamp() . '-category-product.csv';
            $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return array
     */
    public function getInfoCategoryProductBeforeSave(\Magento\Catalog\Model\Category $category){
        $productId = [];
        if($category->getId()){
            $collecttionProduct = $category->getProductCollection();
            /**
             * @var \Magento\Catalog\Model\Product $product
             */
            foreach ($collecttionProduct->getItems() as $product){
                $productId[] = (int)$product->getId();
            }
        }
        return $productId;
    }


}