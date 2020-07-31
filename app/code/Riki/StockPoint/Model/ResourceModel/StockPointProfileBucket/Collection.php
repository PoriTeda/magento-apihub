<?php


namespace Riki\StockPoint\Model\ResourceModel\StockPointProfileBucket;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Riki\StockPoint\Model\StockPointProfileBucket::class,
            \Riki\StockPoint\Model\ResourceModel\StockPointProfileBucket::class
        );
    }
}
