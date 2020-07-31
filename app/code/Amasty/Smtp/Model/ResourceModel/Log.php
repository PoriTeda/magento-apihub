<?php
/**
 * @author    Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package   Amasty_Smtp
 */

namespace Amasty\Smtp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Log extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('amasty_amsmtp_log_new', 'id');
    }

    public function truncate()
    {
        $this->getConnection()->truncateTable($this->getMainTable());
    }

    public function clear($days, $dirLocal)
    {
        $connection = $this->getConnection();

        $result = $this->backup($days, $dirLocal, $connection);

        if ($result) {
            $sql = 'DELETE FROM ' . $this->getMainTable() .
                   ' WHERE DATEDIFF(NOW(), created_at) > ' . intval($days);

            $connection->query($sql);
        }
    }

    /**
     * backup before clear data in amasty email log
     *
     * @param $days
     * @param $dirLocal
     * @param $connection
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function backup($days, $dirLocal, $connection)
    {
        $sql = 'SELECT * FROM ' . $this->getMainTable() .
               ' WHERE DATEDIFF(NOW(), created_at) > ' . intval($days);

        $list = $connection->fetchAll($sql);

        if (count($list)) {
            $fp = fopen($dirLocal . DS . "amasty_amsmtp_log_" . date('Ymd_His') . ".csv", 'a');

            $headers = array();
            foreach (array_keys($list[0]) as $key) {
                $headers[] = $key;
            }

            fputcsv($fp, $headers, ',');
            foreach ($list as $fields) {
                fputcsv($fp, $fields);
            }
            fclose($fp);

            return true;
        }
        return false;
    }
}
