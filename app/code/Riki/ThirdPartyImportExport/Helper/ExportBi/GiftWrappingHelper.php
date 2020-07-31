<?php
namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

class GiftWrappingHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    /**
     * @var \Magento\GiftWrapping\Model\Wrapping
     */
    protected $_wrapping;
    /*list columns which data type is datetime or timestamp, table magento_giftwrapping*/
    protected $_giftWrappingDateTimeColumns;

    const DEFAULT_UPDATED_VALUE = '0000-00-00 00:00:00';

    /**
     * GiftWrappingHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\GiftWrapping\Model\Wrapping $wrapping
     * @param GlobalHelper\SftpExportHelper $sftpHelper
     * @param GlobalHelper\FileExportHelper $fileHelper
     * @param GlobalHelper\ConfigExportHelper $configHelper
     * @param GlobalHelper\EmailExportHelper $emailHelper
     * @param GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\GiftWrapping\Model\Wrapping $wrapping,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        parent::__construct($context, $dateTime, $timezone, $resourceConfig, $sftpHelper, $fileHelper, $configHelper, $emailHelper, $dateTimeColumnsHelper, $connectionHelper);
        $this->_wrapping = $wrapping;
    }

    /**
     * Export process
     */
    public function exportProcess()
    {
        /*export main process*/
        $this->export();

        /* set last time to run */
        $this->setLastRunToCron();

        /*move export folder to ftp*/
        $this->_sftpHelper->moveFileToFtp($this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->getReportPathExport());

        /*send email notify*/
        $this->sentNotificationEmail();
    }

    public function export()
    {
        $resource = $this->_wrapping->getResource();
        /*giff wrapping table*/
        $mainTable = $resource->getMainTable();
        /*gift wrapping connection*/
        $connection = $resource->getConnection();
        /*table columns*/
        $tableColumns = array_keys($connection->describeTable($mainTable));

        /*list gift wrapping columns with prefix - add to export header*/
        $headerColumnName = [];

        foreach ($tableColumns as $key => $columnName) {
            $headerColumnName[$key] = 'giftwrapping.' . $columnName;
        }

        /*export data*/
        $arrayExport = [];

        /*push header data to export data*/
        array_push($arrayExport,$headerColumnName);

        /*build sql query to get data*/
        $select = $connection->select()->from(
            $mainTable, $tableColumns
        );

        /*get export data*/
        $giftWrapping = $connection->query($select);

        while ($row = $giftWrapping->fetch()) {
            /*push gift wrapping record to export data*/
            array_push(
                $arrayExport, $this->convertDateTimeColumnsToConfigTimezone($row)
            );
        }

        /*export file name*/
        $exportFileName = 'giftwrapping-'.$this->_timezone->date()->format('YmdHis').'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportFileName => $arrayExport
        ]);
    }

    /**
     * check columns with data type is datetime or timestamp
     *
     * @param array $object
     * @return array
     */
    public function convertDateTimeColumnsToConfigTimezone($object)
    {
        $giftWrappingDateTimeColumns = $this->getGiftWrappingDateTimeColumns();

        foreach ($giftWrappingDateTimeColumns as $cl) {
            if (!empty($object[$cl]) && $object[$cl] != self::DEFAULT_UPDATED_VALUE) {
                $object[$cl] = $this->convertToConfigTimezone($object[$cl]);
            }
        }

        return $object;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_customer_enquiry_header
     * @return mixed
     */
    public function getGiftWrappingDateTimeColumns()
    {
        if (empty($this->_giftWrappingDateTimeColumns)) {
            $this->_giftWrappingDateTimeColumns = $this->_dateTimeColumnsHelper->getGiftWrappingDateTimeColumns();
        }
        return $this->_giftWrappingDateTimeColumns;
    }

}