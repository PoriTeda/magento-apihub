<?php
namespace Riki\ThirdPartyImportExport\Model\ResourceModel\Order;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'Riki\ThirdPartyImportExport\Model\Order',
            'Riki\ThirdPartyImportExport\Model\ResourceModel\Order'
        );
    }
}
