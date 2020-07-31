<?php 
namespace Riki\AdminLog\Model;

class Log  extends \Magento\Framework\Model\AbstractModel
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\AdminLog\Model\ResourceModel\Log');
    }
}