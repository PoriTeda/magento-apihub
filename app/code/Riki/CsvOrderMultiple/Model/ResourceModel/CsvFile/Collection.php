<?php

namespace Riki\CsvOrderMultiple\Model\ResourceModel\CsvFile;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Riki\CsvOrderMultiple\Model\CsvFile::class,
            \Riki\CsvOrderMultiple\Model\ResourceModel\CsvFile::class
        );
    }
}
