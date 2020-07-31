<?php
namespace Riki\Subscription\Model\Frequency\ResourceModel\Frequency;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'frequency_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Frequency\Frequency', 'Riki\Subscription\Model\Frequency\ResourceModel\Frequency');
        $this->_map['fields']['frequency_id'] = 'main_table.frequency_id';
    }
}