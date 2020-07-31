<?php
namespace Riki\ThirdPartyImportExport\Model\ResourceModel;


class Order extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize
     */
    protected function _construct()
    {
        $this->_init('riki_order', 'order_no');
    }
}
