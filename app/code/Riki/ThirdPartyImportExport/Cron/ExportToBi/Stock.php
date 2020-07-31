<?php
namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Stock
{
    const ADD_MORE_HEADER_FIELD = ['sku', 'warehouse_name'];
    const DEFAULT_LOCAL_SAVE = 'var/BI_EXPORT_STOCK';
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\GiftWrapping\LoggerCSV|\Riki\ThirdPartyImportExport\Logger\LoggerCSV
     */
    protected $_log;
    /**
     * @var DateTime
     */
    protected $_datetime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GiftWrappingHelper
     */
    protected $_dataHelperGift;
    /**
     * @var string
     */
    protected $_path;
    /**
     * @var string
     */
    protected $_pathTmp;
    /**
     * @var File
     */
    protected $_file;

    /**
     * @var \Magento\GiftWrapping\Model\WrappingFactory
     */
    protected $_wrappingFactory;
    /**
     * @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface
     */
    protected $_wrappingRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchBuilder;

    /**
     * @var \Magento\Framework\App\ResourceConnection $_resourceConnection
     */
    protected $_resourceConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_resourceConfig;

    /**
     * GiftWrapping constructor.
     * @param \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $dataHelper
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\GiftWrapping\LoggerCSV $logger
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory
     * @param \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param File $file
     * @param DateTime $datetime
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $dataHelper,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\Stock\LoggerCSV $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        File $file,
        DateTime $datetime,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig
    )
    {
        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
        $this->_dataHelper = $dataHelper;
        $this->_directoryList = $directoryList;
        $this->_log = $logger;
        $this->_log->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_datetime = $datetime;
        $this->_timezone = $timezone;
        $this->_file = $file;
        $this->_scopeConfig = $scopeConfig;
        $this->_resourceConnection = $resourceConnection;
        $this->_resourceConfig = $resourceConfig;

    }

    public function execute()
    {
        if (!$this->_dataHelper->isEnable()) {
            return false;
        }

        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $localCsv = $this->_scopeConfig->getValue('di_data_export_setup/data_cron_stock/csvexport_stock_folder_local', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        //get path save file on ftp
        $pathFtp = $this->_scopeConfig->getValue('di_data_export_setup/data_cron_stock/csvexport_stock_folder_ftp', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $pathReportFrp = $this->_scopeConfig->getValue('di_data_export_setup/data_cron_stock/csvexport_stock_folder_ftp_report', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $lastRunToCron = $this->_scopeConfig->getValue('di_data_export_setup/data_cron_stock/bi_last_run_to_cron', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        if (!$localCsv) {
            $createFileLocal[] = $baseDir . DS . self::DEFAULT_LOCAL_SAVE;
            $createFileLocal[] = $this->_pathTmp = $baseDir . DS . self::DEFAULT_LOCAL_SAVE . '_tmp';
            $this->_path = self::DEFAULT_LOCAL_SAVE;
            $this->_pathTmp = self::DEFAULT_LOCAL_SAVE . '_tmp';
        } else {
            if (trim($localCsv, -1) == DS) {
                $localCsv = str_replace(DS, '', $localCsv);
            }
            $createFileLocal[] = $baseDir . DS . $localCsv;
            $createFileLocal[] = $baseDir . DS . $localCsv . '_tmp';
            $this->_path = $localCsv;
            $this->_pathTmp = $localCsv . '_tmp';
        }
        //delete file log exits before to write new log file
        $this->_dataHelper->backupLog('bi_export_stock', $this->_log);
        foreach ($createFileLocal as $path) {
            if (!$this->_file->isDirectory($path)) {
                if (!$this->_file->createDirectory($path)) {
                    $this->_log->info(__('Can not create dir file') . $path);
                    return;
                }
            }
            if (!$this->_file->isWritable($path)) {
                $this->_log->info(__('The folder have to change permission to 755') . $path);
                return;
            }
        }
        $this->_csv = new \Magento\Framework\File\Csv(new File());
        $connection = $this->_getConnection();
        $describeTable = $connection->describeTable('advancedinventory_stock');
        $arrayHeader = array_keys($describeTable);
        //Header
        $arrayExport[0] = array_merge($arrayHeader, self::ADD_MORE_HEADER_FIELD);

        $select = $connection->select()
            ->from(['adv' => 'advancedinventory_stock'], '*')
            ->joinInner(['cpe' => 'catalog_product_entity'], 'cpe.entity_id = adv.product_id', 'sku')
            ->joinInner(['pos' => 'pointofsale'], 'pos.place_id = adv.place_id', 'name')
            // remove where condition , now , export all record when cron run
            ->where("cpe.type_id <>'bundle'");

        $resultStocks = $connection->fetchAll($select);
        $totalCount = count($resultStocks);
        if ($totalCount) {
            foreach ($resultStocks as $id => $stocks) {
                foreach ($stocks as $column => $value) {
                    if ($column == 'update_at') {
                        $stocks[$column] = $this->_datetime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($value, 2, 2));
                    }
                }
                $arrayExport[] = $stocks;
            }
        }
        //create Name csv
        $nameCsv = 'stock-' . $this->_timezone->date()->format('YmdHis') . '.csv';
        //Create csv
        if (!$this->_file->isExists($baseDir . DS . $this->_pathTmp . DS . $nameCsv)) {
            $this->_csv->saveData($baseDir . DS . $this->_pathTmp . DS . $nameCsv, $arrayExport);
        }
        //move file
        $this->_dataHelper->MoveOneFileToFtp($this->_pathTmp,$this->_path,$nameCsv,$pathFtp,  $this->_log, $pathReportFrp);
        //set last time to run
        $this->_resourceConfig->saveConfig('di_data_export_setup/data_cron_stock/bi_last_run_to_cron', $this->_dataHelper->getTimeByUtc(), 'default', 0);
        $this->_dataHelper->sentMail('bi_export_stock', $this->_log);
    }

    /**
     * Retrieve write connection instance
     *
     * @return bool|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function _getConnection()
    {
        if (null === $this->_connection) {
            $this->_connection = $this->_resourceConnection->getConnection();
        }
        return $this->_connection;
    }
}