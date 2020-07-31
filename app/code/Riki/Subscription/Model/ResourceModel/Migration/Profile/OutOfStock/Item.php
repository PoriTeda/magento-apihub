<?php
namespace Riki\Subscription\Model\ResourceModel\Migration\Profile\OutOfStock;

class Item extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var bool
     */
    protected static $initialized = false;

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        if (!self::$initialized) {
            $this->createTmpTable();
        }

        $this->_init('tmp_subscription_migration_profile_cart_oos', 'id');
    }

    /**
     * Create table
     *
     * @return void
     */
    public function createTmpTable()
    {
        $connection = $this->getConnection();
        $tableName = $connection->getTableName('tmp_subscription_migration_profile_cart_oos');
        $connection->dropTemporaryTable($tableName);
        $table = $connection->newTable($tableName);
        $table->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            [
                'identity' => true,
                'primary' => true,
                'unsigned' => true,
                'nullable' => false
            ],
            'Id'
        );
        $table->addColumn(
            'profile_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [
                'nullable' => false
            ],
            'profile_id'
        );
        $table->addColumn(
            'qty',
            \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
            '12,4',
            [
                'nullable' => false
            ],
            'qty'
        );
        $table->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [
                'nullable' => false
            ],
            'product_id'
        );
        $table->addColumn(
            'unit',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            16,
            [
            ],
            'unit'
        );
        $table->addColumn(
            'unit_qty',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            [
            ],
            'unit_qty'
        );
        $table->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [
                'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT
            ],
            'created_at'
        );
        $table->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [
                'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE
            ],
            'updated_at'
        );
        $table->addColumn(
            'billing_address_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            [
            ],
            'billing_address_id'
        );
        $table->addColumn(
            'shipping_address_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            [
            ],
            'shipping_address_id'
        );
        $table->addColumn(
            'delivery_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [
            ],
            'delivery_date'
        );
        $table->addColumn(
            'retail_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
            '12,4',
            [
                'nullable' => false
            ],
            'retail_price'
        );
        $table->addColumn(
            'unit_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
            '12,4',
            [
            ],
            'unit_price'
        );
        $table->addColumn(
            'order_times',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            [
            ],
            'order_times'
        );
        $table->addColumn(
            'applied_point_rate',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            [
            ],
            'applied_point_rate'
        );
        $this->getConnection()->createTemporaryTable($table);

        self::$initialized = true;
    }
}