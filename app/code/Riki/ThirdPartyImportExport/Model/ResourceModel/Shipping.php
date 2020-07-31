<?php
namespace Riki\ThirdPartyImportExport\Model\ResourceModel;


class Shipping extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_shipping', 'shipping_no');
    }
}
