<?php
namespace Riki\Customer\Model\ResourceModel\ConsumerLog;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Customer\Model\ConsumerLog',
            'Riki\Customer\Model\ResourceModel\ConsumerLog'
        );
    }
    

}