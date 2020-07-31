<?php
namespace Riki\CedynaInvoice\Model\ResourceModel\Invoice;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define   variables
     * @var     string
     */
    protected $_idFieldName = 'id';

    /**
     * Define   resource model
     * @return  void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\CedynaInvoice\Model\Invoice',
            'Riki\CedynaInvoice\Model\ResourceModel\Invoice'
        );
        $this->_map['fields']['id'] = 'main_table.id';
    }
}
