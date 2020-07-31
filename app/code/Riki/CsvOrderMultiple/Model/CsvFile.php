<?php

namespace Riki\CsvOrderMultiple\Model;

class CsvFile extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\CsvFile::class);
    }
}
