<?php

namespace Riki\CsvOrderMultiple\Model\ResourceModel;

class CsvFile extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_csv_order_import_history_download', 'entity_id');
    }
}
