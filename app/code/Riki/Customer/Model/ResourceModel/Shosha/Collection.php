<?php
namespace Riki\Customer\Model\ResourceModel\Shosha;

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
            'Riki\Customer\Model\Shosha',
            'Riki\Customer\Model\ResourceModel\Shosha'
        );
    }


}