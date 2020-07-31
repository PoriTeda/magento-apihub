<?php
namespace Riki\Prize\Model\ResourceModel\Prize;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'prize_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Prize\Model\Prize', 'Riki\Prize\Model\ResourceModel\Prize');
        $this->_map['fields']['prize_id'] = 'main_table.prize_id';
    }

}