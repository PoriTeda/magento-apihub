<?php

namespace Riki\Customer\Cron;

class Rma
{
    const DEFAULT_LOCAL_SAVE = 'var/cedyna_shosha_rma';

    const CONFIG_PATH_FTP_CSV = 'export_shosha/folder_setting_return/folder_ftp';
    const CONFIG_PATH_LOCAL_CSV = 'export_shosha/folder_setting_return/folder_local';
    const CONFIG_CRON_RUN_LAST_TIME = 'export_shosha/folder_setting_return/cron_last_time_run';

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_resourceConfig;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_file;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper
     */
    protected $_dataHelper;
    /**
     * @var \Riki\Customer\Logger\Shosha\LoggerCSV
     */
    protected $_logger;

    /*csv processor*/
    protected $_csv;

    protected $_baseDir;
    protected $_path;
    protected $_pathTmp;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $_shipmentCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $_rmaCollectionFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * Shipment constructor.
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $dataHelper
     * @param \Riki\Customer\Logger\Shosha\LoggerCSV $logger
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shippingCollectionFactory
     * @param \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $dataHelper,
        \Riki\Customer\Logger\Shosha\LoggerCSV $logger,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shippingCollectionFactory,
        \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $itemCollection,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Customer\Model\ShoshaFactory $shoshaFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->_connection = $resourceConnection->getConnection();
        $this->_resourceConfig = $resourceConfig;
        $this->_scopeConfig = $scopeConfig;
        $this->_dateTime = $dateTime;
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_dataHelper = $dataHelper;
        $this->_logger = $logger;
        $this->_shipmentCollectionFactory = $shippingCollectionFactory;
        $this->_rmaRepository = $rmaRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_itemCollection = $itemCollection;
        $this->_shoshaFactory = $shoshaFactory;
        $this->_customerRepository = $customerRepository;
    }

    /**
     * _initExport
     *
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function initExport(){

        $valid = true;

        $this->_csv = new \Magento\Framework\File\Csv(new \Magento\Framework\Filesystem\Driver\File());

        $this->_baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);

        $localCsv = $this->getLocalPathExport();

        if (!$localCsv) {
            $this->_path = self::DEFAULT_LOCAL_SAVE;
        } else {
            if (trim($localCsv, -1) == DS) {
                $localCsv = str_replace(DS, '', $localCsv);
            }
            $this->_path = $localCsv;
        }

        $this->_pathTmp = $this->_path . '_tmp';

        //delete file log exits before to write new log file
        $this->_dataHelper->backupLog('cedyna_export_shosha', $this->_logger);

        if (!$this->_file->isDirectory($this->_baseDir. DS .$this->_path)) {
            if (!$this->_file->createDirectory($this->_baseDir. DS .$this->_path)) {
                $this->_logger->info(__('Can not create dir file') . $this->_path);
                $valid = false;
            }
        } else {
            if (!$this->_file->isWritable($this->_baseDir. DS .$this->_path)) {
                $this->_logger->info(__('The folder have to change permission to 755') . $this->_path);
                $valid = false;
            }
        }

        if (!$this->_file->isDirectory($this->_baseDir. DS .$this->_pathTmp)) {
            if (!$this->_file->createDirectory($this->_baseDir. DS .$this->_pathTmp)) {
                $this->_logger->info(__('Can not create dir file') . $this->_pathTmp);
                $valid = false;
            }
        } else {
            if (!$this->_file->isWritable($this->_baseDir. DS .$this->_pathTmp)) {
                $this->_logger->info(__('The folder have to change permission to 755') . $this->_pathTmp);
                $valid = false;
            }
        }

        return $valid;
    }


    /**
     * @return $this
     */
    public function execute(){

        /*prepare data and folder for export*/
        if (!$this->initExport()) {
            return false;
        }

        /*export process*/
        $this->exportProcess();

        /* set last time to run */
        $this->setLastRunToCron($this->_dateTime->gmtDate('Y-m-d H:i:s'));

        /*move export folder to ftp*/
        $this->_dataHelper->MoveFileToFtp('shosha', $this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->_logger);
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function exportProcess(){

        $exportData = $this->_loadDataReturn();

        $exportDate = $this->_dateTime->date('Ymd');
        $exportFile = 'returnheader-'.$exportDate.'.csv';
        //Create csv
        if (!$this->_file->isExists($this->_baseDir . DS . $this->_pathTmp . DS . $exportFile)) {
            $this->_csv->saveData($this->_baseDir . DS . $this->_pathTmp . DS . $exportFile, $exportData);
        }
    }

    /**
     * @param string $fileName
     * @return array
     */
    protected function _loadDataReturn(){
        //get collection join

        $criteria = $this->_searchCriteriaBuilder->addFilter(
            'return_approval_date',
            $this->getLastRunToCron(),
            'gteq'
            //'approval_date',0, 'gteq'
        )->create();

        $rmaCollection = $this->_rmaRepository->getList($criteria);

        $aRmaIds = [];
        if ($rmaCollection->getTotalCount()) {
            foreach ($rmaCollection->getItems() as $rma) {
                $isCedyna = true;
                try {
                    $customer = $this->_customerRepository->getById($rma->getCustomerId());


                    if ($customer) {
                        $oShoshaBusinessCode = $customer->getCustomAttribute('shosha_business_code');
                        if ($oShoshaBusinessCode) {
                            $oShoshaCollection = $this->_shoshaFactory->create()->getCollection()->addFieldToFilter('shosha_business_code', ['in' => [$oShoshaBusinessCode->getValue()]]);
                            if ($oShoshaCollection->getSize()) {
                                foreach ($oShoshaCollection as $shosha) {
                                    if (\Riki\Customer\Model\Shosha\ShoshaCode::CEDYNA == $shosha->getData('shosha_code')) {
                                        $isCedyna = true;
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->_logger->critical($e->getMessage());
                }
                $order = $rma->getOrder();
                $payment = $order->getPayment();
                $paymentMethod = '';
                if ($payment) {
                    $paymentMethod = $payment->getMethodInstance()->getCode();
                }

                if ($isCedyna && \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_INVOICED == $paymentMethod) {
                    $this->_loadDataReturnDetail($rma->getId(), $rma->getIncrementId());

                    $aRmaIds[] = $rma->getId();
                }
            }
        }

        //list shipment id
        $table = $this->_connection->getTableName('magento_rma');

        $columns = array_keys($this->_connection->describeTable($table));

        $resultData = [$columns];

        if ($aRmaIds) {
            $select = $this->_connection->select()->from(
                $table,
                $columns
            )->where(
                $table . '.entity_id IN (?)',
                $aRmaIds
            );

            $query = $this->_connection->query($select);

            while ($row = $query->fetch()) {
                $resultData[] = $row;
            }
        }

        return $resultData;
    }

    /**
     * _loadDataReturnDetail
     *
     * @param $iRmaId
     * @param $iReturnIncrementId
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function _loadDataReturnDetail($iRmaId, $iReturnIncrementId){

        $aReturnDetailData = [];
        $collectionRma = $this->_itemCollection->create();
        $collectionRma->addFieldToFilter('rma_entity_id', $iRmaId);
        if ($collectionRma->getSize()) {
            $i = 0;
            foreach ($collectionRma as $rmaItem) {
                if ($i == 0) {
                    $columns = array_keys($rmaItem->getData());
                    $aReturnDetailData = [$columns];
                }
                array_push($aReturnDetailData, $rmaItem->getData());
                $i++;
            }
        }

        $exportDate = $this->_dateTime->date('Ymd');
        $exportFile = 'returndetail-'.$iReturnIncrementId.'-'.$exportDate.'.csv';

        //Create csv
        if (!$this->_file->isExists($this->_baseDir . DS . $this->_pathTmp . DS . $exportFile)) {
            $this->_csv->saveData($this->_baseDir . DS . $this->_pathTmp . DS . $exportFile, $aReturnDetailData);
        }
    }
    /**
     * @return mixed
     */
    public function getLocalPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->_scopeConfig->getValue(self::CONFIG_PATH_LOCAL_CSV, $storeScope);
        return $LocalPath;
    }
    /**
     * @return mixed
     */
    public function getSFTPPathExport()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $LocalPath = $this->_scopeConfig->getValue(self::CONFIG_PATH_FTP_CSV, $storeScope);
        return $LocalPath;
    }
    /**
     * @return mixed
     */
    public function getLastRunToCron()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->_scopeConfig->getValue(self::CONFIG_CRON_RUN_LAST_TIME, $storeScope);
    }

    /**
     * @param $time
     */
    public function setLastRunToCron($time)
    {
        $this->_resourceConfig->saveConfig(self::CONFIG_CRON_RUN_LAST_TIME, $time, 'default', 0);
    }
}
