<?php
namespace Riki\CsvOrderMultiple\Model\ResourceModel;

class Import extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_init('riki_csv_order_import_history', 'entity_id');
    }
}