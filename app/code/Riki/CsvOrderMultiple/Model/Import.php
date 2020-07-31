<?php
namespace Riki\CsvOrderMultiple\Model;

class Import extends \Magento\Framework\Model\AbstractModel 
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\CsvOrderMultiple\Model\ResourceModel\Import');
    }

}