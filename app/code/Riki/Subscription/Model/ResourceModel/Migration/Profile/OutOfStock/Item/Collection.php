<?php
namespace Riki\Subscription\Model\ResourceModel\Migration\Profile\OutOfStock\Item;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Riki\Subscription\Model\Migration\Profile\OutOfStock\Item::class,
            \Riki\Subscription\Model\ResourceModel\Migration\Profile\OutOfStock\Item::class);
    }
}