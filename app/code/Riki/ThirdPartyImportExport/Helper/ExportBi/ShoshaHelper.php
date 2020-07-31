<?php
namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

class ShoshaHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    /*main table*/
    const SHOSHATABLE = 'riki_shosha_business_code';
    /*column prefix*/
    const SHOSHAPREFIX = 'shosha.';

    /*export type: cedyna(true) or bi export(false)*/
    protected $_exportType;

    /**
     * Set export type
     *      value: true (cedyna export), false (bi export)
     * @param $type
     */
    public function setExportType($type)
    {
        $this->_exportType = $type;
    }

    /**
     * @param bool $cedyna
     */
    public function exportProcess($cedyna = false)
    {
        /*set export type*/
        $this->setExportType($cedyna);

        /*export main process*/
        $this->export();

        /* set last time to run */
        $this->setLastRunToCron();

        /*move export folder to ftp*/
        $this->_sftpHelper->moveFileToFtp($this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->getReportPathExport());

        /*send notification email for bi export*/
        if (!$this->_exportType) {
            /*send email notify*/
            $this->sentNotificationEmail();
        }
    }

    public function export()
    {
        /*get export data*/
        $exportData = $this->_loadData();
        /*export date*/
        $exportDate = $this->_timezone->date()->format('YmdHis');

        $exportFileName = 'shosha-'.$exportDate.'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportFileName => $exportData
        ]);
    }

    /**
     * @param bool $cedyna
     * @return array
     */
    protected function _loadData()
    {
        /*default connection for shosha table*/
        $connection = $this->_connectionHelper->getDefaultConnection();

        $table = $connection->getTableName(self::SHOSHATABLE);

        $columns = array_keys($connection->describeTable($table));

        if ($this->_exportType) {
            $condition = $table.'.is_cedyna_exported = 0';
        } else {
            $condition = $table.'.is_bi_exported = 0';
        }

        $select = $connection->select()->from(
            $table
        )->where($condition);

        if ($this->_exportType) {
            $select->where( $table.'.shosha_code = ?', \Riki\Customer\Model\Shosha\ShoshaCode::CEDYNA );
        }

        $query = $connection->query($select);

        /*export data { first record is columns name }*/
        $resultData = [$this->addColumnPrefix($columns)];

        /*array of record will be update field value { is_bi_exported = 1 || is_cedyna_exported = 1 }*/
        $updateList = [];

        while ($row = $query->fetch()) {
            /*push record to export data*/
            $resultData[] = $this->convertDateTimeColumnsToConfigTimezone($row);
            /*get success export id*/
            $updateList[] = $row['id'];
        }

        /*update data to make system know this record is exported to BI or CEDYNA*/
        if (!empty( $updateList)) {

            if ($this->_exportType) {
                $bind = [ 'is_cedyna_exported' => 1 ];
            } else {
                $bind = [ 'is_bi_exported' => 1 ];
            }

            $where = ['id IN (?)' => $updateList];

            $connection->update($table, $bind, $where);
        }

        return $resultData;
    }

    /**
     * @param $columns
     * @return array
     */
    public function addColumnPrefix($columns)
    {
        return array_map(function($value) { return self::SHOSHAPREFIX.$value; }, $columns);
    }

    /**
     * check columns with data type is datetime or timestamp
     *
     * @param array $object
     * @return array
     */
    public function convertDateTimeColumnsToConfigTimezone($object)
    {
        $shoshaCustomerDateTimeColumns = $this->getShoshaCustomerDateTimeColumns();

        foreach ($shoshaCustomerDateTimeColumns as $cl) {
            if (!empty($object[$cl])) {
                $object[$cl] = $this->convertToConfigTimezone($object[$cl]);
            }
        }

        return $object;
    }

    /**
     * list columns which data type is date time or time stamp, to be convert to config time zone
     *      table riki_shosha_business_code
     * @return mixed
     */
    public function getShoshaCustomerDateTimeColumns()
    {
        if (empty($this->_shoshaCustomerDateTimeColumns)) {
            $this->_shoshaCustomerDateTimeColumns = $this->_dateTimeColumnsHelper->getShoshaCustomerDateTimeColumns();
        }
        return $this->_shoshaCustomerDateTimeColumns;
    }
}