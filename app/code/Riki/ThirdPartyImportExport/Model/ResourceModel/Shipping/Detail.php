<?php
namespace Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping;

class Detail extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_shipping_detail', 'shipping_no,shipping_detail_no');
    }
}
