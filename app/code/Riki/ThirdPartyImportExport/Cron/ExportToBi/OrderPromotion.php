<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

use Magento\Framework\Exception\NoSuchEntityException;

class OrderPromotion
{
    const DEFAULT_LOCAL_SAVE = 'var/BI_EXPORT_ORDER_PROMOTION';
    const HEADER_FIELD_EXPORT_PROMOTION_ORDER =
        [   'order.increment_id',
            'order.created_at',
            'order.consumer_db_id',
            'order_item.sku',
            'order_item.name',
            'order.applied_rules_ids',
            'order_item.applied_rules_ids',
        ];
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_file;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_datetime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\OrderHelper
     */
    protected $_dataHelperOrder;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper
     */
    protected $_dataHelper;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\OrderPromotion\LoggerCSV
     */
    protected $_log;
    /**
     * @var
     */
    protected $_csv;
    /**
     * @var
     */
    protected $_baseDir;
    /**
     * @var
     */
    protected $_path;
    /**
     * @var
     */
    protected $pathTmp;

    /**
     * DB connection.
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    /**
     * @var array
     */
    protected $_listItemsExported = [];

    /**
     * OrderPromotion constructor.
     * @param \Magento\Framework\App\Filesystem\DirectoryList                         $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File                               $file
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                             $datetime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface                    $timezone
     * @param \Magento\Sales\Api\OrderRepositoryInterface                             $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder                            $searchCriteriaBuilder
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\OrderHelperPromotion       $dataHelperOrder
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper  $dataHelper
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\OrderPromotion\LoggerCSV $logger
     * @param \Magento\Framework\App\ResourceConnection                               $resourceConnection
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                         $productRepository
     * @param \Riki\Subscription\Model\Profile\ProfileRepository                      $profileRepository
     * @param \Magento\Framework\Api\FilterBuilder                                    $filterBuilder
     * @param \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface                $historyFactory
     * @param \Magento\Customer\Model\CustomerFactory                                 $modelCustomerFactory
     */
    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\OrderHelperPromotion $dataHelperOrder,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $dataHelper,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\OrderPromotion\LoggerCSV $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $historyFactory,
        \Magento\Customer\Model\CustomerFactory $modelCustomerFactory,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_datetime = $datetime;
        $this->_timezone = $timezone;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_dataHelperOrder = $dataHelperOrder;
        $this->_dataHelper = $dataHelper;
        $this->_log = $logger;
        $this->_log->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_connection = $resourceConnection->getConnection();
        $this->_productRepository = $productRepository;
        $this->_profileRepository = $profileRepository;
        $this->_filterBuilder = $filterBuilder;
        $this->modelCustomerFactory = $modelCustomerFactory;
        $this->_salesConnection = $connectionHelper->getSalesConnection();

    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function _initExport(){

        $valid = true;

        $this->_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());

        $this->_baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);

        $localCsv = $this->_dataHelperOrder->getLocalPathExport();

        if(!$localCsv){
            $createFileLocal[] = $this->_baseDir . DS .self::DEFAULT_LOCAL_SAVE;
            $createFileLocal[] = $this->pathTmp = $this->_baseDir . DS .self::DEFAULT_LOCAL_SAVE . '_tmp';
            $this->_path = self::DEFAULT_LOCAL_SAVE;
            $this->pathTmp = self::DEFAULT_LOCAL_SAVE . '_tmp';
        }else{
            if(trim($localCsv,-1) == DS){
                $localCsv = str_replace(DS,'',$localCsv);
            }
            $createFileLocal[] = $this->_baseDir . DS . $localCsv;
            $createFileLocal[] =  $this->_baseDir . DS . $localCsv . '_tmp';
            $this->_path = $localCsv;
            $this->pathTmp = $localCsv . '_tmp';
        }

        foreach($createFileLocal as $path){
            if(!$this->_file->isDirectory($path)){
                if(!$this->_file->createDirectory($path)){
                    $this->_log->info(__('Can not create dir file').$path);
                    $valid = false;
                }
            }
            if(!$this->_file->isWritable($path)){
                $this->_log->info(__('The folder have to change permission to 755').$path);
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute(){
        if(!$this->_dataHelper->isEnable() || !$this->_initExport()){
            return false;
        }
        $orderExport = [];

        foreach(self::HEADER_FIELD_EXPORT_PROMOTION_ORDER as $key => $columnName){
            $headerColumnName[$key] =  $columnName;
        }
        $orderExport[0] =  $headerColumnName;

        $filter = $this->_searchCriteriaBuilder->addFilter('is_promotion_exported',0);
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orders */
        $orders = $this->_orderRepository->getList($filter->create());
        /** @var \Magento\Sales\Model\Order $order*/
        $orderList = [];
        if ($orders->getSize()) {
            foreach ($orders as $order) {
                if(isset($order['customer_id']) && $order['customer_id']){
                    $customerOrder =  $this->modelCustomerFactory->create()->load($order['customer_id']);
                    $consumerId = $customerOrder->getData('consumer_db_id');
                }else{
                    $consumerId = '';
                }

                if($order->getAllItems()){
                    foreach ($order->getAllItems() as $orderItem){
                        if(!isset($this->_listItemsExported[$order->getIncrementId().$orderItem->getSku()])){
                            $data = [];
                            $data[] = $order->getIncrementId();
                            $data[] = $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($order->getCreatedAt(), 2, 2));
                            $data[] = $consumerId;
                            $data[] = $orderItem->getSku();
                            $data[] = !empty($orderItem->getProduct()) ? $orderItem->getProduct()->getName() : $orderItem->getName();
                            $data[] = $order->getAppliedRuleIds();
                            $data[] = $orderItem->getData('applied_rules_catalog');
                            $orderExport[] = $data;
                            $this->_listItemsExported[$order->getIncrementId().$orderItem->getSku()] = $orderItem->getId();
                        }

                    }
                }
                array_push($orderList, $order->getId());
            }
        }

        $orderHeader = 'orderpromotion-'.$this->_timezone->date()->format('YmdHis').'.csv';

        if(!$this->_file->isExists($this->_baseDir.DS.$this->pathTmp.DS.$orderHeader)){
            $this->_csv->saveData($this->_baseDir.DS.$this->pathTmp.DS.$orderHeader,$orderExport);
        }


        $pathFtp = $this->_dataHelperOrder->getSFTPPathExport();
        $pathReportFtp = $this->_dataHelperOrder->getSFTPPathReportExport();
        $this->_dataHelper->MoveFileToFtp('order',$this->pathTmp,$this->_path,$pathFtp,$this->_log,$pathReportFtp);

        //set value exported promotion
        $this->updatePromotionExported($orderList);
        //set last time to run
        $this->_dataHelperOrder->setLastRunToCron($this->_dataHelper->getTimeByUtc());
        // Send mail
        $this->_dataHelper->sentMail('bi_export_order_promotion',$this->_log);
    }
    /**
     * After export promotion, change is_promotion_exported to 1
     *
     * @param $orderList
     */
    public function updatePromotionExported($orderList)
    {
        /*update data to make system know this record is exported to BI or promotion*/
        if (!empty( $orderList)) {
            $table = $this->_salesConnection->getTableName('sales_order');
            $bind = [ 'is_promotion_exported' => 1 ];
            $where = ['entity_id IN (?)' => $orderList];
            $this->_salesConnection->update($table, $bind, $where);
        }
    }




}