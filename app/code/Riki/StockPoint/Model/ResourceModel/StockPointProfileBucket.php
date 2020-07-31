<?php


namespace Riki\StockPoint\Model\ResourceModel;

class StockPointProfileBucket extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('stock_point_profile_bucket', 'profile_bucket_id');
    }
}
