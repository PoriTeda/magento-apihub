<?php

namespace Riki\User\Model\ResourceModel;

/**
 * Consumer Log mysql resource.
 */
class Password extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('admin_password_directory', 'pw_id');
    }



}