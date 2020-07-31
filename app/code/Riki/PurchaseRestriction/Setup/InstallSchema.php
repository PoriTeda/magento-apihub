<?php
namespace Riki\PurchaseRestriction\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    protected $_salesConnection;

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order $orderResource
    ){
        $this->_salesConnection = $orderResource->getConnection();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $connection = $this->_salesConnection;

        if(!$setup->tableExists($setup->getTable('riki_purchase_history'))){
            $table = $connection->newTable($setup->getTable('riki_purchase_history'))
                ->addColumn(
                    'customer_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Customer Id'
                )->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Order Id'
                )->addColumn(
                    'sku',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false, 'default' => '0'
                    ],
                    'Product Sku'
                )->addColumn(
                    'qty',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false, 'default' => '0'
                    ],
                    'Qty'
                )->addColumn(
                    'ordered_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'default'   =>  Table::TIMESTAMP_INIT
                    ],
                    'Ordered Datetime'
                )->addIndex(
                    $installer->getIdxName('riki_purchase_history', ['customer_id']),
                    ['customer_id']
                )->addIndex(
                    $installer->getIdxName('riki_purchase_history', ['order_id']),
                    ['order_id']
                )->addIndex(
                    $installer->getIdxName('riki_purchase_history', ['sku']),
                    ['sku']
                )->addForeignKey(
                    $installer->getFkName(
                        'riki_purchase_history',
                        'order_id',
                        'sales_order',
                        'entity_id'
                    ),
                    'order_id',
                    $installer->getTable('sales_order'),
                    'entity_id',
                    Table::ACTION_CASCADE
                )->setComment('Purchase History (Order - Customer - Product)');

            $connection->createTable($table);
        }

        $installer->endSetup();
    }
}
