<?php


namespace Riki\StockPoint\Model\ResourceModel\StockPoint;

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
            \Riki\StockPoint\Model\StockPoint::class,
            \Riki\StockPoint\Model\ResourceModel\StockPoint::class
        );
    }
}
