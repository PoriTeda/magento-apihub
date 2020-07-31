<?php


namespace Riki\StockPoint\Model\ResourceModel;

class StockPoint extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('stock_point', 'stock_point_id');
    }
}
