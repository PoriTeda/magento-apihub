<?php
namespace Riki\User\Model\ResourceModel\Password;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'pw_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\User\Model\Password',
            'Riki\User\Model\ResourceModel\Password'
        );
    }
    

}