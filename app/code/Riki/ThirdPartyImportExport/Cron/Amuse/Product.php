<?php
namespace Riki\ThirdPartyImportExport\Cron\Amuse;
class Product {

    const CODE_STORE = 'ec';

    const MAXIMUM_LIMIT = 20;

    const DEFAULT_LOCAL_SAVE = 'var/asume';
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection
     */
    protected $_collection;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Store\Model\StoreRepository
     */
    protected $_storeRepository;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_datetime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timeZone;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var string
     */
    protected $_path;
    /**
     * @var string
     */
    protected $pathTmp;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_file;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\Asume\LoggerCSV
     */
    protected $_log;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Asume\ProductHelper
     */
    protected $_productHelper;
    /**
     * @var \Magento\Framework\App\ResourceConnection $_resourceConnection
     */
    protected $_resourceConnection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * Product constructor.
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\Collection $collection
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Store\Model\StoreRepository $storeRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Riki\ThirdPartyImportExport\Logger\Asume\LoggerCSV $logger
     * @param \Riki\ThirdPartyImportExport\Helper\Asume\ProductHelper $productHelper
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Item\Collection $collection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\StoreRepository $storeRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Riki\ThirdPartyImportExport\Logger\Asume\LoggerCSV $logger,
        \Riki\ThirdPartyImportExport\Helper\Asume\ProductHelper $productHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->_collection  = $collection;
        $this->_productFactory = $productFactory;
        $this->_storeRepository = $storeRepository;
        $this->_datetime = $dateTime;
        $this->_timeZone = $timezone;
        $this->_directoryList = $directoryList;
        $this->_log = $logger;
        $this->_log->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_productHelper = $productHelper;
        $this->_file = $file;
        $this->_resourceConnection = $resourceConnection;
    }

    public function execute()
    {

        if(!$this->_productHelper->isEnable()){
            return false;
        }
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->_productHelper->backupLog('asume_export_product',$this->_log);
        $this->createFolderLocal();
        $arrayItemProduct = $this->getLatestItemProduct();
        $dom = new \DomDocument("1.0", "UTF-8");
        $dom->version  = "1.0";
        $dom->encoding = "UTF-8";
        $noteElem  = $dom->createElement('OrderXml');
        foreach($arrayItemProduct as $item){
            if($storeIdEc = $this->getStoreIdByCode()){
                $noteElem1  = $noteElem->appendChild($dom->createElement('Commodity'));
                $noteElem1->appendChild($dom->createElement('CommodityName', $item['sku']));
                $noteElem1->appendChild($dom->createElement('OrderDate',$item['order_created_at'])); //
                $noteElem1->appendChild($dom->createElement('URL',$item['url']));
                $dom->appendChild($noteElem);
            }
        }
        $dom->formatOutput = true; // this adds spaces, new lines and makes the XML more readable format.
        $dom->saveXML(); // $xmlString contains the entire String
        //create Name csv
        $nameXml = 'products-'.$this->_timeZone->date()->format('YmdHis').'.xml';
        if (!$dom->save($baseDir . DS . $this->pathTmp . DS . $nameXml)) {
            //sent mail to admin if export csv fail
            $this->_productHelper->sentMail();
        }
        $pathFtp = $this->_productHelper->getSFTPPathExport();
        $this->_productHelper->MoveFileToFtp('product',$this->pathTmp,$this->_path,$pathFtp,$this->_log);
    }

    /**
     * @return bool|int
     */
    public function getStoreIdByCode(){
        try{
            /**
             * @var \Magento\Store\Model\Store
             */
            $store = $this->_storeRepository->get(self::CODE_STORE);
        }catch (\Magento\Framework\Phrase\NoSuchEntityException $e){
            //write file log
            $this->_log->info(__('Store is not exist'));
            return false;
        }
        return $store->getId();
    }

    /**
     * create folder to save file
     */
    public function createFolderLocal(){
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $localCsv = $this->_productHelper->getLocalPathExport();
        if(!$localCsv){
            $createFileLocal[] = $baseDir . DS .self::DEFAULT_LOCAL_SAVE;
            $createFileLocal[] = $this->pathTmp = $baseDir . DS .self::DEFAULT_LOCAL_SAVE . '_tmp';
            $this->_path = self::DEFAULT_LOCAL_SAVE;
            $this->pathTmp = self::DEFAULT_LOCAL_SAVE . '_tmp';
        }else{
            if(trim($localCsv,-1) == DS){
                $localCsv = str_replace(DS,'',$localCsv);
            }
            $createFileLocal[] = $baseDir . DS . $localCsv;
            $createFileLocal[] = $baseDir . DS . $localCsv . '_tmp';
            $this->_path = $localCsv;
            $this->pathTmp = $localCsv . '_tmp';
        }
        foreach($createFileLocal as $path){
            if(!$this->_file->isDirectory($path)){
                if(!$this->_file->createDirectory($path)){
                    $this->_log->info(__('Can not create dir file').$path);
                    // sent mail
                    return;
                }
            }
            if(!$this->_file->isWritable($path)){
                $this->_log->info(__('The folder have to change permission to 755').$path);
                //sent mail
                return ;
            }
        }
    }

    /**
     * @return array
     */
    public function getLatestOrder(){
        $storeId = $this->getStoreIdByCode();
        if(!$storeId){
            return;
        }
        $connection = $this->_getConnection();
        $select = $connection->select()
            ->from('sales_order',['entity_id','subscription_profile_id'])
            ->order('entity_id DESC')
            ->where('store_id = ?' , $storeId)
            ->limit(50);
        return $connection->fetchAll($select);
    }
    /**
     * get 1st order subscription/hanpukai
     * @return array
     */
    public function truncateDuplicateSubProfileId(){
        $subProfileId = $listOrder = [];
        $orders = array_reverse($this->getLatestOrder());
        foreach ($orders as $key => $order){
            if($order['subscription_profile_id']){
                if(in_array($order['subscription_profile_id'] , $subProfileId)){
                    continue;
                }else{
                    $subProfileId[] = $order['subscription_profile_id'];
                }
            }
            $listOrder[] = $order['entity_id'];
        }
        return $listOrder;
    }

    /**
     * @return array
     */
    public function getLatestItemProduct(){
        $storeId = $this->getStoreIdByCode();
        $result = [];
        $productAssigned = [];
        $orderIds = $this->truncateDuplicateSubProfileId();
        $connection = $this->_getConnection();
        $select = $connection->select();
        $select->from('sales_order_item',['product_id','sku','created_at'])
                ->where('order_id IN (?)', $orderIds)
                ->order('order_id Desc');
        $items = $connection->fetchAll($select);
        //only get 20 product visibility
        foreach($items as $item) :
            //if count result is 20 ,end the loop
            if(count($result) == self::MAXIMUM_LIMIT){
                break;
            }
            //skip if product_id is getted
            if(in_array($item['product_id'],$productAssigned)){
                continue;
            }
            /**
             * @var \Magento\Catalog\Model\Product
             */
            $product = $this->_productFactory->create()->load($item['product_id']);
            if($product->getId()){
                if($product->isVisibleInSiteVisibility()){
                    $productAssigned[] = $item['product_id'];
                    $result[$item['product_id']]['sku'] = $item['sku'];
                    $result[$item['product_id']]['order_created_at'] = $this->_datetime->date('Y-m-d h:i:s', $this->_timeZone->formatDateTime($item['created_at'], 2, 2));
                    $result[$item['product_id']]['url'] = $product->getUrlInStore(['_scope' => $storeId]); // url EC web
                }else{
                    continue;
                }
            }
        endforeach;
        return $result;
    }

    /**
     * Retrieve write connection instance
     *
     * @return bool|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function _getConnection()
    {
        if (null === $this->_connection) {
            $this->_connection = $this->_resourceConnection->getConnection('sales');
        }
        return $this->_connection;
    }
}