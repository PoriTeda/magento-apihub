<?php
namespace Riki\Rule\Model\ResourceModel\OrderSapBooking;

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
        $this->_init('Riki\Rule\Model\OrderSapBooking', 'Riki\Rule\Model\ResourceModel\OrderSapBooking');
        $this->_map['fields']['id'] = 'main_table.id';
    }
}