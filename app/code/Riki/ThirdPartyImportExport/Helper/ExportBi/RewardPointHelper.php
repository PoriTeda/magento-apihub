<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

class RewardPointHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    /**
     * @var \Riki\Loyalty\Model\ResourceModel\Reward\CollectionFactory
     */
    protected $_collectionFactory;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    /*list columns which data type is datetime or timestamp, table riki_reward_point*/
    protected $_rewardPointDateTimeColumns;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Riki\Loyalty\Model\ResourceModel\Reward\CollectionFactory $collectionFactory,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        parent::__construct($context, $dateTime, $timezone, $resourceConfig, $sftpHelper, $fileHelper, $configHelper, $emailHelper, $dateTimeColumnsHelper, $connectionHelper);
        $this->_collectionFactory = $collectionFactory;
        $this->_connection = $connectionHelper->getSalesConnection();
    }

    /**
     * Export process
     */
    public function exportProcess()
    {
        $this->export();

        /* set last time to run */
        $this->setLastRunToCron();

        /*move export folder to ftp*/
        $this->_sftpHelper->moveFileToFtp($this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->getReportPathExport());

        /*send email notify*/
        $this->sentNotificationEmail();
    }

    /**
     * Cron export points to BI
     *
     * @return null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function export()
    {
        $lastCronRun = $this->getLastRunToCron();

        /** @var \Riki\Loyalty\Model\ResourceModel\Reward\Collection $collection */
        $collection = $this->_collectionFactory->create();
        $collection->addFieldToSelect('*');
        if ($lastCronRun) {
            $collection->addFieldToFilter('updated_at', ['gteq' => $lastCronRun]);
        }

        $exportData = [];

        $table = $collection->getResource()->getMainTable();
        $header = array_keys($this->_connection->describeTable($table));
        $exportData[] = array_map(function($title) {
            return "point.{$title}";
        }, $header);

        foreach ($collection->getData() as $reward) {
            $item = [];
            foreach ($header as $column) {
                if (!empty($reward[$column]) && $this->isDateTimeColumn($column)) {
                    $item[] = $this->convertToConfigTimezone($reward[$column]);
                } else {
                    $item[] = $reward[$column];
                }
            }
            $exportData[] = $item;
        }

        /*get export date via config timezone*/
        $exportDate = $this->_timezone->date()->format('YmdHis');
        /*export file name*/
        $rewardPointFileName = 'points-'.$exportDate.'.csv';

        /*export order header*/
        $this->createLocalFile([
            $rewardPointFileName => $exportData
        ]);
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_reward_point
     * @return mixed
     */
    public function getRewardPointDateTimeColumns()
    {
        if (empty($this->_rewardPointDateTimecolumns)) {
            $this->_rewardPointDateTimecolumns = $this->_dateTimeColumnsHelper->getRewardPointDateTimeColumns();
        }

        return $this->_rewardPointDateTimecolumns;
    }

    /**
     * check columns with data type is datetime or timestamp
     *
     * @param $cl
     * @return bool
     */
    public function isDateTimeColumn($cl)
    {
        $rewardPointDataTimeColumns = $this->getRewardPointDateTimeColumns();

        if (in_array($cl, $rewardPointDataTimeColumns)) {
            return true;
        }

        return false;
    }
}