<?php
namespace Riki\Catalog\Model\ResourceModel\ProductStatus;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'status_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Catalog\Model\ProductStatus', 'Riki\Catalog\Model\ResourceModel\ProductStatus');
        $this->_map['fields']['status_id'] = 'main_table.status_id';
    }

}