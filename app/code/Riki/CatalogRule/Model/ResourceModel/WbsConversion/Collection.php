<?php
namespace Riki\CatalogRule\Model\ResourceModel\WbsConversion;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected function _construct()
    {
        $this->_init('Riki\CatalogRule\Model\WbsConversion','Riki\CatalogRule\Model\ResourceModel\WbsConversion');
    }
}
