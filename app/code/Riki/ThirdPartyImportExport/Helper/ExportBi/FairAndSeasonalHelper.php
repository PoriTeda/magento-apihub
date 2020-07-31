<?php
namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

class FairAndSeasonalHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    /*default connection for all fair table*/
    protected $_connection;
    /*list columns which data type is datetime or timestamp, table riki_fair_**/
    protected $_fairAndSeasonalDateTimeColumns;

    /*list fair table which will get data to export*/
    protected $_fileToTable = [
        'fair_management'   =>  'riki_fair_management',
        'fair_connection'   =>  'riki_fair_connection',
        'fair_details'   =>  'riki_fair_details',
        'fair_recommendation'   =>  'riki_fair_recommendation'
    ];

    public function exportProcess()
    {
        /*export main process*/
        $this->export();

        /* set last time to run */
        $this->setLastRunToCron();

        /*move export folder to ftp*/
        $this->_sftpHelper->moveFileToFtp($this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->getReportPathExport());

        /*send notification email for bi export*/
        $this->sentNotificationEmail();
    }

    /**
     * set connection for all fair table
     */
    public function setConnection()
    {
        $this->_connection = $this->_connectionHelper->getDefaultConnection();
    }
    /**
     * @return $this
     */
    public function export()
    {
        $this->setConnection();

        foreach ($this->_fileToTable as $fileName    =>  $table) {
            try{
                $this->_exportTableToFile($fileName);
            }catch (\Exception $e){
                $this->_logger->error(__('Export %1 error: %2', $fileName, $e->getMessage()));
            }
        }
    }

    /**
     * @param string $fileName
     * @return array
     */
    protected function _loadData($fileName)
    {
        $table = $this->_fileToTable[$fileName];
        $columns = array_keys($this->_connection->describeTable($table));

        $select = $this->_connection->select()->from(
            $table, $columns
        )->where(
            $table . '.updated_at>?',
            $this->getLastRunToCron()
        );

        $query = $this->_connection->query($select);

        $resultData = [$columns];

        while($row = $query->fetch()){
            $resultData[] = $this->convertDateTimeColumnsToConfigTimezone($row);
        }

        return $resultData;
    }


    /**
     * @param string $fileName
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function _exportTableToFile($fileName)
    {
        /*get export data*/
        $resultData = $this->_loadData($fileName);

        /*export file name*/
        $exportFileName = $fileName .'-'.$this->_timezone->date()->format('YmdHis').'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportFileName => $resultData
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
        $fairAndSeasonalDateTimeColumns = $this->getFairAndSeasonalDateTimeColumns();

        foreach ($fairAndSeasonalDateTimeColumns as $cl) {
            if (!empty($object[$cl])) {
                $object[$cl] = $this->convertToConfigTimezone($object[$cl]);
            }
        }

        return $object;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_fair_connection, riki_fair_details, riki_fair_management, riki_fair_recommendation
     * @return mixed
     */
    public function getFairAndSeasonalDateTimeColumns()
    {
        if (empty($this->_fairAndSeasonalDateTimeColumns)) {
            $this->_fairAndSeasonalDateTimeColumns = $this->_dateTimeColumnsHelper->getFairAndSeasonalDateTimeColumns();
        }
        return $this->_fairAndSeasonalDateTimeColumns;
    }
}