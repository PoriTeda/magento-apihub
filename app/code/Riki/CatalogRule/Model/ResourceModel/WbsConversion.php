<?php

namespace Riki\CatalogRule\Model\ResourceModel;

class WbsConversion extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_wbs_conversion','entity_id');
    }
}
