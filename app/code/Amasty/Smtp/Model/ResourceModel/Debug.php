<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Debug extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('amasty_amsmtp_debug', 'id');
    }

    public function truncate()
    {
        $this->getConnection()->truncateTable($this->getMainTable());
    }

    public function clear($days)
    {
        $connection = $this->getConnection();

        $sql = 'DELETE FROM ' . $this->getMainTable() .
            ' WHERE DATEDIFF(NOW(), created_at) > '.intval($days);

        $connection->query($sql);
    }
}
