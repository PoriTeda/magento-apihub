<?php
namespace Riki\AdminLog\Model\ResourceModel\Log;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'log_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\AdminLog\Model\Log',
            'Riki\AdminLog\Model\ResourceModel\Log'
        );
    }
    

}