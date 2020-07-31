<?php
namespace Riki\AdminLog\Model\ResourceModel;

/**
 * Consumer Log mysql resource
 */
class Log extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_admin_log', 'log_id');
    }


}