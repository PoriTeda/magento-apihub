<?php
namespace Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'Riki\ThirdPartyImportExport\Model\Shipping',
            'Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping'
        );
    }
}
