<?php
namespace Riki\NpAtobarai\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @package Riki\NpAtobarai\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * InstallSchema constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();
        $salesConnection = $this->resourceConnection->getConnection('sales');
        $transactionTable = $salesConnection->newTable($setup->getTable('riki_np_atobarai_transaction'));
        
        $transactionTable->addColumn(
            'transaction_id',
            Table::TYPE_INTEGER,
            10,
            ['primary' => true,'nullable' => false,'auto_increment' => true,'unsigned' => true],
            'Transaction Id'
        );

        $transactionTable->addColumn(
            'order_id',
            Table::TYPE_INTEGER,
            10,
            ['nullable' => false,'unsigned' => true],
            'Reference to sales_order.entity_id'
        );

        $transactionTable->addColumn(
            'order_shipping_address_id',
            Table::TYPE_INTEGER,
            10,
            ['nullable' => false,'unsigned' => true],
            'Reference to sales_order_address.address_id'
        );

        $transactionTable->addColumn(
            'shipment_id',
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true],
            'Reference to sales_shipment.entity_id, It be set after shipment created'
        );

        $transactionTable->addColumn(
            'delivery_type',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Delivery Type'
        );

        $transactionTable->addColumn(
            'warehouse',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Warehouse'
        );

        $transactionTable->addColumn(
            'billed_amount',
            Table::TYPE_DECIMAL,
            '12,4',
            ['default' => '0','nullable' => false, 'unsigned' => true],
            'Billed Amount'
        );

        $transactionTable->addColumn(
            'np_transaction_id',
            Table::TYPE_TEXT,
            255,
            [],
            'NP Transaction Id'
        );

        $transactionTable->addColumn(
            'register_error_codes',
            Table::TYPE_TEXT,
            255,
            [],
            'Register Error Codes'
        );

        $transactionTable->addColumn(
            'np_transaction_status',
            Table::TYPE_TEXT,
            32,
            [],
            '00：OK 10：PENDING  20：NG  30：ER  40：Before validation  50：In validation  99: Cancelled'
        );

        $transactionTable->addColumn(
            'authorize_required_at',
            Table::TYPE_DATETIME,
            null,
            [],
            'YYYY-MM-DD hh:ii:ss'
        );

        $transactionTable->addColumn(
            'authori_ng',
            Table::TYPE_TEXT,
            32,
            [],
            'NG001: Over the maximum amount  NG999: other'
        );

        $transactionTable->addColumn(
            'authorize_pending_reason_codes',
            Table::TYPE_TEXT,
            255,
            [],
            'Multiple values: RE001,RE002'
        );

        $transactionTable->addColumn(
            'authorize_error_codes',
            Table::TYPE_TEXT,
            255,
            [],
            'Multiple values: E0000001,E0000002'
        );

        $transactionTable->addColumn(
            'cancel_error_codes',
            Table::TYPE_TEXT,
            255,
            [],
            'Multiple values: E0000001,E0000002'
        );

        $transactionTable->addColumn(
            'is_shipped_out_registered',
            Table::TYPE_BOOLEAN,
            null,
            ['default' => '0'],
            'Is Shipped Out Registered'
        );

        $transactionTable->addColumn(
            'shipped_out_register_error_codes',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Multiple values: E0000001,E0000002'
        );

        $transactionTable->addColumn(
            'np_customer_payment_status',
            Table::TYPE_TEXT,
            5,
            [],
            '10: Not Paid yet  20: Paid  30: Secret'
        );

        $transactionTable->addColumn(
            'np_customer_payment_date',
            Table::TYPE_DATETIME,
            null,
            [],
            'Np Customer Payment Date'
        );

        $transactionTable->addColumn(
            'goods',
            Table::TYPE_TEXT,
            null,
            [],
            '"List of products by json type: [ { ""goods_name"" : ""商品１"", ""goods_price"" : 1000, ""quantity"" : 3 }, 
            { ""goods_name"" : ""割引"", ""goods_price"" : -500, ""quantity"" : 1 } ] "'
        );

        $transactionTable->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        );

        $transactionTable->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Created At'
        );

        $transactionTable->addForeignKey(
            $setup->getFkName('riki_np_atobarai_transaction', 'order_id', 'sales_order', 'entity_id'),
            'order_id',
            $setup->getTable('sales_order'),
            'entity_id',
            Table::ACTION_CASCADE
        )->addForeignKey(
            $setup->getFkName('riki_np_atobarai_transaction', 'shipment_id', 'sales_shipment', 'entity_id'),
            'shipment_id',
            $setup->getTable('sales_shipment'),
            'entity_id',
            Table::ACTION_CASCADE
        )->addForeignKey(
            $setup->getFkName(
                'riki_np_atobarai_transaction',
                'order_shipping_address_id',
                'sales_order_address',
                'entity_id'
            ),
            'order_shipping_address_id',
            $setup->getTable('sales_order_address'),
            'entity_id',
            Table::ACTION_CASCADE
        );

        $transactionTable->addIndex(
            $installer->getIdxName(
                'riki_np_atobarai_transaction',
                ['order_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['order_id'],
            AdapterInterface::INDEX_TYPE_INDEX
        )->addIndex(
            $installer->getIdxName(
                'riki_np_atobarai_transaction',
                ['np_transaction_id'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['np_transaction_id'],
            AdapterInterface::INDEX_TYPE_INDEX
        )->addIndex(
            $installer->getIdxName(
                'riki_np_atobarai_transaction',
                ['np_transaction_status'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['np_transaction_status'],
            AdapterInterface::INDEX_TYPE_INDEX
        )->addIndex(
            $installer->getIdxName(
                'riki_np_atobarai_transaction',
                ['is_shipped_out_registered'],
                AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['is_shipped_out_registered'],
            AdapterInterface::INDEX_TYPE_INDEX
        );

        $transactionTable->addIndex(
            $installer->getIdxName(
                'riki_np_atobarai_transaction',
                ['shipment_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['shipment_id'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        );

        $salesConnection->createTable($transactionTable);

        $setup->endSetup();
    }
}
