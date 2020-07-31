<?php


namespace Riki\StockPoint\Model\ResourceModel;

class StockPointDeliveryBucket extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('stock_point_delivery_bucket', 'delivery_bucket_id');
    }
}
