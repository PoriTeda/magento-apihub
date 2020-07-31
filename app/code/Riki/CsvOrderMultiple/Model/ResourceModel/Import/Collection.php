<?php
namespace Riki\CsvOrderMultiple\Model\ResourceModel\Import;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\CsvOrderMultiple\Model\Import', 'Riki\CsvOrderMultiple\Model\ResourceModel\Import');
        $this->_map['fields']['entity_id'] = 'main_table.entity_id';
    }

}