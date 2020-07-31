<?php
namespace Riki\ThirdPartyImportExport\Model\ResourceModel\Order;

class Detail extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_order_detail', 'order_no,shop_code,sku_code');
    }
}