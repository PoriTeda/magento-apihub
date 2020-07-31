<?php

namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class Promotion
{
    const DATETIME_COLUMN = 'promo_updated_at';
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $_catRulColFactory;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper
     */
    protected $_dataHelper;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\PromotionHelper
     */
    protected $_exportHelper;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csvProcessor;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;

    /**
     * @var \Riki\ThirdPartyImportExport\Logger\ExportToBi\Promotion\LoggerCSV
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_fileSystem;

    public function __construct(
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collectionFactory,
        \Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory $catRulColFactory,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\GlobalHelper $globalHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\PromotionHelper $exportHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Riki\ThirdPartyImportExport\Logger\ExportToBi\Promotion\LoggerCSV $loggerCSV,
        \Magento\Framework\Filesystem\Driver\File $fileSystem
    )
    {
        $this->_collectionFactory = $collectionFactory;
        $this->_catRulColFactory = $catRulColFactory;
        $this->_dataHelper = $globalHelper;
        $this->_exportHelper = $exportHelper;
        $this->_csvProcessor = $csvProcessor;
        $this->_dateTime = $dateTime;
        $this->_timezone = $timezone;
        $this->_directoryList = $directoryList;
        $this->_logger = $loggerCSV;
        $this->_fileSystem = $fileSystem;
    }

    /**
     * Cron export promotion to BI, from 2 tables catalogrule and salerule
     *
     * @return null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        if (!$this->_dataHelper->isEnable()) {
            return null;
        }
        $timeRunCron = $this->_dataHelper->getTimeByUtc();
        /** @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection $collection */
        $collection = $this->_collectionFactory->create();
        $collection->addFieldToSelect('*');

        $collection->getSelect()->joinLeft(
            ['amasty_promo' => $collection->getResource()->getTable('amasty_ampromo_rule')],
            'main_table.rule_id = amasty_promo.salesrule_id',
            ['present_commodity_code' => 'amasty_promo.sku']
        );
        $table = $collection->getResource()->getMainTable();
        /** @var \Magento\CatalogRule\Model\ResourceModel\Rule\Collection $catRulCollection */
        $catRulCollection = $this->_catRulColFactory->create();
        $catRulCollection->addFieldToSelect('*');

        $catTable = $catRulCollection->getResource()->getMainTable();

        $exportData = [];
        $saleRuleHeader = array_keys($collection->getResource()->getConnection()->describeTable($table));
        $catalogHeader = array_keys($catRulCollection->getResource()->getConnection()->describeTable($catTable));
        $header = array_unique(array_merge($saleRuleHeader, $catalogHeader));
        $header[] = 'present_commodity_code';
        $exportData[] = array_map(function($title) {
            return "promotion.{$title}";
        }, $header);
        //cart price rule
        foreach ($collection->getData() as $rule) {
            $item = [];
            foreach ($header as $column) {
                $value = isset($rule[$column]) ? $rule[$column] : NULL;
                if (!empty($value) && $column == self::DATETIME_COLUMN) {
                    /*convert date time column to config timezone*/
                    $item[] = $this->_dateTime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($value, 2, 2));
                } else {
                    $item[] = $value;
                }
            }
            $exportData[] = $item;
        }
        //catalog price rule
        foreach ($catRulCollection->getData() as $rule) {
            $item = [];
            foreach ($header as $column) {
                $value = isset($rule[$column]) ? $rule[$column] : NULL;
                if (!empty($value) && $column == self::DATETIME_COLUMN) {
                    /*convert date time column to config timezone*/
                    $item[] = $this->_dateTime->date('Y-m-d H:i:s', $this->_timezone->formatDateTime($value, 2, 2));
                } else {
                    $item[] = $value;
                }
            }
            $exportData[] = $item;
        }
        $rootFolder = $this->_directoryList->getRoot();
        $fileName = 'promotion-'.$this->_timezone->date()->format('YmdHis').'.csv';
        $localPath = $rootFolder . DS . $this->_exportHelper->getLocalPath();
        $tmpPath = $rootFolder . DS . $this->_exportHelper->getLocalPath() . '_tmp';
        $ftpPath = $this->_exportHelper->getFtpPath();
        $ftpReportPath = $this->_exportHelper->getReportPath();
        $this->_dataHelper->backupLog('bi_export_promotion', $this->_logger);
        if (!$this->_fileSystem->isDirectory($localPath)) {
            $this->_fileSystem->createDirectory($localPath);
        }
        if (!$this->_fileSystem->isDirectory($tmpPath)) {
            $this->_fileSystem->createDirectory($tmpPath);
        }
        if (!$this->_fileSystem->isWritable($localPath) || !$this->_fileSystem->isWritable($tmpPath)) {
            $this->_logger->info(sprintf('Missing write permission for this folder %s', $localPath));
            return null;
        }
        $filePath = $tmpPath . DS . $fileName;
        $this->_csvProcessor->saveData($filePath, $exportData);
        $this->_exportHelper->setLastCronRun($timeRunCron);
        $this->_dataHelper->MoveFileToFtp(
            'points',
            $this->_exportHelper->getLocalPath() . '_tmp',
            $this->_exportHelper->getLocalPath(),
            $ftpPath,
            $this->_logger,
            $ftpReportPath
        );
        $this->_dataHelper->sentMail('bi_export_promotion',$this->_logger);
        return $this;
    }
}