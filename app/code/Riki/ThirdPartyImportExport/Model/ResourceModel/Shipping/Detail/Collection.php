<?php
namespace Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping\Detail;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init(
            'Riki\ThirdPartyImportExport\Model\Shipping\Detail',
            'Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping\Detail'
        );
    }
}
