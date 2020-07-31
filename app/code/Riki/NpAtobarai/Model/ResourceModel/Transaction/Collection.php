<?php

namespace Riki\NpAtobarai\Model\ResourceModel\Transaction;

use Riki\NpAtobarai\Api\Data\TransactionInterface;

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
            \Riki\NpAtobarai\Model\Transaction::class,
            \Riki\NpAtobarai\Model\ResourceModel\Transaction::class
        );
        $this->_map['fields'][TransactionInterface::IS_SHIPPED_OUT_REGISTERED] =
            'main_table.' . TransactionInterface::IS_SHIPPED_OUT_REGISTERED;
    }
}
