<?php
namespace Riki\MasterDataHistory\Observer\Category;

use Magento\Framework\Event\ObserverInterface;
use Riki\MasterDataHistory\Observer\MasterDataHistoryObserver;
class CategorySaveAfter extends MasterDataHistoryObserver implements ObserverInterface
{
    const DEFAULT_PATH_FOLDER = 'var/history-product';
    const CONFIG_PATH_FOLDER = 'masterdatahistory/common/datahistorycategory';
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;
    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonDecoder;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * CategorySaveAfter constructor.
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Json\DecoderInterface $decoder
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
        \Magento\Framework\Json\DecoderInterface $decoder,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->_product = $product;
        $this->_jsonDecoder = $decoder;
        $this->_connection = $resourceConnection->getConnection();
        parent::__construct($directoryList, $file, $csv,$timezone,$authSession,$scopeConfig,$request);
    }

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
                $prepareData = $productData = [];

                $header[] = [
                    'category_id',
                    'category_name',
                    'user',
                    'time',
                    'action'
                ];
                $data[] = [
                    $category->getId(),
                    $category->getName(),
                    $this->getCurrentUserAdmin(),
                    $this->_datetime->date()->format('Y-m-d H:i:s'),
                    $action
                ];
                $prepareData = array_merge($header,$data);
                $nameCsv = $this->_datetime->date()->getTimestamp() . '-category.csv';
                $this->_csv->saveData($dirLocal.DS.$nameCsv,$prepareData);
            }
            // table catalog_category_product changed ,created file csv
            if($action == 'Add'){
                if($this->_request->getParam('vm_category_products')){
                    $this->createCsvCategoryProduct($category->getId() , $dirLocal);
                }
            }
        }
    }
    public function createCsvCategoryProduct($categoryId, $dirLocal){
        $result = [];
        $select = $this->_connection->select();
        $select->from('catalog_category_product', '*')
            ->where('category_id = ?', $categoryId);
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